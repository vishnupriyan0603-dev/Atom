<?php

namespace Atom\Analytics;

/**
 * Holt-Winters Exponential Smoothing Forecaster — Phase 38
 *
 * Triple Exponential Smoothing for time-series forecasting with level (alpha),
 * trend (beta), seasonality (gamma), and statistical prediction intervals.
 */
class HoltWintersForecaster
{
    private float $alpha;
    private float $beta;
    private float $gamma;
    private int $seasonLength;

    public function __construct(float $alpha = 0.3, float $beta = 0.1, float $gamma = 0.1, int $seasonLength = 7)
    {
        $this->alpha = max(0.01, min(0.99, $alpha));
        $this->beta = max(0.01, min(0.99, $beta));
        $this->gamma = max(0.01, min(0.99, $gamma));
        $this->seasonLength = max(2, $seasonLength);
    }

    /**
     * Fits Holt-Winters model on series and predicts h future steps.
     *
     * @param array $series Numeric time-series values.
     * @param int $horizon Number of periods ahead to forecast.
     * @return array Fitted values, forecast points, confidence bounds, and RMSE.
     */
    public function forecast(array $series, int $horizon = 5): array
    {
        $n = count($series);
        if ($n < $this->seasonLength * 2) {
            // Fallback for short series: Simple linear trend projection
            return $this->fallbackLinearForecast($series, $horizon);
        }

        $series = array_values($series);
        $L = $this->seasonLength;

        // Initialize level and trend
        $level = array_sum(array_slice($series, 0, $L)) / $L;
        $trend = (array_sum(array_slice($series, $L, $L)) - array_sum(array_slice($series, 0, $L))) / ($L * $L);

        // Initialize seasonal indices
        $seasonal = [];
        for ($i = 0; $i < $L; $i++) {
            $seasonal[$i] = $series[$i] - $level;
        }

        $fitted = [];
        $residuals = [];

        // Forward smoothing pass
        for ($t = 0; $t < $n; $t++) {
            $val = (float)$series[$t];
            $prevLevel = $level;
            $prevTrend = $trend;
            $seasonIdx = $t % $L;

            // Fitted point
            $fit = ($prevLevel + $prevTrend) + $seasonal[$seasonIdx];
            $fitted[] = round($fit, 3);
            $residuals[] = $val - $fit;

            // Update level, trend, seasonal
            $level = $this->alpha * ($val - $seasonal[$seasonIdx]) + (1.0 - $this->alpha) * ($prevLevel + $prevTrend);
            $trend = $this->beta * ($level - $prevLevel) + (1.0 - $this->beta) * $prevTrend;
            $seasonal[$seasonIdx] = $this->gamma * ($val - $level) + (1.0 - $this->gamma) * $seasonal[$seasonIdx];
        }

        // Calculate Root Mean Squared Error (RMSE)
        $mse = array_sum(array_map(fn($r) => $r * $r, $residuals)) / count($residuals);
        $rmse = sqrt($mse);

        // Generate future forecast
        $predictions = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $seasonIdx = ($n + $h - 1) % $L;
            $pred = ($level + ($h * $trend)) + $seasonal[$seasonIdx];
            $margin = 1.96 * $rmse * sqrt(1 + ($h * 0.1));

            $predictions[] = [
                'step'        => $h,
                'forecast'    => round($pred, 3),
                'lower_bound' => round($pred - $margin, 3),
                'upper_bound' => round($pred + $margin, 3),
            ];
        }

        return [
            'model'       => 'HOLT_WINTERS_TRIPLE_EXPONENTIAL',
            'rmse'        => round($rmse, 3),
            'last_level'  => round($level, 3),
            'last_trend'  => round($trend, 3),
            'fitted'      => $fitted,
            'predictions' => $predictions,
        ];
    }

    private function fallbackLinearForecast(array $series, int $horizon): array
    {
        $n = count($series);
        if ($n === 0) {
            return ['model' => 'FALLBACK_EMPTY', 'rmse' => 0.0, 'predictions' => []];
        }

        $last = end($series);
        $predictions = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $predictions[] = [
                'step'        => $h,
                'forecast'    => (float)$last,
                'lower_bound' => (float)$last,
                'upper_bound' => (float)$last,
            ];
        }

        return [
            'model'       => 'FALLBACK_PERSISTENCE',
            'rmse'        => 0.0,
            'last_level'  => (float)$last,
            'last_trend'  => 0.0,
            'fitted'      => $series,
            'predictions' => $predictions,
        ];
    }
}
