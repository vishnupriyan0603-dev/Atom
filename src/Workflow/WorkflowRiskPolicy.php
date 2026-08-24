<?php

namespace Atom\Workflow;

use Atom\Agent\RiskEngine;

class WorkflowRiskPolicy
{
    private static array $riskHierarchy = [
        'low'      => 1,
        'medium'   => 2,
        'high'     => 3,
        'critical' => 4,
    ];

    /**
     * Effective risk must always be max(tool risk, workflow policy risk).
     */
    public static function resolveEffectiveRisk(string $toolRisk, string $workflowRisk): string
    {
        $tLevel = self::$riskHierarchy[strtolower($toolRisk)] ?? 1;
        $wLevel = self::$riskHierarchy[strtolower($workflowRisk)] ?? 1;

        $maxLevel = max($tLevel, $wLevel);
        return array_search($maxLevel, self::$riskHierarchy, true) ?: 'medium';
    }
}
