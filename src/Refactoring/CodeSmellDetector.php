<?php

namespace Atom\Refactoring;

use Atom\Security\SecretRedactor;

/**
 * Code Smell Detector — Phase 35
 *
 * Static analysis engine identifying code complexity anti-patterns,
 * cyclomatic complexity, deep nesting, god classes, and computing Maintainability Index (MI).
 */
class CodeSmellDetector
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Scans source code and returns list of detected code smells with severity.
     *
     * @param string $sourceCode PHP source code string.
     * @return array Detected smells, complexity metrics, and maintainability index.
     */
    public function scan(string $sourceCode): array
    {
        $clean = $this->redactor->redact($sourceCode);
        if (trim($clean) === '') {
            return [
                'smells'                 => [],
                'total_smells'           => 0,
                'cyclomatic_complexity'  => 1,
                'loc'                    => 0,
                'maintainability_index'  => 100.0,
                'refactoring_urgency'    => 'LOW',
            ];
        }

        $lines = explode("\n", $clean);
        $loc = count($lines);
        $smells = [];

        // 1. Cyclomatic Complexity calculation
        $complexity = $this->calculateCyclomaticComplexity($clean);
        if ($complexity > 10) {
            $smells[] = [
                'type'        => 'HIGH_CYCLOMATIC_COMPLEXITY',
                'severity'    => $complexity > 20 ? 'CRITICAL' : 'WARNING',
                'description' => "High cyclomatic complexity ($complexity > 10). Too many branching decisions.",
                'metric'      => $complexity,
            ];
        }

        // 2. Long Method detection
        $methods = $this->extractMethods($clean);
        foreach ($methods as $m) {
            if ($m['length'] > 50) {
                $smells[] = [
                    'type'        => 'LONG_METHOD',
                    'severity'    => $m['length'] > 100 ? 'CRITICAL' : 'WARNING',
                    'description' => "Method '{$m['name']}' is {$m['length']} lines long (> 50 lines). Consider Extract Method.",
                    'method'      => $m['name'],
                    'metric'      => $m['length'],
                ];
            }
        }

        // 3. God Class detection
        if (count($methods) > 20 || $loc > 500) {
            $smells[] = [
                'type'        => 'GOD_CLASS',
                'severity'    => 'CRITICAL',
                'description' => "Class has " . count($methods) . " methods and $loc LOC. Violates Single Responsibility Principle.",
                'metric'      => count($methods),
            ];
        }

        // 4. Deep Nesting detection
        $maxNesting = $this->calculateMaxNesting($lines);
        if ($maxNesting > 4) {
            $smells[] = [
                'type'        => 'DEEP_NESTING',
                'severity'    => 'WARNING',
                'description' => "Maximum nesting depth of $maxNesting (> 4). Consider early returns or guard clauses.",
                'metric'      => $maxNesting,
            ];
        }

        // Maintainability Index: 171 - 5.2*ln(Halstead Volume) - 0.23*Complexity - 16.2*ln(LOC)
        $vol = max(1.0, (float)$loc * 4.5);
        $mi = 171.0 - (5.2 * log($vol)) - (0.23 * $complexity) - (16.2 * log(max(1.0, (float)$loc)));
        $mi = max(0.0, min(100.0, round($mi, 1)));

        $urgency = 'LOW';
        if ($mi < 40.0 || count($smells) >= 3) {
            $urgency = 'HIGH';
        } elseif ($mi < 65.0 || count($smells) >= 1) {
            $urgency = 'MEDIUM';
        }

        return [
            'smells'                => $smells,
            'total_smells'          => count($smells),
            'cyclomatic_complexity' => $complexity,
            'loc'                   => $loc,
            'method_count'          => count($methods),
            'maintainability_index' => $mi,
            'refactoring_urgency'   => $urgency,
        ];
    }

    private function calculateCyclomaticComplexity(string $code): int
    {
        $complexity = 1;
        $patterns = [
            '/\bif\s*\(/i',
            '/\belseif\s*\(/i',
            '/\bfor\s*\(/i',
            '/\bforeach\s*\(/i',
            '/\bwhile\s*\(/i',
            '/\bcatch\s*\(/i',
            '/\bcase\b/i',
            '/&&/',
            '/\|\|/',
            '/\?\?/',
            '/\?[^:]+:/',
        ];

        foreach ($patterns as $pattern) {
            $complexity += preg_match_all($pattern, $code);
        }

        return $complexity;
    }

    private function extractMethods(string $code): array
    {
        $methods = [];
        if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*\{/i', $code, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $idx => $match) {
                $name = $match[0];
                $offset = $matches[0][$idx][1];
                $sub = substr($code, $offset);
                $len = substr_count($sub, "\n");
                $methods[] = ['name' => $name, 'length' => min(60, $len)];
            }
        }
        return $methods;
    }

    private function calculateMaxNesting(array $lines): int
    {
        $max = 0;
        $current = 0;
        foreach ($lines as $line) {
            $current += substr_count($line, '{');
            $current -= substr_count($line, '}');
            if ($current > $max) {
                $max = $current;
            }
        }
        return max(0, $max);
    }
}
