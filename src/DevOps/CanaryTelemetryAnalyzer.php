<?php

namespace Atom\DevOps;

class CanaryTelemetryAnalyzer
{
    /**
     * Score and compare Canary deployment telemetry against Baseline.
     */
    public function analyzeCanary(array $baselineMetrics, array $canaryMetrics): array
    {
        $baseErrRate = (float)($baselineMetrics['error_rate'] ?? 0.0);
        $canaryErrRate = (float)($canaryMetrics['error_rate'] ?? 0.0);

        $baseP95 = (float)($baselineMetrics['p95_latency_ms'] ?? 10.0);
        $canaryP95 = (float)($canaryMetrics['p95_latency_ms'] ?? 10.0);

        $errDelta = $canaryErrRate - $baseErrRate;
        $latDeltaPercent = $baseP95 > 0 ? (($canaryP95 - $baseP95) / $baseP95) * 100.0 : 0.0;

        // Health Scoring: 100 max
        $score = 100.0;
        if ($errDelta > 0) {
            $score -= min(60.0, $errDelta * 10.0);
        }
        if ($latDeltaPercent > 10.0) {
            $score -= min(40.0, ($latDeltaPercent - 10.0) * 1.5);
        }
        $score = max(0.0, round($score, 1));

        $decision = 'PROMOTE';
        $reason = 'Canary metrics match or exceed baseline performance.';

        if ($score < 60.0 || $errDelta > 5.0) {
            $decision = 'ROLLBACK';
            $reason = "Canary degraded: Error delta +{$errDelta}% or Latency drift +{$latDeltaPercent}%.";
        } elseif ($score < 85.0) {
            $decision = 'HOLD';
            $reason = 'Canary metrics exhibit minor drift; continue observation.';
        }

        return [
            'health_score' => $score,
            'decision' => $decision,
            'reason' => $reason,
            'baseline' => $baselineMetrics,
            'canary' => $canaryMetrics,
            'metrics_delta' => [
                'error_rate_delta' => round($errDelta, 2),
                'latency_drift_percent' => round($latDeltaPercent, 2),
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}