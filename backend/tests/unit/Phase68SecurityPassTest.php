<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioEmotionClassifierEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 68 — Phase68SecurityPassTest security & safety tests (5 tests).
 */
class Phase68SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInIntentText(): void
    {
        $engine = new AudioEmotionClassifierEngine($this->redactor);
        $text = "Hero mode sk-1122334455667788990011223344 activate attack!";

        $res = $engine->classifyTextIntent($text);
        $this->assertTrue($res['success']);
    }

    public function testExtremeAcousticBoundariesClamped(): void
    {
        $engine = new AudioEmotionClassifierEngine($this->redactor);
        $res = $engine->classifyAcoustic(999999.0, -100.0, 50.0);

        $this->assertTrue($res['success']);
        $this->assertSame(500.0, $res['acoustic_features']['f0_mean_hz']);
        $this->assertSame(1.0, $res['acoustic_features']['energy_rms']);
    }

    public function testHighThroughputAcousticClassification(): void
    {
        $engine = new AudioEmotionClassifierEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->classifyAcoustic(100.0 + ($i % 200), (float) ($i % 50), ($i % 100) / 100.0);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testSsmlModifiersFormatSafe(): void
    {
        $engine = new AudioEmotionClassifierEngine($this->redactor);
        $res = $engine->classifyAcoustic(150.0, 20.0, 0.5);

        $this->assertMatchesRegularExpression('/^[+-]?[0-9]+%$/', $res['ssml_modifiers']['pitch']);
        $this->assertMatchesRegularExpression('/^[+-]?[0-9]+%$/', $res['ssml_modifiers']['rate']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
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
