<?php

use PHPUnit\Framework\TestCase;
use Atom\Math\StatisticalAnalyzer;

/**
 * Phase 31 — StatisticalAnalyzer unit tests (5 tests).
 */
class StatisticalAnalyzerTest extends TestCase
{
    private StatisticalAnalyzer $stats;

    protected function setUp(): void
    {
        $this->stats = new StatisticalAnalyzer();
    }

    public function testDescriptiveSummaryMetrics(): void
    {
        $data = [10, 20, 30, 40, 50];
        $res = $this->stats->describe($data);

        $this->assertSame(5, $res['count']);
        $this->assertEqualsWithDelta(30.0, $res['mean'], 0.001);
        $this->assertEqualsWithDelta(30.0, $res['median'], 0.001);
        $this->assertEqualsWithDelta(250.0, $res['variance'], 0.001);
        $this->assertEqualsWithDelta(15.811, $res['std_dev'], 0.001);
    }

    public function testPercentileAndIqr(): void
    {
        $data = [15, 20, 35, 40, 50];
        $p50 = $this->stats->percentile($data, 50);
        $this->assertEqualsWithDelta(35.0, $p50, 0.001);

        $desc = $this->stats->describe($data);
        $this->assertGreaterThan(0, $desc['iqr']);
    }

    public function testLinearRegressionOrdinaryLeastSquares(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [2, 4, 6, 8, 10]; // y = 2x + 0

        $res = $this->stats->linearRegression($x, $y);

        $this->assertEqualsWithDelta(2.0, $res['slope'], 0.001);
        $this->assertEqualsWithDelta(0.0, $res['intercept'], 0.001);
        $this->assertEqualsWithDelta(1.0, $res['r_squared'], 0.001);
    }

    public function testPearsonCorrelation(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [10, 20, 30, 40, 50];

        $corr = $this->stats->correlation($x, $y);

        $this->assertEqualsWithDelta(1.0, $corr, 0.001);
    }

    public function testEmptyDatasetThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stats->describe([]);
    }
}
