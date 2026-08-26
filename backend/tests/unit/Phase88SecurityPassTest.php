<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\DynamicRangeCompressorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 88 — Phase88SecurityPassTest security & safety tests (5 tests).
 */
class Phase88SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testHighVolumeAudioCompressionThroughput(): void
    {
        $engine = new DynamicRangeCompressorEngine($this->redactor);
        $largeFrames = array_fill(0, 2000, 0.45);

        $startTime = microtime(true);
        $res = $engine->compress($largeFrames, -18.0, 4.0, 3.0);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertCount(2000, $res['compressed_frames']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testZeroOrSubnormalSamplesStabilityNoNan(): void
    {
        $engine = new DynamicRangeCompressorEngine($this->redactor);
        $res = $engine->compress([0.0, 0.0, 1e-15, -1e-15]);

        $this->assertTrue($res['success']);
        foreach ($res['compressed_frames'] as $sample) {
            $this->assertFalse(is_nan($sample));
            $this->assertFalse(is_infinite($sample));
        }
    }

    public function testExtremeParametersClampingSafety(): void
    {
        $engine = new DynamicRangeCompressorEngine($this->redactor);
        $res = $engine->compress([0.5], -100.0, 500.0, 999.0);

        $this->assertSame(-40.0, $res['threshold_db']); // clamped to -40
        $this->assertSame(20.0, $res['ratio']); // clamped to 20
        $this->assertSame(12.0, $res['makeup_gain_db']); // clamped to 12
    }

    public function testBrickwallPeakCeilingStrictEnforcement(): void
    {
        $engine = new DynamicRangeCompressorEngine($this->redactor);
        $res = $engine->compress([100.0, -100.0], -6.0, 10.0, 12.0);

        foreach ($res['compressed_frames'] as $sample) {
            $this->assertLessThanOrEqual(0.988, $sample);
            $this->assertGreaterThanOrEqual(-0.988, $sample);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/DynamicRangeCompressorEngine.php',
            'src/Voice/RealTimePitchCorrectorEngine.php',
            'src/Voice/AudioStemSeparatorEngine.php',
            'src/Voice/AudioEmotionClassifierEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
