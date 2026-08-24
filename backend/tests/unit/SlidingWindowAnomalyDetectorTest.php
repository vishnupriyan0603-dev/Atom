<?php

use PHPUnit\Framework\TestCase;
use Atom\Analytics\SlidingWindowAnomalyDetector;

/**
 * Phase 38 — SlidingWindowAnomalyDetector unit tests (5 tests).
 */
class SlidingWindowAnomalyDetectorTest extends TestCase
{
    private SlidingWindowAnomalyDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new SlidingWindowAnomalyDetector(3.0, 10);
    }

    public function testDetectsExtremeSpikeAnomaly(): void
    {
        $series = [20, 21, 20, 22, 21, 20, 22, 21, 20, 21, 22, 150]; // 150 is extreme spike
        $res = $this->detector->detect($series);

        $this->assertGreaterThanOrEqual(1, $res['total_anomalies']);
        $this->assertSame(11, $res['anomalies'][0]['index']);
        $this->assertContains($res['anomalies'][0]['severity'], ['WARNING', 'CRITICAL']);
    }

    public function testCleanSeriesHasZeroAnomalies(): void
    {
        $series = [20, 21, 20, 21, 20, 21, 20, 21, 20, 21, 20, 21];
        $res = $this->detector->detect($series);

        $this->assertSame(0, $res['total_anomalies']);
        $this->assertEmpty($res['anomalies']);
    }

    public function testInsufficientSampleSizeReturnsEmptyReport(): void
    {
        $series = [10, 20, 30]; // Below minimum 10 samples
        $res = $this->detector->detect($series);

        $this->assertSame(0, $res['total_anomalies']);
    }

    public function testMeanAndStdDevCalculatedCorrectly(): void
    {
        $series = [10, 10, 10, 10, 10, 10, 10, 10, 10, 10];
        $res = $this->detector->detect($series);

        $this->assertSame(10.0, $res['mean']);
        $this->assertSame(0.0, $res['std_dev']);
    }

    public function testCustomZThresholdSensitivity(): void
    {
        $detector = new SlidingWindowAnomalyDetector(1.5, 10); // More sensitive threshold
        $series = [20, 21, 20, 22, 21, 20, 22, 21, 20, 21, 22, 35]; // 35 is modest outlier
        $res = $detector->detect($series);

        $this->assertGreaterThanOrEqual(1, $res['total_anomalies']);
    }
}
