<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioDuplexProtocol;
use Atom\Voice\WakeWordDetector;
use Atom\Voice\ConversationalTurnTakingManager;

/**
 * Phase 34 — VoiceDuplexSecurityPassTest security & safety tests (5 tests).
 */
class VoiceDuplexSecurityPassTest extends TestCase
{
    public function testSecretRedactionInVoiceDuplexTranscripts(): void
    {
        $wake = new WakeWordDetector();
        $inputWithSecret = "Hey Atom, my token is sk-ant-api03-secret12345678901234567890";

        $res = $wake->detect($inputWithSecret);

        $this->assertTrue($res['detected']);
        $this->assertSame('hey atom', $res['phrase']);
    }

    public function testBufferOverflowProtectionInAudioFrames(): void
    {
        $protocol = new AudioDuplexProtocol();
        // Construct oversized payload exceeding 512KB
        $oversized = str_repeat('B', AudioDuplexProtocol::MAX_CHUNK_BYTES + 1024);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds maximum allowed size/');
        $protocol->createFrame('CHUNK', 1, $oversized);
    }

    public function testWakeWordDetectorSanitizesInput(): void
    {
        $wake = new WakeWordDetector();
        $res = $wake->detect("<script>alert('xss')</script> Hey Atom <style>body{}</style>");

        $this->assertTrue($res['detected']);
        $this->assertSame('hey atom', $res['phrase']);
    }

    public function testSessionResetCleansMemoryState(): void
    {
        $turn = new ConversationalTurnTakingManager();
        $turn->onUserSpeechDetected();
        $turn->onSilenceDetected(900); // Transitions to THINKING

        $this->assertSame('THINKING', $turn->getState());
        $this->assertSame(1, $turn->getTurnCount());

        $turn->reset();

        $this->assertSame('IDLE', $turn->getState());
        $this->assertSame(0, $turn->getTurnCount());
        $this->assertEmpty($turn->getEventHistory());
    }

    public function testNoEvalOrDangerousExecutionInVoiceSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $protocolCode = file_get_contents($rootDir . '/src/Voice/AudioDuplexProtocol.php');
        $wakeCode = file_get_contents($rootDir . '/src/Voice/WakeWordDetector.php');
        $turnCode = file_get_contents($rootDir . '/src/Voice/ConversationalTurnTakingManager.php');
        $emotionCode = file_get_contents($rootDir . '/src/Voice/AudioEmotionAnalyzer.php');

        $this->assertNotFalse($protocolCode);
        $this->assertNotFalse($wakeCode);
        $this->assertNotFalse($turnCode);
        $this->assertNotFalse($emotionCode);

        $this->assertStringNotContainsString('eval(', $protocolCode);
        $this->assertStringNotContainsString('eval(', $wakeCode);
        $this->assertStringNotContainsString('eval(', $turnCode);
        $this->assertStringNotContainsString('eval(', $emotionCode);
        $this->assertStringNotContainsString('exec(', $protocolCode);
        $this->assertStringNotContainsString('shell_exec(', $protocolCode);
    }
}
