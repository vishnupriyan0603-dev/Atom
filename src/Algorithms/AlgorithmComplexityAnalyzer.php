<?php

namespace Atom\Algorithms;

/**
 * Algorithm Complexity Analyzer — Phase 31
 *
 * Inspects source code, loop hierarchies, and recursive call structures
 * to estimate asymptotic Big-O Time and Space complexity ($O(1), O(\log N),
 * O(N), O(N \log N), O(N^2), O(N^3), O(2^N)$) and provides optimization tips.
 */
class AlgorithmComplexityAnalyzer
{
    /**
     * Analyzes code snippet to estimate Big-O time and space complexity.
     */
    public function analyze(string $code): array
    {
        $cleanCode = trim($code);
        if (empty($cleanCode)) {
            throw new \InvalidArgumentException('Source code cannot be empty');
        }

        $timeComplexity = 'O(1)';
        $spaceComplexity = 'O(1)';
        $reasons = [];
        $optimizations = [];

        // Count loop nesting levels
        $lines = explode("\n", $cleanCode);
        $maxNesting = 0;
        $currentNesting = 0;
        $hasLoops = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/\b(for|foreach|while)\b/i', $trimmed)) {
                $hasLoops = true;
                $currentNesting++;
                if ($currentNesting > $maxNesting) {
                    $maxNesting = $currentNesting;
                }
            }
            if (str_contains($trimmed, '}') && $currentNesting > 0) {
                $currentNesting--;
            }
        }

        // Check for logarithmic operations (e.g. binary search, >>= 1, /= 2, log)
        $hasLogarithmic = (bool)preg_match('/(\/=\s*2|>>=\s*1|log\(|binary_search|bisect)/i', $cleanCode);

        // Check for sort operations
        $hasSort = (bool)preg_match('/\b(sort|usort|asort|ksort|array_multisort|quicksort|mergesort)\b/i', $cleanCode);

        // Check for multiple recursive calls (exponential: e.g. return fib(n-1) + fib(n-2))
        $hasExponentialRecursion = (bool)preg_match('/([a-zA-Z0-9_]+)\s*\([^)]*\)\s*[\+\*\/]\s*\1\s*\(/i', $cleanCode);

        // Determine Time Complexity
        if ($hasExponentialRecursion) {
            $timeComplexity = 'O(2^N)';
            $reasons[] = 'Multiple branch recursive invocations detected in call tree (exponential branching).';
            $optimizations[] = 'Apply dynamic programming / memoization to reduce time complexity to O(N).';
        } elseif ($maxNesting >= 3) {
            $timeComplexity = 'O(N^3)';
            $reasons[] = 'Triple nested iteration loops detected.';
            $optimizations[] = 'Refactor inner loops with hash-map lookups or matrix decomposition.';
        } elseif ($maxNesting === 2) {
            if ($hasSort) {
                $timeComplexity = 'O(N^2 \log N)';
                $reasons[] = 'Sorting invoked inside nested loop structure.';
            } else {
                $timeComplexity = 'O(N^2)';
                $reasons[] = 'Double nested iteration loops detected (e.g., nested for/while).';
            }
            $optimizations[] = 'Consider replacing quadratic loop passes with single-pass hash indexing or pre-sorting.';
        } elseif ($maxNesting === 1) {
            if ($hasLogarithmic) {
                $timeComplexity = 'O(N \log N)';
                $reasons[] = 'Single loop combined with logarithmic subdivision.';
            } elseif ($hasSort) {
                $timeComplexity = 'O(N \log N)';
                $reasons[] = 'Comparison-based sorting operation detected.';
            } else {
                $timeComplexity = 'O(N)';
                $reasons[] = 'Single linear traversal loop over input collection.';
            }
        } elseif ($hasLogarithmic) {
            $timeComplexity = 'O(\log N)';
            $reasons[] = 'Logarithmic interval halving / binary search pattern detected.';
        } else {
            $timeComplexity = 'O(1)';
            $reasons[] = 'Constant number of sequential elementary operations (no unbounded loops).';
        }

        // Determine Space Complexity
        if (preg_match('/\[\]|\barray\(|\bnew\s+array|\bvector|\bmatrix/i', $cleanCode)) {
            if ($maxNesting >= 2 && preg_match('/\[\]\s*=|\bappend|\bpush/i', $cleanCode)) {
                $spaceComplexity = 'O(N^2)';
                $reasons[] = '2D matrix or quadratic data allocation allocated in memory.';
            } else {
                $spaceComplexity = 'O(N)';
                $reasons[] = 'Dynamic array/collection allocation proportional to input elements.';
            }
        } else {
            $spaceComplexity = 'O(1)';
            $reasons[] = 'Fixed number of scalar variables used (in-place execution).';
        }

        return [
            'time_complexity'  => $timeComplexity,
            'space_complexity' => $spaceComplexity,
            'max_loop_nesting' => $maxNesting,
            'reasons'          => $reasons,
            'optimizations'    => $optimizations,
            'analysis_summary' => "Time: {$timeComplexity} | Space: {$spaceComplexity}",
        ];
    }
}
