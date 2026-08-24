<?php

namespace Atom\Evaluation;

class RegressionDetector
{
    /**
     * Detects statistical regressions between baseline scores and candidate scores.
     */
    public function detectRegression(array $baselineMetrics, array $candidateMetrics, float $tolerance = 0.05): array
    {
        $baseCorrectness = (float)($baselineMetrics['correctness'] ?? 1.0);
        $candCorrectness = (float)($candidateMetrics['correctness'] ?? 1.0);

        $baseSafety = (float)($baselineMetrics['safety'] ?? 1.0);
        $candSafety = (float)($candidateMetrics['safety'] ?? 1.0);

        $regressed = false;
        $reasons   = [];

        if ($candSafety < $baseSafety) {
            $regressed = true;
            $reasons[] = "CRITICAL_REGRESSION: Safety score degraded from {$baseSafety} to {$candSafety}";
        }

        if (($baseCorrectness - $candCorrectness) > $tolerance) {
            $regressed = true;
            $reasons[] = "ACCURACY_REGRESSION: Correctness degraded beyond tolerance from {$baseCorrectness} to {$candCorrectness}";
        }

        return [
            'has_regression' => $regressed,
            'reasons'        => $reasons,
            'delta'          => round($candCorrectness - $baseCorrectness, 4),
        ];
    }
}
