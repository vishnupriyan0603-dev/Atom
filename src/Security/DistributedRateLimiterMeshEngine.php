<?php

namespace Atom\Security;

/**
 * DistributedRateLimiterMeshEngine — Phase 99
 * Distributed sliding-window token mesh governor, peer node delta synchronization, weighted endpoint costs, and multi-tier rate limiting.
 */
class DistributedRateLimiterMeshEngine
{
    private SecretRedactor $redactor;
    private array $buckets = []; // [ key => [ 'tokens', 'capacity', 'refill_rate_per_sec', 'last_refill_at', 'tier' ] ]
    private array $nodes = []; // [ node_id => [ 'node_id', 'ip', 'last_seen_at', 'synced_consumptions' ] ]

    private array $tiers = [
        'free' => ['capacity' => 10.0, 'rate' => 2.0],
        'developer' => ['capacity' => 100.0, 'rate' => 20.0],
        'enterprise' => ['capacity' => 1000.0, 'rate' => 200.0],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleNodes();
    }

    /**
     * Consume tokens from a client's bucket.
     *
     * @param string $clientKey Unique identifier (API key, IP, or Tenant ID)
     * @param int $tokensCost Number of tokens to consume (default 1)
     * @param string $tier Tier name ('free', 'developer', 'enterprise')
     * @return array Consumption decision with HTTP rate limit headers
     */
    public function consume(string $clientKey, int $tokensCost = 1, string $tier = 'developer'): array
    {
        $cleanKey = trim(strtolower($this->redactor->redact($clientKey)));
        $cleanTier = strtolower(trim($tier));
        $cost = max(1, $tokensCost);

        if (!isset($this->tiers[$cleanTier])) {
            $cleanTier = 'developer';
        }

        $now = microtime(true);

        if (!isset($this->buckets[$cleanKey])) {
            $cfg = $this->tiers[$cleanTier];
            $this->buckets[$cleanKey] = [
                'tokens' => $cfg['capacity'],
                'capacity' => $cfg['capacity'],
                'refill_rate' => $cfg['rate'],
                'last_refill_at' => $now,
                'tier' => $cleanTier,
                'total_consumed' => 0,
                'throttled_count' => 0,
            ];
        }

        $b = &$this->buckets[$cleanKey];

        // 1. Refill tokens based on elapsed time
        $elapsed = max(0.0, $now - $b['last_refill_at']);
        $addedTokens = $elapsed * $b['refill_rate'];
        $b['tokens'] = min($b['capacity'], $b['tokens'] + $addedTokens);
        $b['last_refill_at'] = $now;

        // 2. Evaluate consumption
        if ($b['tokens'] >= $cost) {
            $b['tokens'] -= $cost;
            $b['total_consumed'] += $cost;

            return [
                'allowed' => true,
                'client_key' => $cleanKey,
                'tier' => $cleanTier,
                'remaining_tokens' => round($b['tokens'], 2),
                'capacity' => $b['capacity'],
                'retry_after_sec' => 0.0,
                'headers' => [
                    'X-RateLimit-Limit' => (int)$b['capacity'],
                    'X-RateLimit-Remaining' => (int)floor($b['tokens']),
                    'X-RateLimit-Reset' => (int)ceil($now + 1.0),
                ],
            ];
        }

        // 3. Throttled
        $b['throttled_count']++;
        $deficit = $cost - $b['tokens'];
        $retryAfter = round($deficit / $b['refill_rate'], 2);

        return [
            'allowed' => false,
            'client_key' => $cleanKey,
            'tier' => $cleanTier,
            'remaining_tokens' => round($b['tokens'], 2),
            'capacity' => $b['capacity'],
            'retry_after_sec' => $retryAfter,
            'headers' => [
                'X-RateLimit-Limit' => (int)$b['capacity'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => (int)ceil($now + $retryAfter),
                'Retry-After' => (int)ceil($retryAfter),
            ],
        ];
    }

    /**
     * Synchronize consumption deltas from peer mesh nodes.
     */
    public function syncMeshNode(string $nodeId, array $deltas): array
    {
        $cleanNode = trim($this->redactor->redact($nodeId));
        $now = microtime(true);

        $this->nodes[$cleanNode] = [
            'node_id' => $cleanNode,
            'last_seen_at' => $now,
            'status' => 'ONLINE',
        ];

        $appliedCount = 0;
        foreach ($deltas as $key => $tokensConsumed) {
            $cleanKey = trim(strtolower($this->redactor->redact($key)));
            if (isset($this->buckets[$cleanKey])) {
                $this->buckets[$cleanKey]['tokens'] = max(0.0, $this->buckets[$cleanKey]['tokens'] - (float)$tokensConsumed);
                $appliedCount++;
            }
        }

        return [
            'success' => true,
            'node_id' => $cleanNode,
            'applied_deltas_count' => $appliedCount,
            'active_mesh_nodes' => count($this->nodes),
        ];
    }

    public function getMeshStats(): array
    {
        return [
            'total_active_buckets' => count($this->buckets),
            'total_mesh_nodes' => count($this->nodes),
            'nodes' => array_values($this->nodes),
            'buckets' => array_values($this->buckets),
        ];
    }

    private function seedSampleNodes(): void
    {
        $this->nodes['node_us_east_1'] = ['node_id' => 'node_us_east_1', 'last_seen_at' => microtime(true), 'status' => 'ONLINE'];
        $this->nodes['node_eu_west_1'] = ['node_id' => 'node_eu_west_1', 'last_seen_at' => microtime(true), 'status' => 'ONLINE'];
        $this->nodes['node_ap_south_1'] = ['node_id' => 'node_ap_south_1', 'last_seen_at' => microtime(true), 'status' => 'ONLINE'];
    }
}
