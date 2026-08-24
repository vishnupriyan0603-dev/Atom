<?php

namespace Atom\Infrastructure;

/**
 * Incident Event Classifier — Phase 40
 *
 * Classifies runtime infrastructure alerts, outages, exceptions, and latency spikes
 * into standardized severity levels (SEV1_CRITICAL to SEV4_LOW).
 */
class IncidentEventClassifier
{
    public const SEV1_CRITICAL = 'SEV1_CRITICAL'; // Complete outage / data loss
    public const SEV2_MAJOR    = 'SEV2_MAJOR';    // Core feature degraded / high error rate
    public const SEV3_MODERATE = 'SEV3_MODERATE'; // Non-critical degraded
    public const SEV4_LOW      = 'SEV4_LOW';      // Minor / informational anomaly

    /**
     * Classifies an incident event based on telemetry and error signals.
     */
    public function classify(array $event): array
    {
        $message = strtolower($event['message'] ?? '');
        $errorRate = (float)($event['error_rate'] ?? 0.0);
        $latencyMs = (float)($event['latency_ms'] ?? 0.0);
        $subsystem = $event['subsystem'] ?? 'general';

        $severity = self::SEV4_LOW;
        $recommendedRunbook = 'log_investigation';

        if (strpos($message, 'out of memory') !== false || strpos($message, 'fatal') !== false || $errorRate >= 50.0) {
            $severity = self::SEV1_CRITICAL;
            $recommendedRunbook = 'restart_and_scale_workers';
        } elseif (strpos($message, 'database connection refused') !== false || strpos($message, 'deadlock') !== false || $errorRate >= 20.0 || $latencyMs >= 5000.0) {
            $severity = self::SEV2_MAJOR;
            $recommendedRunbook = 'drain_connection_pool';
        } elseif (strpos($message, 'timeout') !== false || strpos($message, 'rate limit') !== false || $errorRate >= 5.0 || $latencyMs >= 1500.0) {
            $severity = self::SEV3_MODERATE;
            $recommendedRunbook = 'flush_cache_and_throttle';
        }

        return [
            'incident_id'        => 'inc_' . bin2hex(random_bytes(4)),
            'severity'           => $severity,
            'subsystem'          => $subsystem,
            'recommended_action' => $recommendedRunbook,
            'timestamp'          => microtime(true),
        ];
    }
}
