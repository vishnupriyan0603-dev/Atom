<?php

use PHPUnit\Framework\TestCase;
use Atom\Analytics\SeasonalityDecomposer;

/**
 * Phase 38 — SeasonalityDecomposer unit tests (5 tests).
 */
class SeasonalityDecomposerTest extends TestCase
{
    private SeasonalityDecomposer $decomposer;

    protected function setUp(): void
    {
        $this->decomposer = new SeasonalityDecomposer();
    }

    public function testDecomposeGeneratesTrendSeasonalResidualComponents(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->decomposer->decompose($series, 7);

        $this->assertCount(count($series), $res['trend']);
        $this->assertCount(count($series), $res['seasonal']);
        $this->assertCount(count($series), $res['residuals']);
        $this->assertSame(7, $res['period']);
    }

    public function testAdditiveReconstructionMatchesOriginalSeries(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->decomposer->decompose($series, 7);

        for ($i = 0; $i < count($series); $i++) {
            $reconstructed = $res['trend'][$i] + $res['seasonal'][$i] + $res['residuals'][$i];
            $this->assertEqualsWithDelta($series[$i], $reconstructed, 0.01);
        }
    }

    public function testShortSeriesFallback(): void
    {
        $series = [1, 2, 3];
        $res = $this->decomposer->decompose($series, 7);

        $this->assertSame($series, $res['trend']);
        $this->assertCount(3, $res['seasonal']);
    }

    public function testSeasonalComponentRepeatsWithPeriodLength(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36];
        $res = $this->decomposer->decompose($series, 7);

        $this->assertEquals($res['seasonal'][0], $res['seasonal'][7]);
        $this->assertEquals($res['seasonal'][1], $res['seasonal'][8]);
    }

    public function testCustomPeriodDecomposition(): void
    {
        $series = [10, 12, 14, 16, 18, 20, 22, 24];
        $res = $this->decomposer->decompose($series, 4);

        $this->assertSame(4, $res['period']);
        $this->assertCount(count($series), $res['trend']);
    }
}
