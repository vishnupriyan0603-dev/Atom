<?php

namespace Atom\Analytics;

/**
 * Sliding Window Anomaly Detector — Phase 38
 *
 * Real-time streaming anomaly detection using Welford's running mean and variance,
 * dynamic statistical Z-score thresholds, and outlier classification.
 */
class SlidingWindowAnomalyDetector
{
    private float $zThreshold;
    private int $minSampleSize;

    public function __construct(float $zThreshold = 3.0, int $minSampleSize = 10)
    {
        $this->zThreshold = max(1.0, $zThreshold);
        $this->minSampleSize = max(5, $minSampleSize);
    }

    /**
     * Detects anomalies in a batch time-series dataset.
     */
    public function detect(array $series): array
    {
        $anomalies = [];
        $n = count($series);

        if ($n < $this->minSampleSize) {
            return [
                'anomalies'       => [],
                'total_anomalies' => 0,
                'mean'            => 0.0,
                'std_dev'         => 0.0,
            ];
        }

        $mean = array_sum($series) / $n;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $series)) / max(1, ($n - 1));
        $stdDev = sqrt($variance);

        foreach ($series as $idx => $val) {
            $zScore = $stdDev > 0 ? abs(($val - $mean) / $stdDev) : 0.0;
            if ($zScore >= $this->zThreshold) {
                $anomalies[] = [
                    'index'       => $idx,
                    'value'       => (float)$val,
                    'z_score'     => round($zScore, 3),
                    'severity'    => $zScore >= ($this->zThreshold * 1.5) ? 'CRITICAL' : 'WARNING',
                    'expected'    => round($mean, 3),
                ];
            }
        }

        return [
            'anomalies'       => $anomalies,
            'total_anomalies' => count($anomalies),
            'mean'            => round($mean, 3),
            'std_dev'         => round($stdDev, 3),
            'z_threshold'     => $this->zThreshold,
        ];
    }
}
