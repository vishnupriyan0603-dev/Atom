<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\CanaryTrafficSplitEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 71 — CanaryTrafficSplitEngine unit tests (6 tests).
 */
class CanaryTrafficSplitEngineTest extends TestCase
{
    private CanaryTrafficSplitEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CanaryTrafficSplitEngine(new SecretRedactor());
    }

    public function testTenantAffinityRoutesToCanary(): void
    {
        $res = $this->engine->routeRequest('req_123', 'tenant_beta');

        $this->assertTrue($res['is_canary']);
        $this->assertSame('TENANT_AFFINITY_MATCH', $res['reason']);
        $this->assertSame('v1.5.0-canary', $res['target_version']);
    }

    public function testOverrideHeaderForcesCanaryRoute(): void
    {
        $res = $this->engine->routeRequest('req_456', 'default', ['X-Canary-Override' => 'true']);

        $this->assertTrue($res['is_canary']);
        $this->assertSame('OVERRIDE_HEADER_MATCH', $res['reason']);
    }

    public function testCanaryZeroWeightRoutesToStable(): void
    {
        $this->engine->setCanaryWeight(0);
        $res = $this->engine->routeRequest('req_789', 'default');

        $this->assertFalse($res['is_canary']);
        $this->assertSame('CANARY_WEIGHT_ZERO', $res['reason']);
        $this->assertSame('v1.4.0-stable', $res['target_version']);
    }

    public function testAutomatedCircuitBreakerTripsOnErrorRate(): void
    {
        $this->engine->setCanaryWeight(100); // Route all to canary

        // Record 10 canary requests with 5 failures (50% error rate > 5% threshold)
        for ($i = 0; $i < 10; $i++) {
            $this->engine->routeRequest("burst_req_{$i}");
            $this->engine->recordCanaryTelemetry($i >= 5);
        }

        $status = $this->engine->getStatus();
        $this->assertTrue($status['circuit_tripped']);
        $this->assertSame(0, $status['canary_weight_pct']);

        // Subsequent requests should fall back to stable
        $subsequent = $this->engine->routeRequest('post_trip_req');
        $this->assertFalse($subsequent['is_canary']);
        $this->assertSame('CIRCUIT_TRIPPED_AUTO_ROLLED_BACK', $subsequent['reason']);
    }

    public function testSetCanaryWeightClampedBetween0And100(): void
    {
        $this->engine->setCanaryWeight(150);
        $this->assertSame(100, $this->engine->getStatus()['canary_weight_pct']);

        $this->engine->setCanaryWeight(-50);
        $this->assertSame(0, $this->engine->getStatus()['canary_weight_pct']);
    }

    public function testResetCircuitBreakerRestoresHealthyState(): void
    {
        $this->engine->setCanaryWeight(50);
        $this->engine->resetCircuitBreaker();

        $status = $this->engine->getStatus();
        $this->assertFalse($status['circuit_tripped']);
        $this->assertSame(0, $status['canary_requests']);
    }
}
