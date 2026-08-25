<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\ReverseProxyLoadBalancerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 86 — ReverseProxyLoadBalancerEngine unit tests (6 tests).
 */
class ReverseProxyLoadBalancerEngineTest extends TestCase
{
    private ReverseProxyLoadBalancerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ReverseProxyLoadBalancerEngine(new SecretRedactor());
    }

    public function testRouteRequestRoundRobin(): void
    {
        $this->engine->setAlgorithm('round_robin');

        $res1 = $this->engine->routeRequest('127.0.0.1', '/api/users');
        $res2 = $this->engine->routeRequest('127.0.0.1', '/api/users');

        $this->assertTrue($res1['success']);
        $this->assertTrue($res2['success']);
        $this->assertSame('round_robin', $res1['algorithm_used']);
        $this->assertArrayHasKey('X-Forwarded-For', $res1['headers_injected']);
    }

    public function testIpHashStickySessionDeterminism(): void
    {
        $this->engine->setAlgorithm('ip_hash');

        $res1 = $this->engine->routeRequest('192.168.1.55', '/api/orders');
        $res2 = $this->engine->routeRequest('192.168.1.55', '/api/orders');

        $this->assertTrue($res1['success']);
        $this->assertSame($res1['routed_node']['node_id'], $res2['routed_node']['node_id']);
    }

    public function testDegradedNodeExcludedFromRoutingPool(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine(new SecretRedactor());
        $engine->setNodeHealth('upstream_us_east', false);
        $engine->setNodeHealth('upstream_us_west', false);

        // Only upstream_eu_central remains healthy
        $res = $engine->routeRequest('10.0.0.1');

        $this->assertTrue($res['success']);
        $this->assertSame('upstream_eu_central', $res['routed_node']['node_id']);
    }

    public function testNoHealthyNodesFailsGracefully(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine(new SecretRedactor());
        $engine->setNodeHealth('upstream_us_east', false);
        $engine->setNodeHealth('upstream_us_west', false);
        $engine->setNodeHealth('upstream_eu_central', false);

        $res = $engine->routeRequest();

        $this->assertFalse($res['success']);
        $this->assertSame('NO_HEALTHY_UPSTREAMS_AVAILABLE', $res['error']);
    }

    public function testLeastLatencyRoutingSelection(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine(new SecretRedactor());
        $engine->setAlgorithm('least_latency');

        $res = $engine->routeRequest();
        $this->assertTrue($res['success']);
        $this->assertIsInt($res['routed_node']['latency_ms']);
    }

    public function testSetAlgorithmValidation(): void
    {
        $this->assertTrue($this->engine->setAlgorithm('weighted'));
        $this->assertFalse($this->engine->setAlgorithm('unsupported_quantum_routing'));
    }
}
