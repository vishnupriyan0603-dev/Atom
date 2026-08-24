<?php

namespace Atom\Analytics;

/**
 * Seasonality Decomposer — Phase 38
 *
 * Decomposes time-series into additive components: Trend (T), Seasonality (S), and Residuals (R).
 */
class SeasonalityDecomposer
{
    /**
     * Decomposes series into trend, seasonal, and residual arrays.
     */
    public function decompose(array $series, int $period = 7): array
    {
        $n = count($series);
        if ($n < $period * 2) {
            return [
                'trend'     => $series,
                'seasonal'  => array_fill(0, $n, 0.0),
                'residuals' => array_fill(0, $n, 0.0),
            ];
        }

        $series = array_values($series);

        // 1. Moving average for trend extraction
        $trend = [];
        $half = (int)floor($period / 2);
        for ($i = 0; $i < $n; $i++) {
            if ($i < $half || $i >= $n - $half) {
                $trend[$i] = (float)$series[$i]; // Edge padding
            } else {
                $window = array_slice($series, $i - $half, $period);
                $trend[$i] = round(array_sum($window) / $period, 3);
            }
        }

        // 2. Detrend series and calculate seasonal averages
        $detrended = [];
        for ($i = 0; $i < $n; $i++) {
            $detrended[$i] = $series[$i] - $trend[$i];
        }

        $seasonalPeriods = array_fill(0, $period, 0.0);
        $periodCounts = array_fill(0, $period, 0);
        for ($i = 0; $i < $n; $i++) {
            $pIdx = $i % $period;
            $seasonalPeriods[$pIdx] += $detrended[$i];
            $periodCounts[$pIdx]++;
        }

        for ($p = 0; $p < $period; $p++) {
            $seasonalPeriods[$p] = $periodCounts[$p] > 0 ? ($seasonalPeriods[$p] / $periodCounts[$p]) : 0.0;
        }

        $seasonal = [];
        $residuals = [];
        for ($i = 0; $i < $n; $i++) {
            $s = round($seasonalPeriods[$i % $period], 3);
            $seasonal[$i] = $s;
            $residuals[$i] = round($series[$i] - ($trend[$i] + $s), 3);
        }

        return [
            'trend'     => $trend,
            'seasonal'  => $seasonal,
            'residuals' => $residuals,
            'period'    => $period,
        ];
    }
}
