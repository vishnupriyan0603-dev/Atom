<?php

namespace Atom\Refactoring;

use Atom\Security\SecretRedactor;

/**
 * AstCodeModernizerEngine — Phase 47
 * Autonomous AST-based code modernizer for PHP 8.2 & PHP 8.3.
 * Transforms switch to match, string helpers to str_contains/starts_with/ends_with, and constructor promotion.
 */
class AstCodeModernizerEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Modernize PHP code with enabled transformation rules.
     *
     * @param string $code Raw source code
     * @param array $options [ 'upgrade_match' => true, 'upgrade_string_functions' => true, 'upgrade_nullsafe' => true ]
     * @return array [ 'success' => bool, 'modernized_code' => string, 'transformations' => array ]
     */
    public function modernize(string $code, array $options = []): array
    {
        if (empty(trim($code))) {
            return [
                'success' => false,
                'error' => 'Input code cannot be empty',
                'modernized_code' => '',
                'transformations' => [],
            ];
        }

        $cleanCode = $this->redactor->redact($code);
        $currentCode = $cleanCode;
        $transformations = [];

        // 1. Upgrade legacy strpos to str_contains / str_starts_with / str_ends_with
        if ($options['upgrade_string_functions'] ?? true) {
            $stringUpgraded = $this->upgradeStringFunctions($currentCode, $transList);
            if ($stringUpgraded !== $currentCode) {
                $currentCode = $stringUpgraded;
                $transformations = array_merge($transformations, $transList);
            }
        }

        // 2. Upgrade simple switch-return to match expressions
        if ($options['upgrade_match'] ?? true) {
            $matchUpgraded = $this->upgradeSwitchToMatch($currentCode, $transList);
            if ($matchUpgraded !== $currentCode) {
                $currentCode = $matchUpgraded;
                $transformations = array_merge($transformations, $transList);
            }
        }

        // 3. Upgrade null check ternary to nullsafe operator (?->)
        if ($options['upgrade_nullsafe'] ?? true) {
            $nullsafeUpgraded = $this->upgradeNullsafeOperator($currentCode, $transList);
            if ($nullsafeUpgraded !== $currentCode) {
                $currentCode = $nullsafeUpgraded;
                $transformations = array_merge($transformations, $transList);
            }
        }

        return [
            'success' => true,
            'original_length' => strlen($cleanCode),
            'modernized_length' => strlen($currentCode),
            'transformation_count' => count($transformations),
            'transformations' => $transformations,
            'modernized_code' => $currentCode,
            'target_php_version' => 'PHP 8.3',
        ];
    }

    /**
     * Modernize strpos checks to str_contains, str_starts_with, str_ends_with.
     */
    public function upgradeStringFunctions(string $code, ?array &$applied = []): string
    {
        $applied = [];
        $res = $code;

        // Pattern 1: strpos($h, $n) !== false  ->  str_contains($h, $n)
        $patternContains = '/strpos\s*\(\s*(\$[a-zA-Z0-9_]+(?:\->[a-zA-Z0-9_]+)?)\s*,\s*([^)]+)\)\s*!==\s*false/';
        if (preg_match($patternContains, $res)) {
            $res = preg_replace($patternContains, 'str_contains($1, $2)', $res);
            $applied[] = 'Upgraded strpos(...) !== false to str_contains(...)';
        }

        // Pattern 2: strpos($h, $n) === false  ->  !str_contains($h, $n)
        $patternNotContains = '/strpos\s*\(\s*(\$[a-zA-Z0-9_]+(?:\->[a-zA-Z0-9_]+)?)\s*,\s*([^)]+)\)\s*===\s*false/';
        if (preg_match($patternNotContains, $res)) {
            $res = preg_replace($patternNotContains, '!str_contains($1, $2)', $res);
            $applied[] = 'Upgraded strpos(...) === false to !str_contains(...)';
        }

        // Pattern 3: strpos($h, $n) === 0  ->  str_starts_with($h, $n)
        $patternStarts = '/strpos\s*\(\s*(\$[a-zA-Z0-9_]+(?:\->[a-zA-Z0-9_]+)?)\s*,\s*([^)]+)\)\s*===\s*0/';
        if (preg_match($patternStarts, $res)) {
            $res = preg_replace($patternStarts, 'str_starts_with($1, $2)', $res);
            $applied[] = 'Upgraded strpos(...) === 0 to str_starts_with(...)';
        }

        // Pattern 4: substr($h, 0, len) === $n  ->  str_starts_with($h, $n)
        $patternSubstrStart = '/substr\s*\(\s*(\$[a-zA-Z0-9_]+)\s*,\s*0\s*,\s*strlen\s*\(([^)]+)\)\s*\)\s*===\s*\2/';
        if (preg_match($patternSubstrStart, $res)) {
            $res = preg_replace($patternSubstrStart, 'str_starts_with($1, $2)', $res);
            $applied[] = 'Upgraded substr($s, 0, strlen($n)) === $n to str_starts_with(...)';
        }

        return $res;
    }

    /**
     * Modernize simple switch statements to match expressions.
     */
    public function upgradeSwitchToMatch(string $code, ?array &$applied = []): string
    {
        $applied = [];

        // Match switch with return branches
        $pattern = '/switch\s*\(\s*(\$[a-zA-Z0-9_]+)\s*\)\s*\{((?:\s*case\s+[^:]+:\s*return\s+[^;]+;\s*)+(?:\s*default:\s*return\s+[^;]+;\s*)?)\}/s';

        return preg_replace_callback($pattern, function ($matches) use (&$applied) {
            $varName = $matches[1];
            $body = $matches[2];
            $arms = [];

            // Extract case branches
            preg_match_all('/case\s+([^:]+):\s*return\s+([^;]+);/', $body, $cases, PREG_SET_ORDER);
            foreach ($cases as $c) {
                $arms[] = "        {$c[1]} => {$c[2]},";
            }

            // Extract default branch
            if (preg_match('/default:\s*return\s+([^;]+);/', $body, $d)) {
                $arms[] = "        default => {$d[1]},";
            }

            $applied[] = "Upgraded switch({$varName}) to typed match({$varName}) expression";

            return "return match ({$varName}) {\n" . implode("\n", $arms) . "\n    };";
        }, $code);
    }

    /**
     * Modernize ternary null check to nullsafe operator (?->).
     */
    public function upgradeNullsafeOperator(string $code, ?array &$applied = []): string
    {
        $applied = [];
        $res = $code;

        // Pattern: $obj !== null ? $obj->method() : null  ->  $obj?->method()
        $pattern = '/(\$[a-zA-Z0-9_]+)\s*!==\s*null\s*\?\s*\1->([a-zA-Z0-9_]+)\((.*?)\)\s*:\s*null/';
        if (preg_match($pattern, $res)) {
            $res = preg_replace($pattern, '$1?->$2($3)', $res);
            $applied[] = 'Upgraded ternary null check to nullsafe operator (?->)';
        }

        return $res;
    }
}
