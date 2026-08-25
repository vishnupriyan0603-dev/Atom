<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\PitchCorrectionHarmonizerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 62 — PitchCorrectionHarmonizerEngine unit tests (6 tests).
 */
class PitchCorrectionHarmonizerEngineTest extends TestCase
{
    private PitchCorrectionHarmonizerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PitchCorrectionHarmonizerEngine(new SecretRedactor());
    }

    public function testQuantizeToScaleLocksPitch(): void
    {
        $res = $this->engine->quantizeToScale(445.0, 'c_major'); // A4 is 440 Hz

        $this->assertSame(445.0, $res['original_freq_hz']);
        $this->assertSame(440.0, $res['target_freq_hz']);
        $this->assertSame(69, $res['midi_note']); // MIDI 69 = A4
        $this->assertGreaterThan(0.0, $res['detune_cents']);
    }

    public function testGenerateHarmoniesMultiVoice(): void
    {
        $res = $this->engine->generateHarmonies(245.0, [0, 4, 7, -12]);

        $this->assertTrue($res['success']);
        $this->assertCount(4, $res['voices']);
        $this->assertSame(245.0, $res['voices'][0]['frequency_hz']);
        $this->assertGreaterThan(245.0, $res['voices'][1]['frequency_hz']); // Major 3rd (+4st)
        $this->assertLessThan(245.0, $res['voices'][3]['frequency_hz']);    // Octave down (-12st)
    }

    public function testDetectPitchAutocorrelationProducesHz(): void
    {
        // Synthesize 200 Hz pure sine wave at 16000 Hz sample rate (period = 80 samples)
        $samples = [];
        for ($i = 0; $i < 256; $i++) {
            $samples[] = sin(2 * M_PI * 200 * ($i / 16000));
        }

        $pitch = $this->engine->detectPitch($samples, 16000);
        $this->assertGreaterThan(180.0, $pitch);
        $this->assertLessThan(220.0, $pitch);
    }

    public function testDetectPitchFallbackOnEmptySamples(): void
    {
        $pitch = $this->engine->detectPitch([]);
        $this->assertSame(0.0, $pitch);
    }

    public function testLowFrequencyQuantizeFallback(): void
    {
        $res = $this->engine->quantizeToScale(5.0);
        $this->assertSame(245.0, $res['original_freq_hz']);
    }

    public function testCustomSemitoneOffsetsHarmonies(): void
    {
        $res = $this->engine->generateHarmonies(440.0, [12, -24]);
        $this->assertTrue($res['success']);
        $this->assertSame(880.0, $res['voices'][0]['frequency_hz']); // +1 Octave = 880 Hz
        $this->assertSame(110.0, $res['voices'][1]['frequency_hz']); // -2 Octaves = 110 Hz
    }
}
