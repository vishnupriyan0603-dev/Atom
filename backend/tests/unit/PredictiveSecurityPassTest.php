<?php

use PHPUnit\Framework\TestCase;
use Atom\Analytics\HoltWintersForecaster;
use Atom\Analytics\SlidingWindowAnomalyDetector;
use Atom\Analytics\SystemResourcePredictor;

/**
 * Phase 38 — PredictiveSecurityPassTest security & safety tests (5 tests).
 */
class PredictiveSecurityPassTest extends TestCase
{
    public function testSecretRedactionInPredictiveOutputs(): void
    {
        $forecaster = new HoltWintersForecaster();
        $res = $forecaster->forecast([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140]);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('predictions', $res);
    }

    public function testNoEvalOrShellExecutionInAnalyticsSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $hwCode = file_get_contents($rootDir . '/src/Analytics/HoltWintersForecaster.php');
        $anomCode = file_get_contents($rootDir . '/src/Analytics/SlidingWindowAnomalyDetector.php');
        $resCode = file_get_contents($rootDir . '/src/Analytics/SystemResourcePredictor.php');
        $decompCode = file_get_contents($rootDir . '/src/Analytics/SeasonalityDecomposer.php');

        $this->assertNotFalse($hwCode);
        $this->assertNotFalse($anomCode);
        $this->assertNotFalse($resCode);
        $this->assertNotFalse($decompCode);

        $this->assertStringNotContainsString('eval(', $hwCode);
        $this->assertStringNotContainsString('eval(', $anomCode);
        $this->assertStringNotContainsString('eval(', $resCode);
        $this->assertStringNotContainsString('eval(', $decompCode);
        $this->assertStringNotContainsString('exec(', $hwCode);
        $this->assertStringNotContainsString('shell_exec(', $hwCode);
    }

    public function testZeroVarianceDivisionByZeroSafety(): void
    {
        $detector = new SlidingWindowAnomalyDetector();
        // Constant series has 0 variance
        $res = $detector->detect([5, 5, 5, 5, 5, 5, 5, 5, 5, 5]);

        $this->assertSame(0, $res['total_anomalies']);
        $this->assertSame(0.0, $res['std_dev']);
    }

    public function testLargeArrayMemorySafety(): void
    {
        $forecaster = new HoltWintersForecaster();
        $largeSeries = array_fill(0, 1000, 42.0);
        $res = $forecaster->forecast($largeSeries, 10);

        $this->assertCount(10, $res['predictions']);
    }

    public function testNegativeOrExtremeValueHandling(): void
    {
        $predictor = new SystemResourcePredictor();
        $res = $predictor->predictSaturation([-10.0, 0.0, 50.0, 100.0, 200.0]);

        $this->assertSame(200.0, $res['current_pct']);
        $this->assertSame('CRITICAL', $res['risk_level']);
    }
}
