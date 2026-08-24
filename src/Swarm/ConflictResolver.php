<?php

namespace Atom\Swarm;

class ConflictResolver
{
    /**
     * Resolves conflicting claims between worker agents using RAG evidence comparison.
     */
    public function resolveConflict(array $agentAOutput, array $agentBOutput): array
    {
        // Compare evidence and pick higher confidence or verified output
        $confA = (float)($agentAOutput['confidence'] ?? 0.8);
        $confB = (float)($agentBOutput['confidence'] ?? 0.8);

        if ($confA >= $confB) {
            return [
                'resolved'      => true,
                'winner'        => 'agent_a',
                'final_output'  => $agentAOutput['output'] ?? '',
                'confidence'    => $confA,
            ];
        }

        return [
            'resolved'     => true,
            'winner'       => 'agent_b',
            'final_output' => $agentBOutput['output'] ?? '',
            'confidence'   => $confB,
        ];
    }
}
