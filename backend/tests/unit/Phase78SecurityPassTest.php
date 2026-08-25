<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\RealTimePitchCorrectorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 78 — Phase78SecurityPassTest security & safety tests (5 tests).
 */
class Phase78SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testHighVolumePitchCorrectionThroughput(): void
    {
        $engine = new RealTimePitchCorrectorEngine($this->redactor);
        $largeFrames = array_fill(0, 1000, 0.35);

        $startTime = microtime(true);
        $res = $engine->autotunePitch($largeFrames, 'tamil_kalyani', 0.9);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertCount(1000, $res['tuned_frames']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testHarmonizerExtremeIntervalsSafety(): void
    {
        $engine = new RealTimePitchCorrectorEngine($this->redactor);
        $frames = [0.1, 0.5, -0.4];

        $res = $engine->synthesizeHarmonies($frames, [12, -24, 36]);
        $this->assertTrue($res['success']);
        $this->assertCount(3, $res['harmony_frames']);
    }

    public function testCorrectionSpeedClampingBetweenZeroAndOne(): void
    {
        $engine = new RealTimePitchCorrectorEngine($this->redactor);
        $res = $engine->autotunePitch([0.2, 0.4], 'major', 5.0);

        $this->assertSame(1.0, $res['correction_speed']);
    }

    public function testSemitoneValuesAreValidIntegers(): void
    {
        $engine = new RealTimePitchCorrectorEngine($this->redactor);
        $res = $engine->autotunePitch([0.3, 0.6], 'minor');

        $this->assertGreaterThanOrEqual(0, $res['detected_semitone']);
        $this->assertLessThanOrEqual(11, $res['detected_semitone']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/RealTimePitchCorrectorEngine.php',
            'src/Voice/AudioStemSeparatorEngine.php',
            'src/Voice/AudioEmotionClassifierEngine.php',
            'src/Voice/PitchCorrectionHarmonizerEngine.php',
            'src/Voice/SpectralNoiseFilterEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
