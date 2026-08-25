<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\GeoFencingFirewallEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 64 — GeoFencingFirewallEngine unit tests (6 tests).
 */
class GeoFencingFirewallEngineTest extends TestCase
{
    private GeoFencingFirewallEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new GeoFencingFirewallEngine(new SecretRedactor());
    }

    public function testLocalNetworkIpAlwaysAllowed(): void
    {
        $resLocal = $this->engine->evaluateAccess('127.0.0.1');
        $this->assertTrue($resLocal['allowed']);
        $this->assertSame('ACCESS_GRANTED_LOCAL_NETWORK', $resLocal['reason']);

        $resLan = $this->engine->evaluateAccess('192.168.1.100');
        $this->assertTrue($resLan['allowed']);
    }

    public function testIpInCidrSubnetMatching(): void
    {
        $this->assertTrue($this->engine->ipInCidr('192.168.1.50', '192.168.1.0/24'));
        $this->assertFalse($this->engine->ipInCidr('192.168.2.50', '192.168.1.0/24'));
        $this->assertTrue($this->engine->ipInCidr('10.5.0.1', '10.0.0.0/8'));
    }

    public function testBlockedCidrSubnetRejection(): void
    {
        $res = $this->engine->evaluateAccess('198.51.100.25');
        $this->assertFalse($res['allowed']);
        $this->assertSame('BLOCKED_BY_CIDR_RULE', $res['reason']);
    }

    public function testResolveIpReturnsValidGeoMetadata(): void
    {
        $geo = $this->engine->resolveIp('157.240.22.35');

        $this->assertArrayHasKey('country_code', $geo);
        $this->assertArrayHasKey('city', $geo);
        $this->assertArrayHasKey('lat', $geo);
        $this->assertArrayHasKey('lon', $geo);
    }

    public function testGetPolicyReturnsRegisteredRules(): void
    {
        $policy = $this->engine->getPolicy();

        $this->assertContains('IN', $policy['allowed_countries']);
        $this->assertContains('US', $policy['allowed_countries']);
        $this->assertContains('KP', $policy['blocked_countries']);
    }

    public function testInvalidCidrDoesNotThrowException(): void
    {
        $this->assertFalse($this->engine->ipInCidr('1.2.3.4', 'invalid-cidr-mask'));
    }
}
