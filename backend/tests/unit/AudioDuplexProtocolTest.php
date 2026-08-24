<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioDuplexProtocol;

/**
 * Phase 34 — AudioDuplexProtocol unit tests (5 tests).
 */
class AudioDuplexProtocolTest extends TestCase
{
    private AudioDuplexProtocol $protocol;

    protected function setUp(): void
    {
        $this->protocol = new AudioDuplexProtocol();
    }

    public function testCreateAndParseValidFrame(): void
    {
        $payload = base64_encode('sample_audio_pcm_bytes');
        $frame = $this->protocol->createFrame(AudioDuplexProtocol::FRAME_CHUNK, 1, $payload);

        $this->assertSame('CHUNK', $frame['type']);
        $this->assertSame(1, $frame['sequence']);
        $this->assertSame(16000, $frame['sample_rate']);
        $this->assertNotEmpty($frame['checksum']);

        $parsed = $this->protocol->parseFrame($frame);
        $this->assertSame('CHUNK', $parsed['type']);
        $this->assertSame(1, $parsed['sequence']);
    }

    public function testParseJsonFormattedFrame(): void
    {
        $json = json_encode([
            'type'        => 'START',
            'sequence'    => 0,
            'payload'     => '',
            'sample_rate' => 16000,
            'channels'    => 1,
        ]);

        $parsed = $this->protocol->parseFrame($json);
        $this->assertSame('START', $parsed['type']);
        $this->assertSame(0, $parsed['sequence']);
    }

    public function testCorruptedChecksumFrameThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/checksum mismatch/');

        $frame = [
            'type'        => 'CHUNK',
            'sequence'    => 2,
            'payload'     => base64_encode('legitimate_audio'),
            'checksum'    => 'forged_corrupted_crc',
        ];

        $this->protocol->parseFrame($frame);
    }

    public function testInvalidFrameTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->protocol->createFrame('INVALID_TYPE', 1);
    }

    public function testExceedingMaxChunkBytesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Oversized payload exceeding 512 KB
        $huge = str_repeat('A', 600000);
        $this->protocol->createFrame('CHUNK', 1, $huge);
    }
}
