<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\SyntheticLoadTestingEngine;

class SyntheticLoadTestingEngineTest extends TestCase
{
    public function testExecutesLoadTestWithHighThroughput(): void
    {
        $engine = new SyntheticLoadTestingEngine();
        $res = $engine->runLoadTest(function($i) {
            return md5("key_{$i}");
        }, 50, 5);

        $this->assertEquals(50, $res['total_requests']);
        $this->assertEquals(50, $res['success_count']);
        $this->assertEquals(0, $res['error_count']);
        $this->assertEquals('PASSED', $res['status']);
        $this->assertGreaterThan(0, $res['requests_per_second']);
    }
}