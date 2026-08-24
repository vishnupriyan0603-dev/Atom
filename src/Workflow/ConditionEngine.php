<?php

namespace Atom\Workflow;

class ConditionEngine
{
    /**
     * Evaluates a safe conditional expression without eval() or arbitrary code execution.
     * Supports: ==, !=, >, >=, <, <=, contains, startsWith, endsWith, exists, isEmpty
     */
    public function evaluate(string $expression, array $variables = []): bool
    {
        $expression = trim($expression);
        if (empty($expression) || $expression === 'true') {
            return true;
        }
        if ($expression === 'false') {
            return false;
        }

        // Replace template variables like {{variables.score}} with actual values
        $resolvedExpr = VariableResolver::resolveString($expression, $variables);

        // Parse binary comparison expression (e.g. "0.85 >= 0.8" or "'success' == 'success'")
        if (preg_match('/^([^\=!\><\s]+|\"[^\"]*\"|\'[^\']*\')\s*(==|!=|>=|<=|>|<|contains|startsWith|endsWith)\s*([^\=!\><\s]+|\"[^\"]*\"|\'[^\']*\')$/i', $resolvedExpr, $m)) {
            $left  = trim($m[1], "'\" ");
            $op    = strtolower(trim($m[2]));
            $right = trim($m[3], "'\" ");

            switch ($op) {
                case '==':
                    return $left == $right;
                case '!=':
                    return $left != $right;
                case '>':
                    return (float)$left > (float)$right;
                case '>=':
                    return (float)$left >= (float)$right;
                case '<':
                    return (float)$left < (float)$right;
                case '<=':
                    return (float)$left <= (float)$right;
                case 'contains':
                    return stripos($left, $right) !== false;
                case 'startswith':
                    return str_starts_with(strtolower($left), strtolower($right));
                case 'endswith':
                    return str_ends_with(strtolower($left), strtolower($right));
            }
        }

        if (preg_match('/^(exists|isEmpty)\s+([^\s]+)$/i', $resolvedExpr, $m)) {
            $op  = strtolower($m[1]);
            $val = trim($m[2], "'\" ");
            if ($op === 'exists') {
                return !empty($val);
            }
            if ($op === 'isempty') {
                return empty($val);
            }
        }

        return true;
    }
}
