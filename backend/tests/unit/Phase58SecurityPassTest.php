<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\SpectralNoiseFilterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 58 — Phase58SecurityPassTest security & safety tests (5 tests).
 */
class Phase58SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testAudioSampleBoundsNeverExceedRange(): void
    {
        $engine = new SpectralNoiseFilterEngine($this->redactor);
        $extremeSamples = [1.5, -2.0, 100.0, -99.0];

        $res = $engine->denoiseFrame($extremeSamples);
        $this->assertTrue($res['success']);

        foreach ($res['cleaned_samples'] as $sample) {
            $this->assertGreaterThanOrEqual(-1.0, $sample);
            $this->assertLessThanOrEqual(1.0, $sample);
        }
    }

    public function testArithmeticSafetyNoNanOrInfinite(): void
    {
        $engine = new SpectralNoiseFilterEngine($this->redactor);
        $tinySamples = [0.00000001, -0.00000001, 0.0];

        $res = $engine->denoiseFrame($tinySamples, 0.0);
        $this->assertTrue($res['success']);
        $this->assertFalse(is_nan($res['snr_after_db']));
        $this->assertFalse(is_infinite($res['snr_after_db']));
    }

    public function testLargeAudioBufferProcessingResilience(): void
    {
        $engine = new SpectralNoiseFilterEngine($this->redactor);
        $largeBuffer = array_fill(0, 1024, 0.25);

        $startTime = microtime(true);
        $res = $engine->denoiseFrame($largeBuffer);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testNoiseReductionPercentageBounded(): void
    {
        $engine = new SpectralNoiseFilterEngine($this->redactor);
        $res = $engine->denoiseFrame([0.1, 0.2, 0.3]);

        $this->assertGreaterThanOrEqual(0.0, $res['noise_reduced_pct']);
        $this->assertLessThanOrEqual(100.0, $res['noise_reduced_pct']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/SpectralNoiseFilterEngine.php',
            'src/Voice/RealtimeFormantShifterEngine.php',
            'src/Voice/AudioDuplexStreamSession.php',
            'src/Voice/TamilReferenceVoiceEngine.php',
            'src/Voice/TamilPhonemeEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
