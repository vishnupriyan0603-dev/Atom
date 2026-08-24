<?php

namespace Atom\Workflow;

class WorkflowBudgetManager
{
    private int $maxNodesExecuted;
    private int $maxAgentTasks;
    private int $maxToolCalls;
    private int $maxLlmCalls;
    private int $maxTokens;
    private int $maxRuntimeSeconds;

    public function __construct(
        int $maxNodesExecuted = 100,
        int $maxAgentTasks = 10,
        int $maxToolCalls = 20,
        int $maxLlmCalls = 30,
        int $maxTokens = 30000,
        int $maxRuntimeSeconds = 3600
    ) {
        $this->maxNodesExecuted   = $maxNodesExecuted;
        $this->maxAgentTasks     = $maxAgentTasks;
        $this->maxToolCalls       = $maxToolCalls;
        $this->maxLlmCalls        = $maxLlmCalls;
        $this->maxTokens          = $maxTokens;
        $this->maxRuntimeSeconds = $maxRuntimeSeconds;
    }

    public function checkBudget(array $executionStats): array
    {
        $nodesExecuted = $executionStats['nodes_executed'] ?? 0;
        $agentTasks    = $executionStats['agent_tasks'] ?? 0;
        $toolCalls     = $executionStats['tool_calls'] ?? 0;
        $llmCalls      = $executionStats['llm_calls'] ?? 0;
        $tokens        = $executionStats['tokens'] ?? 0;
        $runtime       = $executionStats['runtime_seconds'] ?? 0;

        if ($nodesExecuted >= $this->maxNodesExecuted) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: Node execution limit reached (' . $this->maxNodesExecuted . ')'];
        }

        if ($agentTasks >= $this->maxAgentTasks) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: Agent task limit reached (' . $this->maxAgentTasks . ')'];
        }

        if ($toolCalls >= $this->maxToolCalls) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: Tool call limit reached (' . $this->maxToolCalls . ')'];
        }

        if ($llmCalls >= $this->maxLlmCalls) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: LLM call limit reached (' . $this->maxLlmCalls . ')'];
        }

        if ($tokens >= $this->maxTokens) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: Token limit reached (' . $this->maxTokens . ')'];
        }

        if ($runtime >= $this->maxRuntimeSeconds) {
            return ['exceeded' => true, 'reason' => 'WORKFLOW_BUDGET_EXCEEDED: Runtime limit reached (' . $this->maxRuntimeSeconds . ' seconds)'];
        }

        return ['exceeded' => false];
    }
}
