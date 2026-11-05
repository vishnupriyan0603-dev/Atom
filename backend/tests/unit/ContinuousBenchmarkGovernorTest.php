<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\ContinuousBenchmarkGovernor;

class ContinuousBenchmarkGovernorTest extends TestCase
{
    public function testRunsMicrobenchmarkWithPercentiles(): void
    {
        $governor = new ContinuousBenchmarkGovernor();
        $res = $governor->benchmark(function() {
            $sum = 0;
            for ($i = 0; $i < 100; $i++) {
                $sum += $i;
            }
        }, 30, 'Arithmetic Test');

        $this->assertEquals('Arithmetic Test', $res['task_name']);
        $this->assertEquals(30, $res['iterations']);
        $this->assertArrayHasKey('p50', $res['latency_ms']);
        $this->assertArrayHasKey('p95', $res['latency_ms']);
        $this->assertArrayHasKey('p99', $res['latency_ms']);
        $this->assertFalse($res['memory']['leak_detected']);
    }
}