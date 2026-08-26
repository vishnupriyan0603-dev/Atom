<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\StreamFrameCompressorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 90 Landmark — StreamFrameCompressorEngine unit tests (6 tests).
 */
class StreamFrameCompressorEngineTest extends TestCase
{
    private StreamFrameCompressorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new StreamFrameCompressorEngine(new SecretRedactor());
    }

    public function testEncodeAndDecodeDeflateRoundTrip(): void
    {
        $payload = "ATOM Edge Network Stream Compression Payload. " . str_repeat("Repetitive data block 1234567890. ", 20);
        $encoded = $this->engine->encodeFrame($payload, 'deflate', 6);

        $this->assertTrue($encoded['success']);
        $this->assertGreaterThan(1.5, $encoded['compression_ratio']);
        $this->assertSame('deflate', $encoded['codec']);

        $decoded = $this->engine->decodeFrame($encoded['binary_frame']);
        $this->assertTrue($decoded['success']);
        $this->assertSame($payload, $decoded['payload']);
        $this->assertSame(strlen($payload), $decoded['original_bytes']);
    }

    public function testEncodeAndDecodeGzipRoundTrip(): void
    {
        $payload = '{"event":"telemetry_ping","node_id":"node_alpha_1","data":[10,20,30,40,50]}';
        $encoded = $this->engine->encodeFrame($payload, 'gzip');

        $this->assertTrue($encoded['success']);
        $this->assertSame('gzip', $encoded['codec']);

        $decoded = $this->engine->decodeFrame($encoded['binary_frame']);
        $this->assertTrue($decoded['success']);
        $this->assertSame($payload, $decoded['payload']);
    }

    public function testCorruptedMagicBytesFailsDecode(): void
    {
        $encoded = $this->engine->encodeFrame("Test payload data", 'deflate');
        $corruptedFrame = "\x00\x00" . substr($encoded['binary_frame'], 2);

        $decoded = $this->engine->decodeFrame($corruptedFrame);
        $this->assertFalse($decoded['success']);
        $this->assertSame('INVALID_MAGIC_SYNC_BYTES', $decoded['error']);
    }

    public function testCorruptedPayloadCrcIntegrityCheckRejection(): void
    {
        $encoded = $this->engine->encodeFrame("Valid string for integrity validation", 'raw');
        // Mutate last byte of payload data
        $corruptedFrame = substr($encoded['binary_frame'], 0, -1) . 'X';

        $decoded = $this->engine->decodeFrame($corruptedFrame);
        $this->assertFalse($decoded['success']);
        $this->assertSame('CRC32_INTEGRITY_CHECK_FAILED', $decoded['error']);
    }

    public function testEmptyPayloadRejection(): void
    {
        $res = $this->engine->encodeFrame('');
        $this->assertFalse($res['success']);
        $this->assertSame('', $res['frame_hex']);
    }

    public function testGetSupportedCodecsReturnsProfiles(): void
    {
        $codecs = $this->engine->getSupportedCodecs();

        $this->assertArrayHasKey('deflate', $codecs);
        $this->assertArrayHasKey('gzip', $codecs);
        $this->assertArrayHasKey('raw', $codecs);
    }
}
