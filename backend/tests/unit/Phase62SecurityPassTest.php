<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\PitchCorrectionHarmonizerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 62 — Phase62SecurityPassTest security & safety tests (5 tests).
 */
class Phase62SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testQuantizeArithmeticSafetyNoNanOrInfinite(): void
    {
        $engine = new PitchCorrectionHarmonizerEngine($this->redactor);
        $res = $engine->quantizeToScale(0.0);

        $this->assertFalse(is_nan($res['target_freq_hz']));
        $this->assertFalse(is_infinite($res['target_freq_hz']));
        $this->assertFalse(is_nan($res['detune_cents']));
    }

    public function testExtremeFrequencyBoundsSafety(): void
    {
        $engine = new PitchCorrectionHarmonizerEngine($this->redactor);
        $resHigh = $engine->quantizeToScale(50000.0); // 50 kHz

        $this->assertGreaterThan(0.0, $resHigh['target_freq_hz']);
        $this->assertLessThan(100000.0, $resHigh['target_freq_hz']);
    }

    public function testAutocorrelationBufferSafety(): void
    {
        $engine = new PitchCorrectionHarmonizerEngine($this->redactor);
        $buffer = array_fill(0, 1024, 0.5);

        $startTime = microtime(true);
        $pitch = $engine->detectPitch($buffer);
        $duration = microtime(true) - $startTime;

        $this->assertGreaterThan(0.0, $pitch);
        $this->assertLessThan(1.0, $duration);
    }

    public function testHarmoniesVoicesPositiveGain(): void
    {
        $engine = new PitchCorrectionHarmonizerEngine($this->redactor);
        $res = $engine->generateHarmonies(245.0);

        foreach ($res['voices'] as $voice) {
            $this->assertGreaterThan(0.0, $voice['gain']);
            $this->assertLessThanOrEqual(1.0, $voice['gain']);
            $this->assertGreaterThan(0.0, $voice['frequency_hz']);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/PitchCorrectionHarmonizerEngine.php',
            'src/Voice/SpectralNoiseFilterEngine.php',
            'src/Voice/RealtimeFormantShifterEngine.php',
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
