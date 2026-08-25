<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Auth\TokenBucketRateLimiterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 56 — TokenBucketRateLimiterEngine unit tests (6 tests).
 */
class TokenBucketRateLimiterEngineTest extends TestCase
{
    private TokenBucketRateLimiterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TokenBucketRateLimiterEngine(new SecretRedactor());
    }

    public function testInitialConsumeAllowedWithFullCapacity(): void
    {
        $res = $this->engine->consume('client_test_01', 1, 'default');

        $this->assertTrue($res['allowed']);
        $this->assertSame(59, $res['remaining']);
        $this->assertSame(60, $res['limit']);
        $this->assertSame('ALLOWED', $res['status']);
    }

    public function testBurstOverCapacityThrottlesWithRetryAfter(): void
    {
        // Try to consume 70 tokens on a 60-capacity bucket
        $res = $this->engine->consume('client_burst_01', 70, 'default');

        $this->assertFalse($res['allowed']);
        $this->assertSame('RATE_LIMITED_429', $res['status']);
        $this->assertGreaterThan(0, $res['retry_after_sec']);
    }

    public function testEnterpriseTierHasHigherCapacity(): void
    {
        $res = $this->engine->consume('client_enterprise_01', 100, 'tier_enterprise');

        $this->assertTrue($res['allowed']);
        $this->assertSame(500, $res['remaining']);
        $this->assertSame(600, $res['limit']);
    }

    public function testFreeTierThrottlesFaster(): void
    {
        $res = $this->engine->consume('client_free_01', 25, 'tier_free');

        $this->assertFalse($res['allowed']);
        $this->assertSame(20, $res['limit']);
    }

    public function testGetMetricsReportsActiveBuckets(): void
    {
        $this->engine->consume('c1', 1);
        $this->engine->consume('c2', 1);

        $metrics = $this->engine->getMetrics();

        $this->assertGreaterThanOrEqual(2, $metrics['active_clients']);
        $this->assertContains('tier_enterprise', $metrics['supported_tiers']);
    }

    public function testCustomTierQuotaConfiguration(): void
    {
        $this->engine->setTierQuota('tier_vip', 1000, 50.0);
        $res = $this->engine->consume('client_vip', 500, 'tier_vip');

        $this->assertTrue($res['allowed']);
        $this->assertSame(500, $res['remaining']);
    }
}
