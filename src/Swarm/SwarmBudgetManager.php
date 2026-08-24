<?php

namespace Atom\Swarm;

class SwarmBudgetManager
{
    private int $maxAgents;
    private int $maxDepth;
    private int $maxMessages;
    private int $maxAgentTasks;
    private int $maxToolCalls;
    private int $maxTokens;
    private int $maxRuntimeSeconds;

    public function __construct(
        int $maxAgents = 8,
        int $maxDepth = 3,
        int $maxMessages = 100,
        int $maxAgentTasks = 20,
        int $maxToolCalls = 30,
        int $maxTokens = 50000,
        int $maxRuntimeSeconds = 3600
    ) {
        $this->maxAgents         = $maxAgents;
        $this->maxDepth          = $maxDepth;
        $this->maxMessages       = $maxMessages;
        $this->maxAgentTasks     = $maxAgentTasks;
        $this->maxToolCalls       = $maxToolCalls;
        $this->maxTokens          = $maxTokens;
        $this->maxRuntimeSeconds = $maxRuntimeSeconds;
    }

    public function checkBudget(array $stats): array
    {
        $agents     = $stats['agents'] ?? 0;
        $depth      = $stats['depth'] ?? 0;
        $messages   = $stats['messages'] ?? 0;
        $agentTasks = $stats['agent_tasks'] ?? 0;
        $toolCalls  = $stats['tool_calls'] ?? 0;
        $tokens     = $stats['tokens'] ?? 0;
        $runtime    = $stats['runtime_seconds'] ?? 0;

        if ($agents >= $this->maxAgents) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm agent count limit reached (' . $this->maxAgents . ')'];
        }

        if ($depth >= $this->maxDepth) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm depth limit reached (' . $this->maxDepth . ')'];
        }

        if ($messages >= $this->maxMessages) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm message limit reached (' . $this->maxMessages . ')'];
        }

        if ($agentTasks >= $this->maxAgentTasks) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm agent task limit reached (' . $this->maxAgentTasks . ')'];
        }

        if ($toolCalls >= $this->maxToolCalls) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm tool call limit reached (' . $this->maxToolCalls . ')'];
        }

        if ($tokens >= $this->maxTokens) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm token limit reached (' . $this->maxTokens . ')'];
        }

        if ($runtime >= $this->maxRuntimeSeconds) {
            return ['exceeded' => true, 'reason' => 'SWARM_BUDGET_EXCEEDED: Swarm runtime limit reached (' . $this->maxRuntimeSeconds . ' seconds)'];
        }

        return ['exceeded' => false];
    }
}
