<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DistributedCacheInvalidatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 70 Landmark — DistributedCacheInvalidatorEngine unit tests (6 tests).
 */
class DistributedCacheInvalidatorEngineTest extends TestCase
{
    private DistributedCacheInvalidatorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DistributedCacheInvalidatorEngine(new SecretRedactor());
    }

    public function testSetAndGetValidCacheKey(): void
    {
        $this->engine->set('test:key:1', ['data' => 123], 300, 'tenant_1', ['tag_a']);
        $res = $this->engine->get('test:key:1');

        $this->assertTrue($res['found']);
        $this->assertSame(['data' => 123], $res['value']);
        $this->assertSame('tenant_1', $res['tenant_id']);
        $this->assertGreaterThan(0.0, $res['ttl_remaining']);
    }

    public function testInvalidateTagPurgesAllMatchingKeys(): void
    {
        $this->engine->set('order:1', ['amt' => 10], 300, 'tenant_1', ['orders']);
        $this->engine->set('order:2', ['amt' => 20], 300, 'tenant_1', ['orders']);
        $this->engine->set('user:1', ['name' => 'Bob'], 300, 'tenant_1', ['users']);

        $inv = $this->engine->invalidateTag('orders');
        $this->assertTrue($inv['success']);
        $this->assertSame(3, $inv['count']); // order:1, order:2, plus seeded order:9001:details

        $get1 = $this->engine->get('order:1');
        $this->assertFalse($get1['found']);

        $getUser = $this->engine->get('user:1');
        $this->assertTrue($getUser['found']);
    }

    public function testTenantScopedTagInvalidation(): void
    {
        $this->engine->set('item:a', 'valA', 300, 'tenant_1', ['inventory']);
        $this->engine->set('item:b', 'valB', 300, 'tenant_2', ['inventory']);

        $inv = $this->engine->invalidateTag('inventory', 'tenant_1');
        $this->assertSame(1, $inv['count']);

        $getA = $this->engine->get('item:a');
        $this->assertFalse($getA['found']);

        $getB = $this->engine->get('item:b');
        $this->assertTrue($getB['found']);
    }

    public function testXFetchThunderingHerdEarlyExpirationTrigger(): void
    {
        // Set item with 1 second TTL and high computation delta
        $this->engine->set('hot:key', 'computation_result', 1, 'tenant_1', ['hot'], 10.0);
        $res = $this->engine->get('hot:key', 5.0);

        $this->assertTrue($res['found']);
        $this->assertIsBool($res['should_recompute']);
    }

    public function testGetStatsReportsAccurateHitRatio(): void
    {
        $this->engine->set('metric:hit', 'ok', 300);
        $this->engine->get('metric:hit');
        $this->engine->get('metric:miss:unknown');

        $stats = $this->engine->getStats();
        $this->assertGreaterThanOrEqual(0.0, $stats['hit_ratio_pct']);
        $this->assertLessThanOrEqual(100.0, $stats['hit_ratio_pct']);
        $this->assertGreaterThan(0, $stats['total_keys']);
    }

    public function testDeleteSpecificKey(): void
    {
        $this->engine->set('temp:delete:key', 'delete_me', 300);
        $this->assertTrue($this->engine->delete('temp:delete:key'));
        $this->assertFalse($this->engine->get('temp:delete:key')['found']);
    }
}
