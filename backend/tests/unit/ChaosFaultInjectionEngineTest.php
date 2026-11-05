<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\ChaosFaultInjectionEngine;

class ChaosFaultInjectionEngineTest extends TestCase
{
    public function testInjectsSimulatedLatencySpike(): void
    {
        $chaos = new ChaosFaultInjectionEngine();
        $res = $chaos->simulateFault('latency', function() {
            return 'processed';
        }, ['delay_ms' => 5]);

        $this->assertTrue($res['fault_injected']);
        $this->assertTrue($res['recovered']);
        $this->assertEquals(100, $res['resilience_score']);
        $this->assertGreaterThanOrEqual(4.0, $res['duration_ms']);
    }
}