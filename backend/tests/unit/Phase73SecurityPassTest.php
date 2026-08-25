<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioStemSeparatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 73 — Phase73SecurityPassTest security & safety tests (5 tests).
 */
class Phase73SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testArithmeticStabilityNoNanOrInfiniteInSnr(): void
    {
        $engine = new AudioStemSeparatorEngine($this->redactor);
        $res = $engine->separateStems([0.0, 0.0, 0.0]);

        $this->assertFalse(is_nan($res['snr_db']));
        $this->assertFalse(is_infinite($res['snr_db']));
    }

    public function testHighVolumeAudioSampleSeparation(): void
    {
        $engine = new AudioStemSeparatorEngine($this->redactor);
        $largeFrames = array_fill(0, 1000, 0.45);

        $startTime = microtime(true);
        $res = $engine->separateStems($largeFrames);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertCount(1000, $res['vocal_stem']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testMixStemsBufferMismatchSafety(): void
    {
        $engine = new AudioStemSeparatorEngine($this->redactor);
        $vocal = [0.1, 0.2, 0.3, 0.4, 0.5];
        $inst = [0.1, 0.2]; // smaller buffer

        $res = $engine->mixStems($vocal, $inst);
        $this->assertTrue($res['success']);
        $this->assertSame(2, $res['mixed_samples_count']); // safely bounds to min length
    }

    public function testVocalPurityBoundedBetweenZeroAndHundred(): void
    {
        $engine = new AudioStemSeparatorEngine($this->redactor);
        $res = $engine->separateStems([0.9, -0.9]);

        $this->assertGreaterThanOrEqual(0.0, $res['vocal_purity_pct']);
        $this->assertLessThanOrEqual(100.0, $res['vocal_purity_pct']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/AudioStemSeparatorEngine.php',
            'src/Voice/AudioEmotionClassifierEngine.php',
            'src/Voice/PitchCorrectionHarmonizerEngine.php',
            'src/Voice/SpectralNoiseFilterEngine.php',
            'src/Voice/TamilReferenceVoiceEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
