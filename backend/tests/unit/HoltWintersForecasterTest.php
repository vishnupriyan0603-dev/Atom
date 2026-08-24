<?php

use PHPUnit\Framework\TestCase;
use Atom\Analytics\HoltWintersForecaster;

/**
 * Phase 38 — HoltWintersForecaster unit tests (5 tests).
 */
class HoltWintersForecasterTest extends TestCase
{
    private HoltWintersForecaster $forecaster;

    protected function setUp(): void
    {
        $this->forecaster = new HoltWintersForecaster(0.3, 0.1, 0.1, 7);
    }

    public function testForecastGeneratesSpecifiedHorizonPredictions(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->forecaster->forecast($series, 5);

        $this->assertSame('HOLT_WINTERS_TRIPLE_EXPONENTIAL', $res['model']);
        $this->assertCount(5, $res['predictions']);
        $this->assertGreaterThan(0, $res['rmse']);
    }

    public function testPredictionConfidenceIntervalsAreWellOrdered(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->forecaster->forecast($series, 3);

        foreach ($res['predictions'] as $pred) {
            $this->assertLessThanOrEqual($pred['forecast'], $pred['lower_bound']);
            $this->assertGreaterThanOrEqual($pred['forecast'], $pred['upper_bound']);
        }
    }

    public function testShortSeriesUsesFallbackGracefully(): void
    {
        $series = [5, 10]; // Too short for season length 7 * 2 = 14
        $res = $this->forecaster->forecast($series, 3);

        $this->assertSame('FALLBACK_PERSISTENCE', $res['model']);
        $this->assertCount(3, $res['predictions']);
        $this->assertSame(10.0, $res['predictions'][0]['forecast']);
    }

    public function testEmptySeriesFallback(): void
    {
        $res = $this->forecaster->forecast([], 5);
        $this->assertSame('FALLBACK_EMPTY', $res['model']);
        $this->assertEmpty($res['predictions']);
    }

    public function testFittedValuesMatchInputLength(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->forecaster->forecast($series, 4);

        $this->assertCount(count($series), $res['fitted']);
    }
}
