<?php

namespace Atom\Telemetry;

use Atom\Security\SecretRedactor;

/**
 * LatencyHeatmapEngine — Phase 67
 * Autonomous API latency heatmap matrix and SLA breach detection engine.
 */
class LatencyHeatmapEngine
{
    private SecretRedactor $redactor;
    private array $buckets = [
        'p0_fast' => 0,    // < 10ms
        'p1_good' => 0,    // 10 - 50ms
        'p2_warm' => 0,    // 50 - 200ms
        'p3_breach' => 0,  // > 200ms
    ];
    private array $subsystemLatencies = [];
    private float $slaThresholdMs = 50.0;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleHeatmap();
    }

    /**
     * Record endpoint latency in milliseconds.
     */
    public function recordLatency(string $subsystem, float $durationMs): array
    {
        $cleanSubsystem = basename($this->redactor->redact($subsystem));
        $ms = max(0.01, round($durationMs, 2));

        if ($ms < 10.0) {
            $bucket = 'p0_fast';
        } elseif ($ms <= 50.0) {
            $bucket = 'p1_good';
        } elseif ($ms <= 200.0) {
            $bucket = 'p2_warm';
        } else {
            $bucket = 'p3_breach';
        }

        $this->buckets[$bucket]++;

        if (!isset($this->subsystemLatencies[$cleanSubsystem])) {
            $this->subsystemLatencies[$cleanSubsystem] = [
                'subsystem' => $cleanSubsystem,
                'requests_count' => 0,
                'total_ms' => 0.0,
                'min_ms' => $ms,
                'max_ms' => $ms,
                'sla_breaches' => 0,
            ];
        }

        $entry = &$this->subsystemLatencies[$cleanSubsystem];
        $entry['requests_count']++;
        $entry['total_ms'] += $ms;
        $entry['min_ms'] = min($entry['min_ms'], $ms);
        $entry['max_ms'] = max($entry['max_ms'], $ms);
        if ($ms > $this->slaThresholdMs) {
            $entry['sla_breaches']++;
        }

        return [
            'success' => true,
            'subsystem' => $cleanSubsystem,
            'duration_ms' => $ms,
            'bucket' => $bucket,
            'is_sla_breach' => $ms > $this->slaThresholdMs,
        ];
    }

    /**
     * Get heatmap matrix and SLA compliance score across all subsystems.
     */
    public function getHeatmapMatrix(): array
    {
        $totalRequests = array_sum($this->buckets);
        $totalBreaches = $this->buckets['p3_breach'];
        $slaCompliancePct = $totalRequests > 0
            ? round((($totalRequests - $totalBreaches) / $totalRequests) * 100, 2)
            : 100.0;

        $matrix = [];
        foreach ($this->subsystemLatencies as $name => $stats) {
            $count = $stats['requests_count'];
            $avg = $count > 0 ? round($stats['total_ms'] / $count, 2) : 0.0;
            $matrix[] = [
                'subsystem' => $name,
                'requests_count' => $count,
                'avg_ms' => $avg,
                'min_ms' => $stats['min_ms'],
                'max_ms' => $stats['max_ms'],
                'sla_breaches' => $stats['sla_breaches'],
                'status' => $stats['sla_breaches'] === 0 ? 'OPTIMAL' : ($stats['sla_breaches'] < 3 ? 'WARNING' : 'CRITICAL_BREACH'),
            ];
        }

        return [
            'total_requests' => $totalRequests,
            'sla_threshold_ms' => $this->slaThresholdMs,
            'sla_compliance_pct' => $slaCompliancePct,
            'buckets' => $this->buckets,
            'matrix' => $matrix,
        ];
    }

    private function seedSampleHeatmap(): void
    {
        $this->recordLatency('GatewayCrossbar', 1.2);
        $this->recordLatency('GatewayCrossbar', 2.4);
        $this->recordLatency('RateLimiter', 0.8);
        $this->recordLatency('VoiceHarmonizer', 4.5);
        $this->recordLatency('VoiceHarmonizer', 8.2);
        $this->recordLatency('PostQuantumVault', 3.1);
        $this->recordLatency('QueryOptimizer', 1.6);
    }
}
