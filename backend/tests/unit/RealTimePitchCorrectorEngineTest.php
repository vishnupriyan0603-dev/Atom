<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\RealTimePitchCorrectorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 78 — RealTimePitchCorrectorEngine unit tests (6 tests).
 */
class RealTimePitchCorrectorEngineTest extends TestCase
{
    private RealTimePitchCorrectorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RealTimePitchCorrectorEngine(new SecretRedactor());
    }

    public function testAutotunePitchMajorScale(): void
    {
        $frames = [0.1, 0.4, 0.7, 0.3, -0.2, -0.6, 0.1];
        $res = $this->engine->autotunePitch($frames, 'major', 0.8);

        $this->assertTrue($res['success']);
        $this->assertSame('major', $res['scale']);
        $this->assertCount(count($frames), $res['tuned_frames']);
        $this->assertSame('PITCH_TUNED_OPTIMAL', $res['status']);
    }

    public function testTamilKalyaniRagaScaleTuning(): void
    {
        $frames = [0.2, 0.5, 0.8, -0.3];
        $res = $this->engine->autotunePitch($frames, 'tamil_kalyani', 1.0);

        $this->assertTrue($res['success']);
        $this->assertSame('tamil_kalyani', $res['scale']);
        $this->assertIsInt($res['target_semitone']);
    }

    public function testSynthesizeMultiVoiceHarmonies(): void
    {
        $frames = [0.2, 0.5, 0.8, -0.3, -0.5];
        $harmony = $this->engine->synthesizeHarmonies($frames, [4, 7]);

        $this->assertTrue($harmony['success']);
        $this->assertSame(3, $harmony['voices_count']);
        $this->assertCount(count($frames), $harmony['harmony_frames']);
    }

    public function testEmptyAudioFramesFailsGracefully(): void
    {
        $res = $this->engine->autotunePitch([]);
        $this->assertFalse($res['success']);

        $harm = $this->engine->synthesizeHarmonies([]);
        $this->assertFalse($harm['success']);
    }

    public function testAudioSampleClippingBounds(): void
    {
        $frames = [1.5, -2.0, 0.5];
        $res = $this->engine->autotunePitch($frames);

        foreach ($res['tuned_frames'] as $s) {
            $this->assertLessThanOrEqual(1.0, $s);
            $this->assertGreaterThanOrEqual(-1.0, $s);
        }
    }

    public function testGetSupportedScalesReturnsAllModes(): void
    {
        $scales = $this->engine->getSupportedScales();

        $this->assertArrayHasKey('major', $scales);
        $this->assertArrayHasKey('minor', $scales);
        $this->assertArrayHasKey('chromatic', $scales);
        $this->assertArrayHasKey('tamil_kalyani', $scales);
    }
}
