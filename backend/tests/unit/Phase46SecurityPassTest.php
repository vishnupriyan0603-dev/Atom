<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\RealtimeFormantShifterEngine;
use Atom\Voice\AudioDuplexStreamSession;
use Atom\Security\SecretRedactor;

/**
 * Phase 46 — Phase46SecurityPassTest security & safety tests (5 tests).
 */
class Phase46SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testNanAndInfinitySampleClamping(): void
    {
        $engine = new RealtimeFormantShifterEngine(1.18, 1.12, 245.0, 16000, $this->redactor);
        // Feed extreme out-of-range samples (+999.0, -999.0)
        $extremeSamples = [999.0, -999.0, 50.0, -50.0];

        $result = $engine->processFrame($extremeSamples);

        $this->assertTrue($result['success']);
        foreach ($result['processed_samples'] as $sample) {
            $this->assertGreaterThanOrEqual(-1.0, $sample);
            $this->assertLessThanOrEqual(1.0, $sample);
            $this->assertFalse(is_nan($sample));
            $this->assertFalse(is_infinite($sample));
        }
    }

    public function testOversizedAudioPayloadClamping(): void
    {
        $session = new AudioDuplexStreamSession('sec_stream_01', null, $this->redactor, 50);
        $largeSampleArray = array_fill(0, 10000, 0.1); // 10,000 samples

        $result = $session->processIngressFrame($largeSampleArray);

        $this->assertTrue($result['success']);
        // Output samples returned to client are bounded to 512 for memory safety
        $this->assertLessThanOrEqual(512, count($result['processed_samples']));
    }

    public function testParameterBoundsSafety(): void
    {
        $engine = new RealtimeFormantShifterEngine(1.18, 1.12, 245.0, 16000, $this->redactor);
        // Attempt to set dangerous negative or excessive pitch
        $engine->tuneParameters([
            'pitch_scale' => -10.0,
            'formant_scale' => 999.0,
            'target_f0' => 99999.0,
        ]);

        $params = $engine->getParameters();

        // Must clamp to safe ranges
        $this->assertGreaterThanOrEqual(0.5, $params['pitch_scale']);
        $this->assertLessThanOrEqual(2.0, $params['formant_scale']);
        $this->assertLessThanOrEqual(500.0, $params['target_f0']);
    }

    public function testBargeInStateToggleIntegrity(): void
    {
        $session = new AudioDuplexStreamSession('sec_stream_02', null, $this->redactor);

        // Frame 1: Loud
        $res1 = $session->processIngressFrame(array_fill(0, 100, 0.9));
        $this->assertTrue($res1['barge_in_triggered']);

        // Frame 2: Quiet
        $res2 = $session->processIngressFrame(array_fill(0, 100, 0.001));
        $this->assertFalse($res2['barge_in_triggered']);
    }

    public function testNoDangerousEvalOrShellExecutionInVoiceSubsystem(): void
    {
        $files = [
            'src/Voice/RealtimeFormantShifterEngine.php',
            'src/Voice/AudioDuplexStreamSession.php',
            'src/Voice/TamilReferenceVoiceEngine.php',
            'src/Voice/TamilPhonemeEngine.php',
            'src/Voice/AudioDspFilterEngine.php',
            'src/Voice/AudioEqualizerEngine.php',
            'src/Voice/AudioDuplexProtocol.php',
            'src/Voice/WakeWordDetector.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
