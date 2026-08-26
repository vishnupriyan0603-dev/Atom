<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\DynamicRangeCompressorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 88 — DynamicRangeCompressorEngine unit tests (6 tests).
 */
class DynamicRangeCompressorEngineTest extends TestCase
{
    private DynamicRangeCompressorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DynamicRangeCompressorEngine(new SecretRedactor());
    }

    public function testCompressReducesDynamicOvershoot(): void
    {
        $frames = [0.1, 0.5, 0.9, 0.95, -0.85, -0.99, 0.2];
        $res = $this->engine->compress($frames, -18.0, 4.0, 2.0);

        $this->assertTrue($res['success']);
        $this->assertCount(count($frames), $res['compressed_frames']);
        $this->assertGreaterThan(0.0, $res['max_gain_reduction_db']);
        $this->assertLessThanOrEqual(-0.10, $res['peak_after_db']);
    }

    public function testBroadcastVoicePresetAppliesParams(): void
    {
        $frames = [0.2, 0.7, 0.95, -0.8];
        $res = $this->engine->processPreset($frames, 'broadcast_voice');

        $this->assertTrue($res['success']);
        $this->assertSame(-18.0, $res['threshold_db']);
        $this->assertSame(4.0, $res['ratio']);
        $this->assertSame(3.5, $res['makeup_gain_db']);
    }

    public function testBrickwallLimiterPreventsDigitalClipping(): void
    {
        // Extreme loud samples that would ordinarily clip
        $frames = [2.0, 5.0, -10.0, 0.99];
        $res = $this->engine->processPreset($frames, 'brickwall_limiter');

        $this->assertTrue($res['success']);
        foreach ($res['compressed_frames'] as $s) {
            $this->assertLessThanOrEqual(0.988, $s);
            $this->assertGreaterThanOrEqual(-0.988, $s);
        }
    }

    public function testEmptyAudioFramesFailsGracefully(): void
    {
        $res = $this->engine->compress([]);
        $this->assertFalse($res['success']);
        $this->assertSame(0.0, $res['gain_reduction_db']);
    }

    public function testGetPresetsReturnsStudioProfiles(): void
    {
        $presets = $this->engine->getPresets();

        $this->assertArrayHasKey('broadcast_voice', $presets);
        $this->assertArrayHasKey('punchy_podcast', $presets);
        $this->assertArrayHasKey('vocal_leveler', $presets);
        $this->assertArrayHasKey('brickwall_limiter', $presets);
    }

    public function testLowLevelSignalsUnattenuatedBelowThreshold(): void
    {
        // -30dB signal is below -18dB threshold
        $quietFrames = [0.01, 0.02, -0.01];
        $res = $this->engine->compress($quietFrames, -18.0, 4.0, 0.0);

        $this->assertTrue($res['success']);
        $this->assertSame(0.0, $res['max_gain_reduction_db']);
    }
}
