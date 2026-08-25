<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Telemetry\LatencyHeatmapEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 67 — LatencyHeatmapEngine unit tests (6 tests).
 */
class LatencyHeatmapEngineTest extends TestCase
{
    private LatencyHeatmapEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new LatencyHeatmapEngine(new SecretRedactor());
    }

    public function testRecordLatencyBinsCorrectly(): void
    {
        $resFast = $this->engine->recordLatency('FastService', 4.2);
        $this->assertSame('p0_fast', $resFast['bucket']);
        $this->assertFalse($resFast['is_sla_breach']);

        $resWarm = $this->engine->recordLatency('WarmService', 85.0);
        $this->assertSame('p2_warm', $resWarm['bucket']);
        $this->assertTrue($resWarm['is_sla_breach']);

        $resBreach = $this->engine->recordLatency('BreachService', 350.0);
        $this->assertSame('p3_breach', $resBreach['bucket']);
        $this->assertTrue($resBreach['is_sla_breach']);
    }

    public function testGetHeatmapMatrixReturnsValidStructure(): void
    {
        $matrix = $this->engine->getHeatmapMatrix();

        $this->assertGreaterThan(0, $matrix['total_requests']);
        $this->assertGreaterThan(0.0, $matrix['sla_compliance_pct']);
        $this->assertNotEmpty($matrix['matrix']);
    }

    public function testSlaCompliancePercentageCalculation(): void
    {
        $matrix = $this->engine->getHeatmapMatrix();
        $this->assertLessThanOrEqual(100.0, $matrix['sla_compliance_pct']);
        $this->assertGreaterThanOrEqual(0.0, $matrix['sla_compliance_pct']);
    }

    public function testSubsystemStatsMinMaxCalculation(): void
    {
        $this->engine->recordLatency('DynamicSubsystem', 10.0);
        $this->engine->recordLatency('DynamicSubsystem', 20.0);
        $this->engine->recordLatency('DynamicSubsystem', 30.0);

        $matrix = $this->engine->getHeatmapMatrix();
        $matched = null;
        foreach ($matrix['matrix'] as $row) {
            if ($row['subsystem'] === 'DynamicSubsystem') {
                $matched = $row;
                break;
            }
        }

        $this->assertNotNull($matched);
        $this->assertSame(3, $matched['requests_count']);
        $this->assertSame(20.0, $matched['avg_ms']);
        $this->assertSame(10.0, $matched['min_ms']);
        $this->assertSame(30.0, $matched['max_ms']);
    }

    public function testZeroLatencyClamping(): void
    {
        $res = $this->engine->recordLatency('ZeroService', -5.0);
        $this->assertSame(0.01, $res['duration_ms']);
    }

    public function testStatusOptimalVsBreach(): void
    {
        $this->engine->recordLatency('OptimalSub', 2.0);
        $matrix = $this->engine->getHeatmapMatrix();

        $optimalRow = null;
        foreach ($matrix['matrix'] as $r) {
            if ($r['subsystem'] === 'OptimalSub') {
                $optimalRow = $r;
                break;
            }
        }
        $this->assertSame('OPTIMAL', $optimalRow['status']);
    }
}
