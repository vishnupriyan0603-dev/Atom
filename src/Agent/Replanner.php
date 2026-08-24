<?php

namespace Atom\Agent;

class Replanner
{
    public function replan(AgentTask $task, array $completedSteps, string $failureReason): array
    {
        $newSequence = count($completedSteps) + 1;

        $revisedSteps = [];
        foreach ($completedSteps as $step) {
            $revisedSteps[] = is_array($step) ? $step : $step->toArray();
        }

        // Add fallback step
        $revisedSteps[] = [
            'sequence'    => $newSequence++,
            'type'        => 'reasoning',
            'description' => 'Fallback step due to replan: ' . $failureReason,
            'tool'        => null,
            'risk'        => 'low',
        ];

        $revisedSteps[] = [
            'sequence'    => $newSequence,
            'type'        => 'final_response',
            'description' => 'Produce final response after replan',
            'tool'        => null,
            'risk'        => 'low',
        ];

        return [
            'objective' => $task->objective,
            'replan_reason' => $failureReason,
            'steps'     => $revisedSteps,
        ];
    }
}
