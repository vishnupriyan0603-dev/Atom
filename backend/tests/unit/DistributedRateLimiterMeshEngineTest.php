<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\DistributedRateLimiterMeshEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 99 — DistributedRateLimiterMeshEngine unit tests (6 tests).
 */
class DistributedRateLimiterMeshEngineTest extends TestCase
{
    private DistributedRateLimiterMeshEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DistributedRateLimiterMeshEngine(new SecretRedactor());
    }

    public function testInitialConsumeAllowedWithFullCapacity(): void
    {
        $res = $this->engine->consume('test_client_key_1', 1, 'developer');

        $this->assertTrue($res['allowed']);
        $this->assertSame(99.0, $res['remaining_tokens']);
        $this->assertSame(100.0, $res['capacity']);
        $this->assertSame(0.0, $res['retry_after_sec']);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $res['headers']);
    }

    public function testBurstOverCapacityThrottlesWithRetryAfter(): void
    {
        // Free tier capacity is 10
        $res = $this->engine->consume('test_client_free', 15, 'free');

        $this->assertFalse($res['allowed']);
        $this->assertGreaterThan(0.0, $res['retry_after_sec']);
        $this->assertSame(0, $res['headers']['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $res['headers']);
    }

    public function testEnterpriseTierHasHigherCapacity(): void
    {
        $res = $this->engine->consume('test_enterprise_corp', 500, 'enterprise');

        $this->assertTrue($res['allowed']);
        $this->assertSame(500.0, $res['remaining_tokens']);
        $this->assertSame(1000.0, $res['capacity']);
    }

    public function testSyncMeshNodeAppliesPeerConsumption(): void
    {
        // 1. Initial consume
        $this->engine->consume('peer_shared_key', 10, 'developer'); // 90 remaining

        // 2. Peer node reports 20 tokens consumed on its edge
        $syncRes = $this->engine->syncMeshNode('node_edge_tokyo', ['peer_shared_key' => 20]);

        $this->assertTrue($syncRes['success']);
        $this->assertSame(1, $syncRes['applied_deltas_count']);

        // 3. Next local consume sees reduced balance
        $res = $this->engine->consume('peer_shared_key', 1, 'developer');
        $this->assertLessThanOrEqual(70.0, $res['remaining_tokens']);
    }

    public function testGetMeshStatsReportsNodesAndBuckets(): void
    {
        $stats = $this->engine->getMeshStats();

        $this->assertGreaterThanOrEqual(3, $stats['total_mesh_nodes']);
        $this->assertArrayHasKey('nodes', $stats);
        $this->assertArrayHasKey('buckets', $stats);
    }

    public function testInvalidTierFallsBackToDeveloper(): void
    {
        $res = $this->engine->consume('test_fallback', 1, 'unknown_platinum_tier');

        $this->assertTrue($res['allowed']);
        $this->assertSame('developer', $res['tier']);
    }
}
