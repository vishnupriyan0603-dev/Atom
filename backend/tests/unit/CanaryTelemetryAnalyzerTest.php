<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\CanaryTelemetryAnalyzer;

class CanaryTelemetryAnalyzerTest extends TestCase
{
    public function testPromotesHealthyCanary(): void
    {
        $analyzer = new CanaryTelemetryAnalyzer();
        $baseline = ['error_rate' => 0.05, 'p95_latency_ms' => 15.0];
        $canary = ['error_rate' => 0.05, 'p95_latency_ms' => 14.8];

        $res = $analyzer->analyzeCanary($baseline, $canary);
        $this->assertEquals('PROMOTE', $res['decision']);
        $this->assertGreaterThanOrEqual(95.0, $res['health_score']);
    }

    public function testTriggersRollbackOnDegradedCanary(): void
    {
        $analyzer = new CanaryTelemetryAnalyzer();
        $baseline = ['error_rate' => 0.05, 'p95_latency_ms' => 15.0];
        $canary = ['error_rate' => 8.50, 'p95_latency_ms' => 45.0];

        $res = $analyzer->analyzeCanary($baseline, $canary);
        $this->assertEquals('ROLLBACK', $res['decision']);
        $this->assertLessThan(60.0, $res['health_score']);
    }
}