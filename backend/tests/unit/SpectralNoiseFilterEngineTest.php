<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\SpectralNoiseFilterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 58 — SpectralNoiseFilterEngine unit tests (6 tests).
 */
class SpectralNoiseFilterEngineTest extends TestCase
{
    private SpectralNoiseFilterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SpectralNoiseFilterEngine(new SecretRedactor());
    }

    public function testDenoiseAudioFrameProducesCleanedSamples(): void
    {
        $samples = [0.1, 0.4, 0.8, -0.5, -0.2, 0.05, 0.9];
        $res = $this->engine->denoiseFrame($samples);

        $this->assertTrue($res['success']);
        $this->assertCount(count($samples), $res['cleaned_samples']);
        $this->assertGreaterThan(0.0, $res['noise_reduced_pct']);
    }

    public function testSnrGainIsPositiveForNoisySignal(): void
    {
        $samples = [];
        for ($i = 0; $i < 32; $i++) {
            $samples[] = sin($i * 0.3) + 0.1;
        }

        $res = $this->engine->denoiseFrame($samples);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan($res['snr_before_db'], $res['snr_after_db']);
        $this->assertGreaterThan(0.0, $res['snr_gain_db']);
    }

    public function testEmptySamplesFailsGracefully(): void
    {
        $res = $this->engine->denoiseFrame([]);

        $this->assertFalse($res['success']);
        $this->assertSame(0.0, $res['snr_before_db']);
    }

    public function testSnrCalculationPrecision(): void
    {
        $snr = $this->engine->calculateSnr(100.0, 1.0);
        $this->assertSame(20.0, $snr); // 10 * log10(100) = 20 dB
    }

    public function testZeroNoisePowerSnrHandling(): void
    {
        $snr = $this->engine->calculateSnr(1.0, 0.0);
        $this->assertSame(0.0, $snr);
    }

    public function testCustomFilterParametersBoundaries(): void
    {
        $this->engine->setFilterParameters(10.0, 0.9); // Exceeds upper limits
        $res = $this->engine->denoiseFrame([0.5, -0.5]);

        $this->assertTrue($res['success']);
        $this->assertSame(5.0, $res['filter_parameters']['over_subtraction_alpha']); // Clamped to 5.0
        $this->assertSame(0.2, $res['filter_parameters']['spectral_floor_beta']); // Clamped to 0.2
    }
}
