<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * DistributedCacheInvalidatorEngine — Phase 70 Landmark Milestone
 * Multi-tenant tagged distributed cache invalidation and XFetch thundering-herd stampede protector.
 */
class DistributedCacheInvalidatorEngine
{
    private SecretRedactor $redactor;
    private array $store = [];
    private array $tagIndex = [];
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleData();
    }

    /**
     * Store an item in the cache with tenant ID, TTL in seconds, and tags.
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 300, string $tenantId = 'default', array $tags = [], float $computationDelta = 0.05): bool
    {
        $cleanKey = trim($this->redactor->redact($key));
        $now = microtime(true);
        $expiresAt = $now + max(1, $ttlSeconds);

        $this->store[$cleanKey] = [
            'key' => $cleanKey,
            'value' => $value,
            'tenant_id' => $tenantId,
            'tags' => $tags,
            'ttl' => $ttlSeconds,
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'computation_delta' => $computationDelta, // time taken to compute value in seconds
        ];

        // Index by tags
        foreach ($tags as $tag) {
            $cleanTag = trim(strtolower($tag));
            $this->tagIndex[$cleanTag][$cleanKey] = true;
        }

        return true;
    }

    /**
     * Retrieve an item with XFetch probabilistic early expiration to avoid thundering herd.
     *
     * @param string $key
     * @param float $beta Tuning parameter for early expiration (default 1.0)
     * @return array [ 'found' => bool, 'value' => mixed, 'should_recompute' => bool ]
     */
    public function get(string $key, float $beta = 1.0): array
    {
        $cleanKey = trim($this->redactor->redact($key));
        $now = microtime(true);

        if (!isset($this->store[$cleanKey])) {
            $this->misses++;
            return ['found' => false, 'value' => null, 'should_recompute' => true];
        }

        $item = $this->store[$cleanKey];

        // Hard expiration check
        if ($now >= $item['expires_at']) {
            $this->delete($cleanKey);
            $this->misses++;
            return ['found' => false, 'value' => null, 'should_recompute' => true];
        }

        $this->hits++;

        // XFetch algorithm: now - (delta * beta * ln(rand())) > expires_at
        // If true, trigger background recomputation before hard expiration
        $rand = mt_rand(1, 10000) / 10000.0;
        $delta = $item['computation_delta'];
        $shouldRecompute = ($now - ($delta * $beta * log($rand))) >= $item['expires_at'];

        return [
            'found' => true,
            'value' => $item['value'],
            'tenant_id' => $item['tenant_id'],
            'ttl_remaining' => round($item['expires_at'] - $now, 1),
            'should_recompute' => $shouldRecompute,
        ];
    }

    /**
     * Invalidate all keys matching a specific tag (e.g. 'users', 'orders').
     */
    public function invalidateTag(string $tag, ?string $tenantId = null): array
    {
        $cleanTag = trim(strtolower($tag));
        if (!isset($this->tagIndex[$cleanTag])) {
            return ['success' => true, 'invalidated_keys' => [], 'count' => 0];
        }

        $keys = array_keys($this->tagIndex[$cleanTag]);
        $purged = [];

        foreach ($keys as $k) {
            if (isset($this->store[$k])) {
                if ($tenantId === null || $this->store[$k]['tenant_id'] === $tenantId) {
                    unset($this->store[$k]);
                    $purged[] = $k;
                }
            }
        }

        unset($this->tagIndex[$cleanTag]);

        return [
            'success' => true,
            'tag' => $cleanTag,
            'invalidated_keys' => $purged,
            'count' => count($purged),
        ];
    }

    public function delete(string $key): bool
    {
        if (isset($this->store[$key])) {
            unset($this->store[$key]);
            return true;
        }
        return false;
    }

    /**
     * Get real-time cache telemetry metrics.
     */
    public function getStats(): array
    {
        $totalOps = $this->hits + $this->misses;
        $hitRatio = $totalOps > 0 ? round(($this->hits / $totalOps) * 100, 1) : 100.0;

        return [
            'total_keys' => count($this->store),
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_ratio_pct' => $hitRatio,
            'active_tags_count' => count($this->tagIndex),
            'tags' => array_keys($this->tagIndex),
            'keys' => array_values(array_map(function ($item) {
                return [
                    'key' => $item['key'],
                    'tenant_id' => $item['tenant_id'],
                    'tags' => $item['tags'],
                    'ttl' => $item['ttl'],
                ];
            }, $this->store)),
        ];
    }

    private function seedSampleData(): void
    {
        $this->set('user:101:profile', ['id' => 101, 'name' => 'Alex'], 600, 'tenant_alpha', ['users', 'tenant_alpha']);
        $this->set('user:102:profile', ['id' => 102, 'name' => 'Elena'], 600, 'tenant_alpha', ['users', 'tenant_alpha']);
        $this->set('order:9001:details', ['order_id' => 9001, 'amount' => 250.0], 300, 'tenant_alpha', ['orders']);
        $this->set('system:config:runtime', ['mode' => 'production', 'edge_sync' => true], 1200, 'default', ['system']);
    }
}
