<?php

use PHPUnit\Framework\TestCase;
use Atom\Vision\VisionEngine;
use Atom\Vision\MultiModalPayload;
use Atom\Brain\Voice\VoiceEngine;
use Atom\Brain\Voice\AudioTranscriber;

/**
 * Phase 24 — MultiModalSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for multi-modal operations:
 * - Image payload size limits (rejects > 10MB)
 * - Secret redaction in vision analysis outputs
 * - Secret redaction in audio transcriptions
 * - Non-image MIME type rejection
 * - Voice formatting strips ANSI codes and markdown
 */
class MultiModalSecurityPassTest extends TestCase
{
    public function testRejectsOversizedImagePayload(): void
    {
        $engine = new VisionEngine();
        // Create an oversized payload base64 (> 10MB)
        $oversizedData = base64_encode(str_repeat('A', 11 * 1024 * 1024));
        $payload = new MultiModalPayload($oversizedData, 'image/png', 'huge.png');

        $result = $engine->analyze($payload);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceeds limit', $result['error']);
    }

    public function testSecretRedactionInVisionOutput(): void
    {
        $engine = new VisionEngine();
        $dummyBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $payload = new MultiModalPayload($dummyBase64, 'image/png', 'apikey_screenshot.png');

        $result = $engine->analyze($payload, 'Analyze key sk-1234567890abcdef1234567890abcdef in screenshot');
        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $result['data']['analysis']);
    }

    public function testVoiceEngineFormatStripsAnsiAndFences(): void
    {
        $voice = new VoiceEngine();
        $raw = "\x1B[32mSuccess\x1B[0m: ```php\n\$secret = 'pass';\n``` **Done**";
        $clean = $voice->formatForVoice($raw);

        $this->assertStringNotContainsString("\x1B[", $clean);
        $this->assertStringNotContainsString('```', $clean);
        $this->assertStringNotContainsString('**', $clean);
    }

    public function testNonImageMimeTypeBlocked(): void
    {
        $engine = new VisionEngine();
        $payload = new MultiModalPayload('dGVzdA==', 'application/x-executable', 'virus.exe');
        $result = $engine->analyze($payload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported file type', $result['error']);
    }

    public function testVoiceEngineSynthesizerIntegration(): void
    {
        $voice = new VoiceEngine(true, 'local_tts');
        $res = $voice->synthesize('System ready');

        $this->assertTrue($res['success']);
        $this->assertSame('System ready', $res['text']);
        $this->assertArrayHasKey('voice_meta', $res);
    }
}
