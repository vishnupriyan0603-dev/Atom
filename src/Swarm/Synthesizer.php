<?php

namespace Atom\Swarm;

class Synthesizer
{
    /**
     * Synthesizes verified worker agent outputs into a cohesive final response.
     */
    public function synthesize(string $objective, array $verifiedOutputs): string
    {
        if (empty($verifiedOutputs)) {
            return "Swarm synthesis completed for objective: {$objective}. No worker outputs generated.";
        }

        $summary = "Swarm Synthesis Report for Objective: {$objective}\n\n";
        foreach ($verifiedOutputs as $idx => $item) {
            $role = $item['role'] ?? 'worker';
            $text = $item['output'] ?? '';
            $summary .= "=== Agent #" . ($idx + 1) . " ({$role}) ===\n{$text}\n\n";
        }

        return trim($summary);
    }
}
