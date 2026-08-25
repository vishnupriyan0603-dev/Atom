<?php

namespace Atom\Swarm;

/**
 * Swarm Orchestration Hub — Phase 41
 * Manages autonomous multi-agent task delegation, weighted consensus voting,
 * and unified artifact synthesis across specialized agent roles.
 */
class SwarmOrchestrationHub
{
    private array $registeredAgents;
    private array $taskHistory;

    public function __construct()
    {
        $this->registeredAgents = [
            'architect' => [
                'name'        => 'System Architect',
                'role'        => 'architect',
                'weight'      => 1.5,
                'status'      => 'ready',
                'capabilities'=> ['system_design', 'api_contract', 'dag_decomposition']
            ],
            'coder' => [
                'name'        => 'Principal Coder',
                'role'        => 'coder',
                'weight'      => 1.2,
                'status'      => 'ready',
                'capabilities'=> ['code_generation', 'refactoring', 'ast_patching']
            ],
            'reviewer' => [
                'name'        => 'Code Reviewer',
                'role'        => 'reviewer',
                'weight'      => 1.0,
                'status'      => 'ready',
                'capabilities'=> ['code_quality', 'style_check', 'performance_audit']
            ],
            'security' => [
                'name'        => 'Security Inspector',
                'role'        => 'security',
                'weight'      => 1.8,
                'status'      => 'ready',
                'capabilities'=> ['vulnerability_scan', 'invariant_check', 'path_traversal_guard']
            ],
            'synthesizer' => [
                'name'        => 'Swarm Synthesizer',
                'role'        => 'synthesizer',
                'weight'      => 1.3,
                'status'      => 'ready',
                'capabilities'=> ['consensus_merge', 'conflict_resolution', 'artifact_build']
            ]
        ];
        $this->taskHistory = [];
    }

    /**
     * Get registered agent definitions and current swarm topology.
     */
    public function getSwarmTopology(): array
    {
        return [
            'swarm_id'         => 'swarm_' . date('Ymd_His'),
            'total_agents'     => count($this->registeredAgents),
            'active_agents'    => count(array_filter($this->registeredAgents, fn($a) => $a['status'] === 'ready')),
            'agents'           => array_values($this->registeredAgents),
            'consensus_engine' => 'weighted_majority_vote',
            'quorum_threshold' => 0.65
        ];
    }

    /**
     * Decompose a goal into multi-agent work orders.
     */
    public function planSwarmExecution(string $goal): array
    {
        $goal = trim($goal);
        if (empty($goal)) {
            throw new \InvalidArgumentException('Goal cannot be empty for swarm planning.');
        }

        $workOrders = [
            [
                'order_id'       => 'order_arch_' . uniqid(),
                'agent_role'     => 'architect',
                'task_name'      => 'Design Architecture & Data Contracts',
                'dependencies'   => [],
                'estimated_tps'  => 120,
                'status'         => 'ready'
            ],
            [
                'order_id'       => 'order_code_' . uniqid(),
                'agent_role'     => 'coder',
                'task_name'      => 'Implement Core Logic & Middleware',
                'dependencies'   => ['order_arch'],
                'estimated_tps'  => 250,
                'status'         => 'pending'
            ],
            [
                'order_id'       => 'order_sec_' . uniqid(),
                'agent_role'     => 'security',
                'task_name'      => 'Verify Invariants & Vulnerability Surface',
                'dependencies'   => ['order_code'],
                'estimated_tps'  => 90,
                'status'         => 'pending'
            ],
            [
                'order_id'       => 'order_rev_' . uniqid(),
                'agent_role'     => 'reviewer',
                'task_name'      => 'Review AST Cleanliness & Test Coverage',
                'dependencies'   => ['order_code'],
                'estimated_tps'  => 110,
                'status'         => 'pending'
            ]
        ];

        $plan = [
            'plan_id'     => 'plan_' . uniqid(),
            'goal'        => $goal,
            'work_orders' => $workOrders,
            'total_steps' => count($workOrders),
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $this->taskHistory[] = $plan;
        return $plan;
    }

    /**
     * Run weighted consensus evaluation on multiple agent claims.
     */
    public function evaluateConsensus(array $claims): array
    {
        if (empty($claims)) {
            throw new \InvalidArgumentException('Claims array cannot be empty.');
        }

        $totalWeight = 0.0;
        $approvedWeight = 0.0;
        $scores = [];

        foreach ($claims as $claim) {
            $role = $claim['role'] ?? 'reviewer';
            $verdict = !empty($claim['verdict']) && $claim['verdict'] === 'approve';
            $confidence = (float)($claim['confidence'] ?? 0.85);

            $agentWeight = $this->registeredAgents[$role]['weight'] ?? 1.0;
            $effectiveScore = $confidence * $agentWeight;

            $totalWeight += $agentWeight;
            if ($verdict) {
                $approvedWeight += $effectiveScore;
            }

            $scores[] = [
                'role'            => $role,
                'verdict'         => $verdict ? 'approve' : 'reject',
                'confidence'      => $confidence,
                'weighted_score'  => round($effectiveScore, 3)
            ];
        }

        $consensusScore = ($totalWeight > 0) ? round($approvedWeight / $totalWeight, 4) : 0.0;
        $isAccepted = ($consensusScore >= 0.65);

        return [
            'consensus_score' => $consensusScore,
            'is_accepted'     => $isAccepted,
            'quorum_met'      => count($claims) >= 3,
            'agent_evaluations'=> $scores,
            'action_taken'    => $isAccepted ? 'commit_artifact' : 'trigger_peer_arbitration'
        ];
    }

    /**
     * Synthesize unified artifact from multiple verified worker inputs.
     */
    public function synthesizeArtifact(string $taskTitle, array $contributions): array
    {
        $mergedSummary = [];
        $totalTokens = 0;

        foreach ($contributions as $item) {
            $role = $item['role'] ?? 'worker';
            $text = $item['output'] ?? '';
            $mergedSummary[] = "[{$role}]: {$text}";
            $totalTokens += strlen($text);
        }

        return [
            'artifact_id'    => 'art_' . uniqid(),
            'task_title'     => $taskTitle,
            'synthesized_at' => date('Y-m-d H:i:s'),
            'sections_count' => count($mergedSummary),
            'unified_body'   => implode("\n\n", $mergedSummary),
            'verified'       => true,
            'integrity_hash' => hash('sha256', implode('|', $mergedSummary))
        ];
    }
}
