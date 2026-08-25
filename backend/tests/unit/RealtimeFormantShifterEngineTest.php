<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\RealtimeFormantShifterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 46 — RealtimeFormantShifterEngine unit tests (6 tests).
 */
class RealtimeFormantShifterEngineTest extends TestCase
{
    private RealtimeFormantShifterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RealtimeFormantShifterEngine(1.18, 1.12, 245.0, 16000, new SecretRedactor());
    }

    public function testProcessFrameArraySamples(): void
    {
        $samples = [];
        for ($i = 0; $i < 128; $i++) {
            $samples[] = sin($i * 0.2) * 0.5;
        }

        $result = $this->engine->processFrame($samples);

        $this->assertTrue($result['success']);
        $this->assertSame(128, $result['sample_count']);
        $this->assertSame(16000, $result['sample_rate']);
        $this->assertTrue($result['is_voice_active']);
        $this->assertGreaterThan(0.0, $result['rms_energy']);
        $this->assertNotEmpty($result['fft_spectrum']);
    }

    public function testProcessBinaryPcmChunk(): void
    {
        // 64 samples of 16-bit PCM (128 bytes)
        $rawPcm = pack('s*', ...array_fill(0, 64, 15000));
        $result = $this->engine->processFrame($rawPcm);

        $this->assertTrue($result['success']);
        $this->assertSame(64, $result['sample_count']);
        $this->assertNotEmpty($result['processed_samples']);
    }

    public function testDynamicParameterTuning(): void
    {
        $this->engine->tuneParameters([
            'pitch_scale' => 1.25,
            'formant_scale' => 1.15,
            'target_f0' => 260.0,
        ]);

        $params = $this->engine->getParameters();

        $this->assertSame(1.25, $params['pitch_scale']);
        $this->assertSame(1.15, $params['formant_scale']);
        $this->assertSame(260.0, $params['target_f0']);
        $this->assertSame(680.0 * 1.15, $params['formant_filters']['F1']);
    }

    public function testSilenceVoiceActivityDetection(): void
    {
        $silentSamples = array_fill(0, 128, 0.0);
        $result = $this->engine->processFrame($silentSamples);

        $this->assertFalse($result['is_voice_active']);
        $this->assertSame(0.0, $result['rms_energy']);
    }

    public function testEmptyChunkFallbackGraceful(): void
    {
        $result = $this->engine->processFrame([]);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['sample_count']);
    }

    public function testFftSpectrumBandsCountAndBounds(): void
    {
        $samples = array_fill(0, 256, 0.3);
        $result = $this->engine->processFrame($samples);

        $this->assertCount(16, $result['fft_spectrum']);
        foreach ($result['fft_spectrum'] as $band) {
            $this->assertGreaterThanOrEqual(0.0, $band);
            $this->assertLessThanOrEqual(1.0, $band);
        }
    }
}
