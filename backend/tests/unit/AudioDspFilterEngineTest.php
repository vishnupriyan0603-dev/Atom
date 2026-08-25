<?php

namespace Tests\Unit;

use Atom\Voice\AudioDspFilterEngine;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 — AudioDspFilterEngine unit tests (5 tests).
 */
class AudioDspFilterEngineTest extends TestCase
{
    private AudioDspFilterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AudioDspFilterEngine();
    }

    public function testGetFilterGraphReturnsWebAudioNodes(): void
    {
        $graph = $this->engine->getFilterGraph();
        $this->assertIsArray($graph);
        $this->assertEquals('active', $graph['engine_status']);
        $this->assertEquals(48000, $graph['sample_rate']);
        $this->assertArrayHasKey('biquad_nodes', $graph);
        $this->assertCount(10, $graph['biquad_nodes']);
    }

    public function testComputeFftSpectrumGeneratesPowerBins(): void
    {
        $spectrum = $this->engine->computeFftSpectrum();
        $this->assertIsArray($spectrum);
        $this->assertArrayHasKey('spectrum_bins', $spectrum);
        $this->assertCount(10, $spectrum['spectrum_bins']);
        $this->assertGreaterThan(0.0, $spectrum['snr_db']);
    }

    public function testApplyNoiseGateZerosSubThresholdAudio(): void
    {
        $this->engine->setNoiseGate(true, -30.0);
        $samples = [0.0001, -0.0002, 0.5, -0.8];
        $filtered = $this->engine->applyNoiseGate($samples);

        $this->assertEquals(0.0, $filtered[0]);
        $this->assertEquals(0.0, $filtered[1]);
        $this->assertEquals(0.5, $filtered[2]);
        $this->assertEquals(-0.8, $filtered[3]);
    }

    public function testSetNoiseGateClampsValues(): void
    {
        $this->engine->setNoiseGate(true, -120.0);
        $graph = $this->engine->getFilterGraph();
        $this->assertEquals(-90.0, $graph['noise_gate']['threshold_db']);
    }

    public function testSetAgcEnforcesSafetyThresholds(): void
    {
        $this->engine->setAgc(true, 5.0);
        $graph = $this->engine->getFilterGraph();
        $this->assertEquals(-6.0, $graph['agc']['target_rms']);
    }
}
