<?php

namespace Atom\Analytics;

/**
 * System Resource Predictor — Phase 38
 *
 * Predicts CPU, RAM, and Disk storage saturation headroom with
 * Time-To-Exhaustion (TTE) trajectory estimation.
 */
class SystemResourcePredictor
{
    /**
     * Estimates resource trajectory and time until 100% saturation.
     *
     * @param array $history Series of historical percentage utilization values [0.0 - 100.0].
     * @param float $saturationLimit Threshold percentage (default 95.0).
     * @return array Growth rate per step, estimated steps to saturation (TTE), and risk level.
     */
    public function predictSaturation(array $history, float $saturationLimit = 95.0): array
    {
        $n = count($history);
        if ($n < 2) {
            return [
                'current_pct'    => end($history) ?: 0.0,
                'growth_rate'    => 0.0,
                'steps_to_limit' => null,
                'risk_level'     => 'UNKNOWN',
            ];
        }

        $history = array_values($history);
        $current = (float)end($history);

        // Simple Ordinary Least Squares (OLS) slope for trend
        $xMean = ($n - 1) / 2.0;
        $yMean = array_sum($history) / $n;

        $num = 0.0;
        $den = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $num += ($i - $xMean) * ($history[$i] - $yMean);
            $den += pow($i - $xMean, 2);
        }

        $slope = $den > 0 ? ($num / $den) : 0.0;

        $tteSteps = null;
        if ($slope > 0.01 && $current < $saturationLimit) {
            $remaining = $saturationLimit - $current;
            $tteSteps = (int)ceil($remaining / $slope);
        } elseif ($current >= $saturationLimit) {
            $tteSteps = 0;
        }

        $risk = 'LOW';
        if ($current >= 90.0 || ($tteSteps !== null && $tteSteps <= 10)) {
            $risk = 'CRITICAL';
        } elseif ($current >= 75.0 || ($tteSteps !== null && $tteSteps <= 30)) {
            $risk = 'WARNING';
        }

        return [
            'current_pct'    => round($current, 2),
            'growth_rate'    => round($slope, 3),
            'steps_to_limit' => $tteSteps,
            'limit_threshold'=> $saturationLimit,
            'risk_level'     => $risk,
        ];
    }
}
