<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebRtcFileTransferEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 66 — WebRtcFileTransferEngine unit tests (6 tests).
 */
class WebRtcFileTransferEngineTest extends TestCase
{
    private WebRtcFileTransferEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WebRtcFileTransferEngine(new SecretRedactor());
    }

    public function testPrepareTransferSplitsFileIntoChunks(): void
    {
        $payload = str_repeat('ABCDEF123456', 50); // 600 bytes
        $prep = $this->engine->prepareTransfer('test.txt', $payload, 100);

        $this->assertTrue($prep['success']);
        $this->assertSame(6, $prep['total_chunks']);
        $this->assertCount(6, $prep['chunks']);
        $this->assertSame(hash('sha256', $payload), $prep['checksum_sha256']);
    }

    public function testIngestAndReassembleEndToEnd(): void
    {
        $payload = "ATOM Platform High Speed P2P Data Channel Payload Test 2026";
        $prep = $this->engine->prepareTransfer('payload.bin', $payload, 16);

        $transferId = $prep['transfer_id'];
        foreach ($prep['chunks'] as $chunk) {
            $ingest = $this->engine->ingestChunk($transferId, $chunk['chunk_index'], $chunk['data'], $chunk['chunk_checksum']);
            $this->assertTrue($ingest['success']);
        }

        $reasm = $this->engine->reassembleFile($transferId);
        $this->assertTrue($reasm['success']);
        $this->assertTrue($reasm['checksum_verified']);
        $this->assertSame(strlen($payload), $reasm['file_size_bytes']);
    }

    public function testCorruptedChunkChecksumRejection(): void
    {
        $payload = "Valid file contents";
        $prep = $this->engine->prepareTransfer('test.bin', $payload);

        $transferId = $prep['transfer_id'];
        $badChecksum = hash('sha256', 'TamperedCorruptedContent');

        $ingest = $this->engine->ingestChunk($transferId, 0, $prep['chunks'][0]['data'], $badChecksum);
        $this->assertFalse($ingest['success']);
        $this->assertStringContainsString('checksum mismatch', $ingest['error']);
    }

    public function testReassembleIncompleteTransferFails(): void
    {
        $payload = str_repeat('DATA', 50);
        $prep = $this->engine->prepareTransfer('partial.bin', $payload, 32);

        $transferId = $prep['transfer_id'];
        // Ingest only first chunk
        $this->engine->ingestChunk($transferId, 0, $prep['chunks'][0]['data'], $prep['chunks'][0]['chunk_checksum']);

        $reasm = $this->engine->reassembleFile($transferId);
        $this->assertFalse($reasm['success']);
        $this->assertStringContainsString('Cannot reassemble: only received 1', $reasm['error']);
    }

    public function testEmptyFilePayloadFailsGracefully(): void
    {
        $prep = $this->engine->prepareTransfer('', '');
        $this->assertFalse($prep['success']);
    }

    public function testUnknownTransferSessionFails(): void
    {
        $reasm = $this->engine->reassembleFile('unknown_transfer_session');
        $this->assertFalse($reasm['success']);
    }
}
