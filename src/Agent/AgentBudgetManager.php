<?php

namespace Atom\Agent;

class AgentBudgetManager
{
    /**
     * Verifies if executing another step/action would violate task budgets.
     */
    public function checkBudget(AgentTask $task, array $stats = []): array
    {
        $currentStep = $task->currentStep;
        $toolCalls   = $stats['tool_calls'] ?? 0;
        $tokens      = $stats['tokens'] ?? 0;
        $runtime     = $stats['runtime_seconds'] ?? 0;
        $cost        = $stats['cost'] ?? 0.0;
        $replans     = $stats['replans'] ?? 0;

        if ($currentStep >= $task->maxSteps) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Step count limit reached (' . $task->maxSteps . ' steps)',
            ];
        }

        if ($toolCalls >= $task->maxToolCalls) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Tool call limit reached (' . $task->maxToolCalls . ' calls)',
            ];
        }

        if ($tokens >= $task->maxTokens) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Token limit reached (' . $task->maxTokens . ' tokens)',
            ];
        }

        if ($runtime >= $task->maxRuntimeSeconds) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Runtime limit reached (' . $task->maxRuntimeSeconds . ' seconds)',
            ];
        }

        if ($cost >= $task->maxCost) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Financial cost limit reached ($' . $task->maxCost . ')',
            ];
        }

        if ($replans >= $task->maxReplans) {
            return [
                'exceeded' => true,
                'reason'   => 'BUDGET_EXCEEDED: Replan limit reached (' . $task->maxReplans . ' replans)',
            ];
        }

        return ['exceeded' => false];
    }
}
