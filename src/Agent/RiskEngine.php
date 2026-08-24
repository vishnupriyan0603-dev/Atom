<?php

namespace Atom\Agent;

class RiskEngine
{
    private static array $riskLevels = [
        'read_file'      => 'low',
        'search_code'    => 'low',
        'php_lint'       => 'low',
        'calculator'     => 'low',
        'weather'        => 'low',
        'retrieval'      => 'low',
        'memory'         => 'low',
        
        'create_file'    => 'medium',
        'patch_file'     => 'high',
        'sql_query'      => 'high',
        'delete_file'    => 'critical',
        'drop_table'     => 'critical',
        'system_exec'    => 'critical',
    ];

    public static function evaluateToolRisk(string $toolName, array $parameters = []): string
    {
        $name = strtolower($toolName);
        $baseRisk = self::$riskLevels[$name] ?? 'medium';

        // Check for elevated risk indicators in parameters
        $paramJson = strtolower(json_encode($parameters));
        if (strpos($paramJson, 'delete') !== false || strpos($paramJson, 'drop') !== false || strpos($paramJson, 'truncate') !== false) {
            return 'critical';
        }

        return $baseRisk;
    }

    public static function requiresHumanApproval(string $riskLevel): bool
    {
        $level = strtolower($riskLevel);
        return ($level === 'high' || $level === 'critical');
    }
}
