<?php

namespace Atom\Agent;

use Atom\Tools\ToolManager;
use Atom\Security\HumanApprovalGate;
use Atom\Telemetry\TelemetryManager;
use CodeIgniter\Database\BaseConnection;

class AgentOrchestrator
{
    private Planner $planner;
    private PlanValidator $validator;
    private AgentExecutor $executor;
    private ObservationEngine $observationEngine;
    private AgentVerifier $verifier;
    private Replanner $replanner;
    private AgentBudgetManager $budgetManager;
    private ?ToolManager $toolManager;
    private ?HumanApprovalGate $approvalGate;

    public function __construct(
        ?Planner $planner = null,
        ?PlanValidator $validator = null,
        ?AgentExecutor $executor = null,
        ?ObservationEngine $observationEngine = null,
        ?AgentVerifier $verifier = null,
        ?Replanner $replanner = null,
        ?AgentBudgetManager $budgetManager = null,
        ?ToolManager $toolManager = null,
        ?HumanApprovalGate $approvalGate = null
    ) {
        $this->planner           = $planner ?? new Planner();
        $this->validator         = $validator ?? new PlanValidator();
        $this->executor          = $executor ?? new AgentExecutor($toolManager, $approvalGate);
        $this->observationEngine = $observationEngine ?? new ObservationEngine();
        $this->verifier          = $verifier ?? new AgentVerifier();
        $this->replanner         = $replanner ?? new Replanner();
        $this->budgetManager     = $budgetManager ?? new AgentBudgetManager();
        $this->toolManager       = $toolManager;
        $this->approvalGate      = $approvalGate;
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
     * Creates and initializes a new agent task.
     */
    public function createTask(string $objective, int $userId = 1, array $options = []): AgentTask
    {
        $taskData = [
            'user_id'             => $userId,
            'title'               => $options['title'] ?? 'Agent Task',
            'objective'           => $objective,
            'status'              => 'pending',
            'priority'            => $options['priority'] ?? 'normal',
            'max_steps'           => $options['max_steps'] ?? 20,
            'max_tool_calls'      => $options['max_tool_calls'] ?? 10,
            'max_tokens'          => $options['max_tokens'] ?? 8000,
            'max_runtime_seconds' => $options['max_runtime_seconds'] ?? 300,
            'max_cost'            => $options['max_cost'] ?? 1.0,
            'max_replans'         => $options['max_replans'] ?? 3,
            'risk_level'          => 'low',
            'created_at'          => date('Y-m-d H:i:s'),
        ];

        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_agent_tasks'), true)->insert($taskData);
                $taskData['id'] = (int)$db->insertID();
            } catch (\Throwable $e) {
                $taskData['id'] = time();
            }
        } else {
            $taskData['id'] = time();
        }

