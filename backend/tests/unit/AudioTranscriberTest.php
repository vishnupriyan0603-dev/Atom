<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\Voice\AudioTranscriber;

/**
 * Phase 24 — AudioTranscriber unit tests (4 tests).
 */
class AudioTranscriberTest extends TestCase
{
    private AudioTranscriber $transcriber;

    protected function setUp(): void
    {
        $this->transcriber = new AudioTranscriber();
    }

    public function testTranscribeAudioPayloadSuccess(): void
    {
        $dummyPayload = base64_encode(str_repeat("\x00", 16000));
        $result = $this->transcriber->transcribe($dummyPayload, 'en', 'audio/wav');
        $this->assertTrue($result['success']);
        $this->assertSame('en', $result['language']);
        $this->assertGreaterThanOrEqual(1, $result['duration_est_sec']);
        $this->assertNotEmpty($result['text']);
    }

    public function testTranscribeRejectsEmptyPayload(): void
    {
        $result = $this->transcriber->transcribe('');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty', $result['error']);
    }

    public function testTranscribeLanguageTagging(): void
    {
        $dummyPayload = 'UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=';
        $result = $this->transcriber->transcribe($dummyPayload, 'ta');
        $this->assertTrue($result['success']);
        $this->assertSame('ta', $result['language']);
    }

    public function testConfidenceScoreReported(): void
    {
        $dummyPayload = 'UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=';
        $result = $this->transcriber->transcribe($dummyPayload);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }
}
