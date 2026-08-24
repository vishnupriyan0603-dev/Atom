<?php

namespace Atom\Agent;

class ObservationEngine
{
    public function generateObservation(AgentStep $step, array $executionResult): string
    {
        if (!empty($executionResult['observation'])) {
            return $executionResult['observation'];
        }

        if (!empty($executionResult['success'])) {
            return "Step #{$step->sequence} ({$step->type}) executed successfully.";
        }

        $errorMsg = $executionResult['error'] ?? 'Unknown execution error';
        return "Step #{$step->sequence} ({$step->type}) failed: {$errorMsg}";
    }
}
