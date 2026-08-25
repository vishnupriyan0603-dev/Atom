<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * ConnectionPoolGovernorEngine — Phase 79
 * Multi-tenant dynamic database connection pool governor, leak detector, and starvation protector.
 */
class ConnectionPoolGovernorEngine
{
    private SecretRedactor $redactor;
    private int $maxConnections = 50;
    private float $leakTimeoutSeconds = 3.0; // Leased handle held > 3s is flagged as leaked

    private array $activeLeases = [];
    private int $totalLeaseCount = 0;
    private int $totalReclaimedCount = 0;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleLeases();
    }

    /**
     * Lease a connection from the pool.
     */
    public function leaseConnection(string $tenantId = 'default', string $context = 'query_execution'): array
    {
        $cleanTenant = trim(strtolower($this->redactor->redact($tenantId)));
        $now = microtime(true);

        if (count($this->activeLeases) >= $this->maxConnections) {
            return [
                'success' => false,
                'error' => 'CONNECTION_POOL_EXHAUSTED',
                'active_connections' => count($this->activeLeases),
                'max_connections' => $this->maxConnections,
            ];
        }

        $handleId = 'conn_' . bin2hex(random_bytes(6));
        $lease = [
            'handle_id' => $handleId,
            'tenant_id' => $cleanTenant,
            'context' => $context,
            'leased_at' => $now,
            'status' => 'ACTIVE',
        ];

        $this->activeLeases[$handleId] = $lease;
        $this->totalLeaseCount++;

        return [
            'success' => true,
            'handle_id' => $handleId,
            'tenant_id' => $cleanTenant,
            'leased_at' => $now,
            'active_connections' => count($this->activeLeases),
            'available_connections' => $this->maxConnections - count($this->activeLeases),
        ];
    }

    /**
     * Release a leased connection handle back to the pool.
     */
    public function releaseConnection(string $handleId): bool
    {
        if (isset($this->activeLeases[$handleId])) {
            unset($this->activeLeases[$handleId]);
            return true;
        }
        return false;
    }

    /**
     * Scan for leaked connection handles held longer than leakTimeoutSeconds and reclaim them.
     */
    public function reclaimLeakedConnections(): array
    {
        $now = microtime(true);
        $reclaimed = [];

        foreach ($this->activeLeases as $handleId => $lease) {
            $duration = $now - $lease['leased_at'];
            if ($duration >= $this->leakTimeoutSeconds) {
                unset($this->activeLeases[$handleId]);
                $this->totalReclaimedCount++;
                $reclaimed[] = [
                    'handle_id' => $handleId,
                    'tenant_id' => $lease['tenant_id'],
                    'held_duration_s' => round($duration, 2),
                    'context' => $lease['context'],
                ];
            }
        }

        return [
            'success' => true,
            'reclaimed_count' => count($reclaimed),
            'reclaimed_handles' => $reclaimed,
            'active_connections' => count($this->activeLeases),
        ];
    }

    public function getPoolStatus(): array
    {
        $now = microtime(true);
        $activeCount = count($this->activeLeases);
        $utilizationPct = round(($activeCount / max(1, $this->maxConnections)) * 100, 1);

        $leasesWithDuration = array_map(function ($item) use ($now) {
            $duration = round($now - $item['leased_at'], 2);
            $isLeaked = $duration >= $this->leakTimeoutSeconds;
            return array_merge($item, [
                'held_duration_s' => $duration,
                'is_leaked' => $isLeaked,
            ]);
        }, array_values($this->activeLeases));

        return [
            'active_connections' => $activeCount,
            'available_connections' => $this->maxConnections - $activeCount,
            'max_connections' => $this->maxConnections,
            'utilization_pct' => $utilizationPct,
            'total_leases_granted' => $this->totalLeaseCount,
            'total_reclaimed_leaks' => $this->totalReclaimedCount,
            'leak_timeout_s' => $this->leakTimeoutSeconds,
            'active_leases' => $leasesWithDuration,
        ];
    }

    private function seedSampleLeases(): void
    {
        $this->leaseConnection('tenant_alpha', 'analytics_aggregation');
        $this->leaseConnection('tenant_beta', 'user_profile_fetch');
        $this->leaseConnection('default', 'telemetry_flush');
    }
}
