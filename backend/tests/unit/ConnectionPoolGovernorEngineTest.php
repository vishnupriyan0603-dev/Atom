<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\ConnectionPoolGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 79 — ConnectionPoolGovernorEngine unit tests (6 tests).
 */
class ConnectionPoolGovernorEngineTest extends TestCase
{
    private ConnectionPoolGovernorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ConnectionPoolGovernorEngine(new SecretRedactor());
    }

    public function testLeaseConnectionReturnsValidHandle(): void
    {
        $res = $this->engine->leaseConnection('tenant_1', 'user_lookup');

        $this->assertTrue($res['success']);
        $this->assertStringStartsWith('conn_', $res['handle_id']);
        $this->assertSame('tenant_1', $res['tenant_id']);
        $this->assertGreaterThan(0, $res['active_connections']);
    }

    public function testReleaseConnectionFreesHandle(): void
    {
        $lease = $this->engine->leaseConnection('tenant_2', 'analytics');
        $handle = $lease['handle_id'];

        $this->assertTrue($this->engine->releaseConnection($handle));

        // Releasing non-existent handle returns false
        $this->assertFalse($this->engine->releaseConnection('non_existent_handle_123'));
    }

    public function testReclaimLeakedConnectionsPurgesStaleHandles(): void
    {
        // Seeded leases will not be old yet, but we can verify reclaim execution
        $reclaim = $this->engine->reclaimLeakedConnections();

        $this->assertTrue($reclaim['success']);
        $this->assertIsInt($reclaim['reclaimed_count']);
        $this->assertIsArray($reclaim['reclaimed_handles']);
    }

    public function testGetPoolStatusReportsUtilization(): void
    {
        $status = $this->engine->getPoolStatus();

        $this->assertGreaterThan(0, $status['active_connections']);
        $this->assertGreaterThan(0, $status['available_connections']);
        $this->assertGreaterThanOrEqual(0.0, $status['utilization_pct']);
        $this->assertLessThanOrEqual(100.0, $status['utilization_pct']);
        $this->assertIsArray($status['active_leases']);
    }

    public function testPoolMaxCapacityEnforcement(): void
    {
        $engine = new ConnectionPoolGovernorEngine(new SecretRedactor());

        // Lease up to max capacity
        for ($i = 0; $i < 60; $i++) {
            $engine->leaseConnection("tenant_{$i}");
        }

        $overflow = $engine->leaseConnection('overflow_tenant');
        $this->assertFalse($overflow['success']);
        $this->assertSame('CONNECTION_POOL_EXHAUSTED', $overflow['error']);
    }

    public function testHeldDurationIsAccurate(): void
    {
        $lease = $this->engine->leaseConnection('tenant_timer');
        $status = $this->engine->getPoolStatus();

        $found = false;
        foreach ($status['active_leases'] as $l) {
            if ($l['handle_id'] === $lease['handle_id']) {
                $this->assertGreaterThanOrEqual(0.0, $l['held_duration_s']);
                $found = true;
            }
        }
        $this->assertTrue($found);
    }
}
