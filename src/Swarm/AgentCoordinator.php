<?php

namespace Atom\Swarm;

use Atom\Agent\AgentOrchestrator;
use Atom\Telemetry\TelemetryManager;
use CodeIgniter\Database\BaseConnection;

class AgentCoordinator
{
    private AgentSelector $agentSelector;
    private SwarmBudgetManager $budgetManager;
    private ResultVerifier $verifier;
    private ConflictResolver $conflictResolver;
    private Synthesizer $synthesizer;

    public function __construct(
        ?AgentSelector $agentSelector = null,
        ?SwarmBudgetManager $budgetManager = null,
        ?ResultVerifier $verifier = null,
        ?ConflictResolver $conflictResolver = null,
        ?Synthesizer $synthesizer = null
    ) {
        $this->agentSelector    = $agentSelector ?? new AgentSelector();
        $this->budgetManager    = $budgetManager ?? new SwarmBudgetManager();
        $this->verifier         = $verifier ?? new ResultVerifier();
        $this->conflictResolver = $conflictResolver ?? new ConflictResolver();
        $this->synthesizer      = $synthesizer ?? new Synthesizer();
    }

    private function getDb(): ?BaseConnection
    {
        try {
            return \Config\Database::connect();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Executes a bounded multi-agent swarm task cleanly.
     */
    public function runSwarm(string $objective, int $userId = 1, ?int $workflowExecutionId = null): SwarmExecution
    {
        $span = TelemetryManager::getInstance()->startSpan('swarm.execute');

        $swarmData = [
            'user_id'               => $userId,
            'workflow_execution_id' => $workflowExecutionId,
            'objective'             => $objective,
            'status'                => 'running',
            'coordinator_agent_id'  => 1,
            'max_agents'            => 8,
            'created_at'            => date('Y-m-d H:i:s'),
            'started_at'            => date('Y-m-d H:i:s'),
        ];

        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_swarm_executions'), true)->insert($swarmData);
                $swarmData['id'] = (int)$db->insertID();
            } catch (\Throwable $e) {
                $swarmData['id'] = time();
            }
        } else {
            $swarmData['id'] = time();
        }

        $swarm = new SwarmExecution($swarmData);
        $this->logEvent($swarm->id, null, 'swarm.started', ['objective' => $objective]);

        // Step 1: Decompose objective into specialized worker tasks (Researcher + Analyst)
        $researcherDef = $this->agentSelector->selectAgentForRole('researcher', $userId);
        $analystDef    = $this->agentSelector->selectAgentForRole('analyst', $userId);

        $orchestrator = new AgentOrchestrator();

        // Worker 1: Researcher Task
        $rTask = $orchestrator->createTask("Research evidence for: {$objective}", $userId);
        $orchestrator->runTask($rTask);

        // Worker 2: Analyst Task
        $aTask = $orchestrator->createTask("Analyze findings for: {$objective}", $userId);
        $orchestrator->runTask($aTask);

        $workerOutputs = [
            ['role' => 'researcher', 'output' => $rTask->result ?? $rTask->error, 'status' => $rTask->status],
            ['role' => 'analyst', 'output' => $aTask->result ?? $aTask->error, 'status' => $aTask->status],
        ];

        // Step 2: Independent Result Verification
        $verifiedOutputs = [];
        foreach ($workerOutputs as $wOut) {
            $vRes = $this->verifier->verifyResult($wOut);
            if ($vRes['verified']) {
                $verifiedOutputs[] = $wOut;
            }
        }

        // Step 3: Synthesis
        SwarmStateMachine::validateTransition($swarm->status, 'synthesizing');
        $swarm->status = 'synthesizing';
        $this->saveSwarm($swarm);

        $finalReport = $this->synthesizer->synthesize($objective, $verifiedOutputs);

        // Step 4: Completion
        SwarmStateMachine::validateTransition($swarm->status, 'completed');
        $swarm->status      = 'completed';
        $swarm->completedAt = date('Y-m-d H:i:s');
        $swarm->result      = $finalReport;
        $this->saveSwarm($swarm);

        $this->logEvent($swarm->id, null, 'swarm.completed', ['result' => $finalReport]);
        TelemetryManager::getInstance()->endSpan($span, 'ok');

        return $swarm;
    }

    private function saveSwarm(SwarmExecution $swarm): void
    {
        $db = $this->getDb();
        if ($db !== null && $swarm->id > 0) {
            try {
                $db->table($db->prefixTable('atom_swarm_executions'), true)
                   ->where('id', $swarm->id)
                   ->update($swarm->toArray());
            } catch (\Throwable $e) {}
        }
    }

    private function logEvent(int $swarmId, ?int $memberId, string $eventType, array $payload = []): void
    {
        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_swarm_events'), true)->insert([
                    'swarm_id'     => $swarmId,
                    'member_id'    => $memberId,
                    'event_type'   => $eventType,
                    'payload_json' => json_encode($payload),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
        }
    }
}
