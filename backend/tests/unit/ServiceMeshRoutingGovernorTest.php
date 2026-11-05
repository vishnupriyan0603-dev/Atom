<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\ServiceMeshRoutingGovernor;

class ServiceMeshRoutingGovernorTest extends TestCase
{
    public function testConfiguresTrafficSplitWithCanary(): void
    {
        $mesh = new ServiceMeshRoutingGovernor();
        $res = $mesh->configureTrafficSplit('atom-chat-api', 85, 15);

        $this->assertEquals('atom-chat-api', $res['service']);
        $this->assertTrue($res['mtls_enforced']);
        $this->assertEquals(85, $res['routes'][0]['weight_percent']);
        $this->assertEquals(15, $res['routes'][1]['weight_percent']);
    }

    public function testTripsCircuitBreakerOnThreshold(): void
    {
        $mesh = new ServiceMeshRoutingGovernor();
        $res = $mesh->evaluateCircuitBreaker('atom-llm-upstream', 5, 5);

        $this->assertEquals('OPEN (TRIPPED)', $res['state']);
        $this->assertStringContainsString('diverted', $res['action_taken']);
    }
}