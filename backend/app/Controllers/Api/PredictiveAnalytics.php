<?php

namespace App\Controllers\Api;

use Atom\Analytics\HoltWintersForecaster;
use Atom\Analytics\SlidingWindowAnomalyDetector;
use Atom\Analytics\SystemResourcePredictor;
use Atom\Analytics\SeasonalityDecomposer;

/**
 * Time-Series Predictive Analytics & Anomaly Detection API Controller — Phase 38
 *
 * Endpoints:
 * - POST /api/v1/predictive/forecast   — Run Holt-Winters triple exponential forecast
 * - POST /api/v1/predictive/anomalies  — Detect statistical Z-score anomalies
 * - POST /api/v1/predictive/saturation — Estimate resource saturation and Time-To-Exhaustion (TTE)
 * - POST /api/v1/predictive/decompose  — Decompose time-series into trend, seasonal, and residuals
 */
class PredictiveAnalytics extends BaseApiController
{
    private static ?HoltWintersForecaster $forecasterInstance = null;
    private static ?SlidingWindowAnomalyDetector $anomalyInstance = null;
    private static ?SystemResourcePredictor $resourceInstance = null;
    private static ?SeasonalityDecomposer $decomposerInstance = null;

    private function getForecaster(): HoltWintersForecaster
    {
        if (self::$forecasterInstance === null) {
            self::$forecasterInstance = new HoltWintersForecaster();
        }
        return self::$forecasterInstance;
    }

    private function getAnomalyDetector(): SlidingWindowAnomalyDetector
    {
        if (self::$anomalyInstance === null) {
            self::$anomalyInstance = new SlidingWindowAnomalyDetector();
        }
        return self::$anomalyInstance;
    }

    private function getResourcePredictor(): SystemResourcePredictor
    {
        if (self::$resourceInstance === null) {
            self::$resourceInstance = new SystemResourcePredictor();
        }
        return self::$resourceInstance;
    }

    private function getDecomposer(): SeasonalityDecomposer
    {
        if (self::$decomposerInstance === null) {
            self::$decomposerInstance = new SeasonalityDecomposer();
        }
        return self::$decomposerInstance;
    }

    /**
     * POST /api/v1/predictive/forecast
     */
    public function forecast()
    {
        $json = $this->request->getJSON(true) ?? [];
        $series = $json['series'] ?? [10, 12, 15, 14, 18, 22, 25, 24, 28, 32, 35, 36, 40, 45];
        $horizon = (int)($json['horizon'] ?? 5);

        $result = $this->getForecaster()->forecast($series, $horizon);
        return $this->respondSuccess($result, 'Time-series forecast generated');
    }

    /**
     * POST /api/v1/predictive/anomalies
     */
    public function anomalies()
    {
        $json = $this->request->getJSON(true) ?? [];
        $series = $json['series'] ?? [20, 22, 21, 23, 22, 95, 21, 20, 22, 21, 24, 22]; // 95 is spike

        $result = $this->getAnomalyDetector()->detect($series);
        return $this->respondSuccess($result, 'Anomalies detected');
    }

    /**
     * POST /api/v1/predictive/saturation
     */
    public function saturation()
    {
        $json = $this->request->getJSON(true) ?? [];
        $history = $json['history'] ?? [50.0, 52.0, 55.0, 58.0, 62.0, 66.0, 70.0, 75.0];
        $limit = (float)($json['limit'] ?? 95.0);

        $result = $this->getResourcePredictor()->predictSaturation($history, $limit);
        return $this->respondSuccess($result, 'Resource saturation forecast generated');
    }

    /**
     * POST /api/v1/predictive/decompose
     */
    public function decompose()
    {
        $json = $this->request->getJSON(true) ?? [];
        $series = $json['series'] ?? [10, 12, 15, 14, 18, 22, 25, 24, 28, 32, 35, 36, 40, 45];
        $period = (int)($json['period'] ?? 7);

        $result = $this->getDecomposer()->decompose($series, $period);
        return $this->respondSuccess($result, 'Time-series decomposed');
    }
}
