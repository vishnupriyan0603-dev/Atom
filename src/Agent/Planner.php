<?php

namespace Atom\Agent;

class Planner
{
    /**
     * Generates a structured execution plan for an objective.
     */
    public function generatePlan(string $objective, array $context = []): array
    {
        $objectiveLower = strtolower($objective);

        $steps = [];

        // Single simple question or conversational objective -> single step response
        if (!preg_match('/\b(research|build|create|deploy|find and|search and|analyze and)\b/i', $objectiveLower)) {
            $steps[] = [
                'sequence'    => 1,
                'type'        => 'reasoning',
                'description' => 'Direct reasoning response for query: ' . $objective,
                'tool'        => null,
                'risk'        => 'low',
            ];
            $steps[] = [
                'sequence'    => 2,
                'type'        => 'final_response',
                'description' => 'Produce final response',
                'tool'        => null,
                'risk'        => 'low',
            ];
            return [
                'objective' => $objective,
                'steps'     => $steps,
            ];
        }

        // Multi-step complex workflow
        $sequence = 1;

        // Step 1: Retrieval/Search
        $steps[] = [
            'sequence'    => $sequence++,
            'type'        => 'retrieval',
            'description' => 'Retrieve relevant project context and documentation for objective',
            'tool'        => null,
            'risk'        => 'low',
        ];

        // Step 2: Tool execution if creating/patching
        if (strpos($objectiveLower, 'create') !== false) {
            $steps[] = [
                'sequence'    => $sequence++,
                'type'        => 'tool_call',
                'description' => 'Execute create file tool',
                'tool'        => 'create_file',
                'risk'        => 'medium',
            ];
        } elseif (strpos($objectiveLower, 'patch') !== false || strpos($objectiveLower, 'modify') !== false) {
            $steps[] = [
                'sequence'    => $sequence++,
                'type'        => 'tool_call',
                'description' => 'Execute patch file tool',
                'tool'        => 'patch_file',
                'risk'        => 'high',
            ];
        }

        // Verification & Final response
        $steps[] = [
            'sequence'    => $sequence++,
            'type'        => 'verification',
            'description' => 'Verify output correctness',
            'tool'        => null,
            'risk'        => 'low',
        ];

        $steps[] = [
            'sequence'    => $sequence,
            'type'        => 'final_response',
            'description' => 'Synthesize and present completed response',
            'tool'        => null,
            'risk'        => 'low',
        ];

        return [
            'objective' => $objective,
            'steps'     => $steps,
        ];
    }
}
