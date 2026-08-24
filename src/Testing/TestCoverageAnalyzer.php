<?php

namespace Atom\Testing;

/**
 * TestCoverageAnalyzer — Analyzes source code and test files to compute coverage metrics.
 */
class TestCoverageAnalyzer
{
    /**
     * Analyze method coverage and identify untested functions.
     */
    public function analyzeCoverage(string $sourceCode, string $testCode = ''): array
    {
        // Extract public methods from source
        preg_match_all('/public\s+function\s+([a-zA-Z0-9_]+)\s*\(/i', $sourceCode, $sourceMatches);
        $sourceMethods = $sourceMatches[1] ?? [];

        if (empty($sourceMethods)) {
            return [
                'total_methods' => 0,
                'covered_methods' => 0,
                'coverage_percent' => 100.0,
                'tested_methods' => [],
                'untested_methods' => [],
            ];
        }

        $tested = [];
        $untested = [];

        foreach ($sourceMethods as $method) {
            // Check if method is mentioned in test code
            if (!empty($testCode) && (stripos($testCode, $method) !== false || stripos($testCode, 'test' . $method) !== false)) {
                $tested[] = $method;
            } else {
                $untested[] = $method;
            }
        }

        $coverage = count($sourceMethods) > 0 ? round((count($tested) / count($sourceMethods)) * 100, 1) : 100.0;

        return [
            'total_methods' => count($sourceMethods),
            'covered_methods' => count($tested),
            'coverage_percent' => $coverage,
            'tested_methods' => $tested,
            'untested_methods' => $untested,
        ];
    }
}