        $task = new AgentTask($taskData);
        $this->logEvent($task->id, null, 'task.created', ['objective' => $objective]);
        return $task;
    }

    /**
     * Plans and executes a task through to completion or approval wait state.
     */
    public function runTask(AgentTask $task): AgentTask
    {
        $span = TelemetryManager::getInstance()->startSpan('agent.task');

        // Transition pending -> planning
        AgentStateMachine::validateTransition($task->status, 'planning');
        $task->status = 'planning';
        $task->startedAt = date('Y-m-d H:i:s');
        $this->saveTask($task);
        $this->logEvent($task->id, null, 'task.planning', []);

        // Generate plan
        $plan = $this->planner->generatePlan($task->objective);
        $validation = $this->validator->validatePlan($plan, $task, $this->toolManager);

        if (!$validation['valid']) {
            $task->status = 'failed';
            $task->error  = $validation['error'];
            $this->saveTask($task);
            $this->logEvent($task->id, null, 'task.failed', ['error' => $task->error]);
            TelemetryManager::getInstance()->endSpan($span, 'error');
            return $task;
        }

        // Save planned steps
        $steps = $this->persistPlanSteps($task->id, $plan['steps']);
        $task->status = 'planned';
        $this->saveTask($task);
        $this->logEvent($task->id, null, 'plan.created', ['step_count' => count($steps)]);

        // Transition planned -> running
        AgentStateMachine::validateTransition($task->status, 'running');
        $task->status = 'running';
        $this->saveTask($task);

        // Execute steps sequentially
        foreach ($steps as $stepObj) {
            // Budget check
            $budgetCheck = $this->budgetManager->checkBudget($task);
            if ($budgetCheck['exceeded']) {
                $task->status = 'failed';
                $task->error  = $budgetCheck['reason'];
                $this->saveTask($task);
                $this->logEvent($task->id, $stepObj->id, 'task.budget_exceeded', ['reason' => $budgetCheck['reason']]);
                TelemetryManager::getInstance()->endSpan($span, 'error');
                return $task;
            }

            $task->currentStep = $stepObj->sequence;
            $stepObj->status    = 'running';
            $stepObj->startedAt = date('Y-m-d H:i:s');
            $this->saveStep($stepObj);
            $this->logEvent($task->id, $stepObj->id, 'step.started', ['sequence' => $stepObj->sequence, 'type' => $stepObj->type]);

            // Execute
            $execResult = $this->executor->executeStep($task, $stepObj);
            $obs = $this->observationEngine->generateObservation($stepObj, $execResult);
            $stepObj->observation = $obs;
            $stepObj->output      = $execResult['output'] ?? null;
            $stepObj->error       = $execResult['error'] ?? null;

            // Verify
            $verResult = $this->verifier->verifyStep($task, $stepObj, $execResult);

            if ($verResult['status'] === 'waiting_approval') {
                $stepObj->status = 'waiting_approval';
                $stepObj->requiresApproval = true;
                $this->saveStep($stepObj);

                $task->status = 'waiting_approval';
                $task->requiresApproval = true;
                $this->saveTask($task);

                $this->logEvent($task->id, $stepObj->id, 'approval.required', ['tool' => $stepObj->toolName]);
                TelemetryManager::getInstance()->endSpan($span, 'ok');
                return $task;
            }

            if ($verResult['status'] === 'replan') {
                $stepObj->status = 'failed';
                $this->saveStep($stepObj);
                $this->logEvent($task->id, $stepObj->id, 'verification.replan_required', ['reason' => $verResult['reason']]);
                // Perform replan
                $replanRes = $this->replanner->replan($task, [$stepObj], $verResult['reason']);
                $this->logEvent($task->id, null, 'task.replanned', ['reason' => $verResult['reason']]);
            } else {
                $stepObj->status = 'completed';
                $stepObj->completedAt = date('Y-m-d H:i:s');
                $this->saveStep($stepObj);
                $this->logEvent($task->id, $stepObj->id, 'step.completed', ['sequence' => $stepObj->sequence]);
            }

            $this->saveTask($task);
        }

        // Transition running -> verifying -> completed
        AgentStateMachine::validateTransition($task->status, 'verifying');
        $task->status = 'verifying';
        $this->saveTask($task);

        AgentStateMachine::validateTransition($task->status, 'completed');
        $task->status      = 'completed';
        $task->completedAt = date('Y-m-d H:i:s');
        $task->result      = 'Task completed successfully for objective: ' . $task->objective;
        $this->saveTask($task);

        $this->logEvent($task->id, null, 'task.completed', ['result' => $task->result]);
        TelemetryManager::getInstance()->endSpan($span, 'ok');

        return $task;
    }


    private function persistPlanSteps(int $taskId, array $stepsData): array
    {
        $db = $this->getDb();
        $objects = [];

        foreach ($stepsData as $s) {
            $data = [
                'task_id'           => $taskId,
                'sequence'          => $s['sequence'] ?? 1,
                'type'              => $s['type'] ?? 'reasoning',
                'description'       => $s['description'] ?? '',
                'status'            => 'pending',
                'tool_name'         => $s['tool'] ?? null,
                'requires_approval' => ($s['risk'] ?? '') === 'high' ? 1 : 0,
            ];

            if ($db !== null) {
                try {
                    $db->table($db->prefixTable('atom_agent_steps'), true)->insert($data);
                    $data['id'] = (int)$db->insertID();
                } catch (\Throwable $e) {
                    $data['id'] = time() + rand(1, 999);
                }
            } else {
                $data['id'] = time() + rand(1, 999);
            }

            $objects[] = new AgentStep($data);
        }

        return $objects;
    }

    private function saveTask(AgentTask $task): void
    {
        $db = $this->getDb();
        if ($db !== null && $task->id > 0) {
            try {
                $db->table($db->prefixTable('atom_agent_tasks'), true)
                   ->where('id', $task->id)
                   ->update($task->toArray());
            } catch (\Throwable $e) {}
        }
    }

    private function saveStep(AgentStep $step): void
    {
        $db = $this->getDb();
        if ($db !== null && $step->id > 0) {
            try {
                $db->table($db->prefixTable('atom_agent_steps'), true)
                   ->where('id', $step->id)
                   ->update($step->toArray());
            } catch (\Throwable $e) {}
        }
    }

    private function logEvent(int $taskId, ?int $stepId, string $eventType, array $payload = []): void
    {
        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_agent_events'), true)->insert([
                    'task_id'    => $taskId,
                    'step_id'    => $stepId,
                    'event_type' => $eventType,
                    'payload'    => json_encode($payload),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
        }
    }
}
