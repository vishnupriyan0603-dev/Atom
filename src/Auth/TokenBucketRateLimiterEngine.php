<?php

namespace Atom\Auth;

use Atom\Security\SecretRedactor;

/**
 * TokenBucketRateLimiterEngine — Phase 56
 * Multi-tenant sliding-window token bucket rate limiter with burst allowance and quota management.
 */
class TokenBucketRateLimiterEngine
{
    private SecretRedactor $redactor;
    private array $buckets = [];
    private array $tenantQuotas = [
        'default' => ['capacity' => 60, 'refill_rate' => 1.0], // 60 tokens, 1 token/sec (60 RPM)
        'tier_enterprise' => ['capacity' => 600, 'refill_rate' => 10.0], // 600 tokens, 10 tokens/sec (600 RPM)
        'tier_free' => ['capacity' => 20, 'refill_rate' => 0.33], // 20 tokens, 20 RPM
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Check and consume rate limit tokens for a client/tenant.
     *
     * @param string $clientId Identifier (e.g. tenant_id, user_id, ip_address)
     * @param int $tokensToConsume Number of tokens required (default: 1)
     * @param string $tier Tier name (default, tier_enterprise, tier_free)
     * @return array [ 'allowed' => bool, 'remaining' => int, 'limit' => int, 'retry_after_sec' => int ]
     */
    public function consume(string $clientId, int $tokensToConsume = 1, string $tier = 'default'): array
    {
        $cleanId = $this->redactor->redact($clientId);
        $quota = $this->tenantQuotas[$tier] ?? $this->tenantQuotas['default'];
        $capacity = (float) $quota['capacity'];
        $refillRate = (float) $quota['refill_rate'];
        $now = microtime(true);

        if (!isset($this->buckets[$cleanId])) {
            $this->buckets[$cleanId] = [
                'tokens' => $capacity,
                'last_refill' => $now,
            ];
        }

        $bucket = &$this->buckets[$cleanId];

        // Refill bucket based on elapsed time
        $elapsed = max(0.0, $now - $bucket['last_refill']);
        $bucket['tokens'] = min($capacity, $bucket['tokens'] + ($elapsed * $refillRate));
        $bucket['last_refill'] = $now;

        $allowed = false;
        $retryAfter = 0;

        if ($bucket['tokens'] >= $tokensToConsume) {
            $bucket['tokens'] -= $tokensToConsume;
            $allowed = true;
        } else {
            $tokensNeeded = $tokensToConsume - $bucket['tokens'];
            $retryAfter = (int) ceil($tokensNeeded / max(0.01, $refillRate));
        }

        $remaining = (int) floor($bucket['tokens']);

        return [
            'allowed' => $allowed,
            'client_id' => $cleanId,
            'tier' => $tier,
            'limit' => (int) $capacity,
            'remaining' => $remaining,
            'retry_after_sec' => $retryAfter,
            'status' => $allowed ? 'ALLOWED' : 'RATE_LIMITED_429',
        ];
    }

    /**
     * Get rate limiter metrics across all active buckets.
     */
    public function getMetrics(): array
    {
        $totalBuckets = count($this->buckets);
        $throttledCount = 0;

        foreach ($this->buckets as $b) {
            if ($b['tokens'] < 1.0) {
                $throttledCount++;
            }
        }

        return [
            'active_clients' => $totalBuckets,
            'throttled_clients' => $throttledCount,
            'supported_tiers' => array_keys($this->tenantQuotas),
            'algorithm' => 'Token Bucket with Continuous Refill',
        ];
    }

    /**
     * Set custom quota for a tenant or tier.
     */
    public function setTierQuota(string $tier, int $capacity, float $refillRate): void
    {
        $this->tenantQuotas[$tier] = [
            'capacity' => $capacity,
            'refill_rate' => $refillRate,
        ];
    }
}
