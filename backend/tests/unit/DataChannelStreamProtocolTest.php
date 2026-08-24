<?php

use PHPUnit\Framework\TestCase;
use Atom\Network\DataChannelStreamProtocol;

/**
 * Phase 37 — DataChannelStreamProtocol unit tests (5 tests).
 */
class DataChannelStreamProtocolTest extends TestCase
{
    private DataChannelStreamProtocol $dc;

    protected function setUp(): void
    {
        $this->dc = new DataChannelStreamProtocol();
    }

    public function testFragmentAndReassemblePayload(): void
    {
        $originalPayload = str_repeat('ABCDEF123456', 5000); // 60,000 bytes
        $packets = $this->dc->fragment('stream_101', $originalPayload, 16384);

        $this->assertGreaterThan(1, count($packets));

        $result = null;
        foreach ($packets as $pkt) {
            $result = $this->dc->ingest($pkt);
        }

        $this->assertTrue($result['complete']);
        $this->assertSame($originalPayload, $result['payload']);
    }

    public function testSingleChunkPayloadCompletesImmediately(): void
    {
        $payload = 'Small hello message';
        $packets = $this->dc->fragment('stream_single', $payload, 32768);

        $this->assertCount(1, $packets);
        $res = $this->dc->ingest($packets[0]);

        $this->assertTrue($res['complete']);
        $this->assertSame($payload, $res['payload']);
    }

    public function testPartialChunkIngestionReturnsIncomplete(): void
    {
        $payload = str_repeat('X', 40000);
        $packets = $this->dc->fragment('stream_partial', $payload, 10000); // 4 chunks

        $res1 = $this->dc->ingest($packets[0]);
        $this->assertFalse($res1['complete']);
        $this->assertSame(1, $res1['received_chunks']);
        $this->assertSame(4, $res1['total_chunks']);
    }

    public function testOutofOrderReassemblyIsSorted(): void
    {
        $payload = 'Part0_Part1_Part2';
        $packets = $this->dc->fragment('stream_ooo', $payload, 6); // 3 chunks

        // Ingest in reverse order (2, 1, 0)
        $this->dc->ingest($packets[2]);
        $this->dc->ingest($packets[1]);
        $final = $this->dc->ingest($packets[0]);

        $this->assertTrue($final['complete']);
        $this->assertSame($payload, $final['payload']);
    }

    public function testPacketContainsChecksum(): void
    {
        $packets = $this->dc->fragment('stream_crc', 'test_data');
        $this->assertNotEmpty($packets[0]['checksum']);
    }
}
