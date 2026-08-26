<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\SpatialBinauralAudioEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 94 — Phase94SecurityPassTest security & safety tests (5 tests).
 */
class Phase94SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testHighThroughputBinauralSpatialization(): void
    {
        $engine = new SpatialBinauralAudioEngine($this->redactor);
        $monoFrames = array_fill(0, 1000, 0.5);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->spatialize($monoFrames, ($i % 360) - 180, 0.0, 1.5);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testCoordinateClampingSafety(): void
    {
        $engine = new SpatialBinauralAudioEngine($this->redactor);
        $res = $engine->spatialize([0.5], 999.0, -999.0, 0.001); // extreme values

        $this->assertSame(180.0, $res['azimuth_deg']); // clamped to 180
        $this->assertSame(-90.0, $res['elevation_deg']); // clamped to -90
        $this->assertSame(0.2, $res['distance_m']); // clamped to min 0.2m
    }

    public function testNoNanOrInfiniteInStereoOutput(): void
    {
        $engine = new SpatialBinauralAudioEngine($this->redactor);
        $res = $engine->spatialize([0.0, 0.0, 1e-12, -1e-12], 45.0, 0.0, 1.0);

        $this->assertTrue($res['success']);
        foreach ($res['left_channel'] as $s) {
            $this->assertFalse(is_nan($s));
            $this->assertFalse(is_infinite($s));
        }
        foreach ($res['right_channel'] as $s) {
            $this->assertFalse(is_nan($s));
            $this->assertFalse(is_infinite($s));
        }
    }

    public function testPresetIntegrityAndSafety(): void
    {
        $engine = new SpatialBinauralAudioEngine($this->redactor);
        $res = $engine->spatializePreset([0.5, -0.5], 'cinematic_far_right');

        $this->assertTrue($res['success']);
        $this->assertSame(60.0, $res['azimuth_deg']);
        $this->assertSame(4.0, $res['distance_m']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/SpatialBinauralAudioEngine.php',
            'src/Voice/DynamicRangeCompressorEngine.php',
            'src/Voice/RealTimePitchCorrectorEngine.php',
            'src/Voice/AudioStemSeparatorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
