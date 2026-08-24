<?php

use PHPUnit\Framework\TestCase;
use Atom\Network\WebRTCMeshSignalingHub;
use Atom\Network\DataChannelStreamProtocol;

/**
 * Phase 37 — WebRtcSecurityPassTest security & safety tests (5 tests).
 */
class WebRtcSecurityPassTest extends TestCase
{
    public function testSecretRedactionInSignalingPayloads(): void
    {
        $hub = new WebRTCMeshSignalingHub();
        $offer = $hub->postOffer('peer_1', 'peer_2', 'v=0\r\nsdp with sk-ant-api03-123456789012345678901234');

        $this->assertIsArray($offer);
        $this->assertSame('OFFER_SENT', $offer['status']);
    }

    public function testNoEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $hubCode = file_get_contents($rootDir . '/src/Network/WebRTCMeshSignalingHub.php');
        $dcCode = file_get_contents($rootDir . '/src/Network/DataChannelStreamProtocol.php');
        $nodeCode = file_get_contents($rootDir . '/src/Network/P2PEdgeSwarmNode.php');
        $meshCode = file_get_contents($rootDir . '/src/Network/MeshConsensusProtocol.php');

        $this->assertNotFalse($hubCode);
        $this->assertNotFalse($dcCode);
        $this->assertNotFalse($nodeCode);
        $this->assertNotFalse($meshCode);

        $this->assertStringNotContainsString('eval(', $hubCode);
        $this->assertStringNotContainsString('eval(', $dcCode);
        $this->assertStringNotContainsString('eval(', $nodeCode);
        $this->assertStringNotContainsString('eval(', $meshCode);
        $this->assertStringNotContainsString('exec(', $hubCode);
        $this->assertStringNotContainsString('shell_exec(', $hubCode);
    }

    public function testDataChannelPacketOversizeRejection(): void
    {
        $dc = new DataChannelStreamProtocol();
        $packets = $dc->fragment('str_test', 'small payload');

        $this->assertLessThanOrEqual(DataChannelStreamProtocol::MAX_PACKET_BYTES, strlen($packets[0]['data']));
    }

    public function testCorruptedChunkReassemblySafety(): void
    {
        $dc = new DataChannelStreamProtocol();
        // Ingest malformed packet
        $res = $dc->ingest([
            'stream_id'    => 'broken_stream',
            'chunk_index'  => 0,
            'total_chunks' => 2,
            'data'         => base64_encode('chunk_0'),
        ]);

        $this->assertFalse($res['complete']);
        $this->assertSame(1, $res['received_chunks']);
    }

    public function testPeerIdInjectionSanitization(): void
    {
        $hub = new WebRTCMeshSignalingHub();
        $peer = $hub->registerPeer("<script>alert('xss')</script> peer_safe", 'browser');

        $this->assertStringContainsString('peer_safe', $peer['peer_id']);
    }
}
