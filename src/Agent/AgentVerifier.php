<?php

namespace Atom\Agent;

class AgentVerifier
{
    public function verifyStep(AgentTask $task, AgentStep $step, array $executionResult): array
    {
        if (!empty($executionResult['requires_approval'])) {
            return [
                'status'     => 'waiting_approval',
                'reason'     => 'Execution requires human authorization gate approval',
                'confidence' => 1.0,
            ];
        }

        if (empty($executionResult['success'])) {
            return [
                'status'     => 'replan',
                'reason'     => 'Step execution failed: ' . ($executionResult['error'] ?? 'Execution failure'),
                'confidence' => 0.8,
            ];
        }

        if ($step->type === 'final_response' || $task->currentStep >= $task->maxSteps) {
            return [
                'status'     => 'complete',
                'reason'     => 'Task objective completed cleanly',
                'confidence' => 0.95,
            ];
        }

        return [
            'status'     => 'continue',
            'reason'     => 'Step verified. Proceed to next sequence step',
            'confidence' => 0.9,
        ];
    }
}
