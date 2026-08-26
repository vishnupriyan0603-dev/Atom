<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\SpatialBinauralAudioEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 94 — SpatialBinauralAudioEngine unit tests (6 tests).
 */
class SpatialBinauralAudioEngineTest extends TestCase
{
    private SpatialBinauralAudioEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SpatialBinauralAudioEngine(new SecretRedactor());
    }

    public function testHardRightAzimuthHasLargerRightGain(): void
    {
        $frames = [0.5, 0.8, -0.5];
        $res = $this->engine->spatialize($frames, 90.0, 0.0, 1.0); // Hard right

        $this->assertTrue($res['success']);
        $this->assertGreaterThan($res['ild_left_gain'], $res['ild_right_gain']);
        $this->assertGreaterThan(0.0, $res['itd_delay_ms']);
    }

    public function testHardLeftAzimuthHasLargerLeftGain(): void
    {
        $frames = [0.5, 0.8, -0.5];
        $res = $this->engine->spatialize($frames, -90.0, 0.0, 1.0); // Hard left

        $this->assertTrue($res['success']);
        $this->assertGreaterThan($res['ild_right_gain'], $res['ild_left_gain']);
        $this->assertGreaterThan(0.0, $res['itd_delay_ms']);
    }

    public function testCenterAzimuthHasBalancedGainAndZeroItd(): void
    {
        $frames = [0.5, 0.8, -0.5];
        $res = $this->engine->spatialize($frames, 0.0, 0.0, 1.0); // Center

        $this->assertTrue($res['success']);
        $this->assertEqualsWithDelta($res['ild_left_gain'], $res['ild_right_gain'], 0.01);
        $this->assertSame(0.0, $res['itd_delay_ms']);
    }

    public function testDistanceAttenuationReducesOutputGain(): void
    {
        $frames = [0.8];
        $closeRes = $this->engine->spatialize($frames, 0.0, 0.0, 1.0);
        $farRes = $this->engine->spatialize($frames, 0.0, 0.0, 9.0); // 9 meters away (1/sqrt(9) = 1/3)

        $this->assertGreaterThan($farRes['distance_gain'], $closeRes['distance_gain']);
    }

    public function testEmptyFramesFailsGracefully(): void
    {
        $res = $this->engine->spatialize([]);
        $this->assertFalse($res['success']);
        $this->assertEmpty($res['left_channel']);
    }

    public function testGetPresetsReturnsSoundscapes(): void
    {
        $presets = $this->engine->getPresets();

        $this->assertArrayHasKey('front_center', $presets);
        $this->assertArrayHasKey('left_ear_close', $presets);
        $this->assertArrayHasKey('right_ear_close', $presets);
        $this->assertArrayHasKey('cinematic_far_right', $presets);
    }
}
