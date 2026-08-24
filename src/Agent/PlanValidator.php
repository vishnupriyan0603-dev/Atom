<?php

namespace Atom\Agent;

use Atom\Tools\ToolManager;

class PlanValidator
{
    private array $allowedStepTypes = [
        'reasoning',
        'tool_call',
        'retrieval',
        'memory',
        'verification',
        'human_approval',
        'final_response',
    ];

    private array $forbiddenOperations = [
        'eval(',
        'exec(',
        'passthru(',
        'shell_exec(',
        'system(',
        'rm -rf /',
        'drop database',
        'disable_security',
        'bypass_approval',
    ];


    public function validatePlan(array $plan, AgentTask $task, ?ToolManager $toolManager = null): array
    {
        if (!isset($plan['steps']) || !is_array($plan['steps']) || empty($plan['steps'])) {
            return [
                'valid' => false,
                'error' => 'PLAN_INVALID: Plan structure must contain a non-empty array of steps',
            ];
        }

        if (count($plan['steps']) > $task->maxSteps) {
            return [
                'valid' => false,
                'error' => 'PLAN_INVALID: Plan exceeds maximum step budget (' . count($plan['steps']) . ' > ' . $task->maxSteps . ')',
            ];
        }

        $registeredTools = $toolManager ? array_keys($toolManager->getTools()) : ['read_file', 'search_code', 'php_lint', 'create_file', 'patch_file'];

        foreach ($plan['steps'] as $idx => $step) {
            $seq = $step['sequence'] ?? ($idx + 1);
            $type = strtolower($step['type'] ?? '');

            if (!in_array($type, $this->allowedStepTypes, true)) {
                return [
                    'valid' => false,
                    'error' => "PLAN_INVALID: Step #{$seq} contains unsupported step type '{$type}'",
                ];
            }

            // Verify tool existence for tool_call steps
            if ($type === 'tool_call') {
                $tool = $step['tool'] ?? null;
                if (empty($tool) || !in_array($tool, $registeredTools, true)) {
                    return [
                        'valid' => false,
                        'error' => "PLAN_INVALID: Step #{$seq} references nonexistent or unregistered tool '{$tool}'",
                    ];
                }
            }

            // Check for forbidden strings in description or parameters
            $descJson = strtolower(json_encode($step));
            foreach ($this->forbiddenOperations as $forbidden) {
                if (strpos($descJson, $forbidden) !== false) {
                    return [
                        'valid' => false,
                        'error' => "PLAN_INVALID: Step #{$seq} contains forbidden operation '{$forbidden}'",
                    ];
                }
            }
        }

        return ['valid' => true];
    }
}
