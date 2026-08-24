<?php

use PHPUnit\Framework\TestCase;
use Atom\Analytics\SystemResourcePredictor;

/**
 * Phase 38 — SystemResourcePredictor unit tests (5 tests).
 */
class SystemResourcePredictorTest extends TestCase
{
    private SystemResourcePredictor $predictor;

    protected function setUp(): void
    {
        $this->predictor = new SystemResourcePredictor();
    }

    public function testPredictSaturationTTEForUpwardTrend(): void
    {
        $history = [50.0, 55.0, 60.0, 65.0, 70.0, 75.0]; // +5% per step
        $res = $this->predictor->predictSaturation($history, 95.0);

        $this->assertSame(75.0, $res['current_pct']);
        $this->assertGreaterThan(0, $res['growth_rate']);
        $this->assertSame(4, $res['steps_to_limit']); // 4 steps from 75% to 95% at 5%/step
        $this->assertSame('CRITICAL', $res['risk_level']);
    }

    public function testPredictSaturationForFlatTrendHasNoTTE(): void
    {
        $history = [40.0, 40.0, 40.0, 40.0, 40.0];
        $res = $this->predictor->predictSaturation($history, 95.0);

        $this->assertSame(40.0, $res['current_pct']);
        $this->assertNull($res['steps_to_limit']);
        $this->assertSame('LOW', $res['risk_level']);
    }

    public function testAlreadySaturatedReturnsZeroSteps(): void
    {
        $history = [80.0, 90.0, 96.0];
        $res = $this->predictor->predictSaturation($history, 95.0);

        $this->assertSame(0, $res['steps_to_limit']);
        $this->assertSame('CRITICAL', $res['risk_level']);
    }

    public function testSingleItemHistoryGracefulFallback(): void
    {
        $res = $this->predictor->predictSaturation([50.0]);

        $this->assertSame(50.0, $res['current_pct']);
        $this->assertSame('UNKNOWN', $res['risk_level']);
    }

    public function testWarningRiskClassification(): void
    {
        $history = [50.0, 60.0, 76.0];
        $res = $this->predictor->predictSaturation($history, 95.0);

        $this->assertContains($res['risk_level'], ['WARNING', 'CRITICAL']);
    }
}
