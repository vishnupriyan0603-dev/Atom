<?php

use PHPUnit\Framework\TestCase;
use Atom\Network\WebRTCMeshSignalingHub;

/**
 * Phase 37 — WebRTCMeshSignalingHub unit tests (5 tests).
 */
class WebRTCMeshSignalingHubTest extends TestCase
{
    private WebRTCMeshSignalingHub $hub;

    protected function setUp(): void
    {
        $this->hub = new WebRTCMeshSignalingHub();
    }

    public function testRegisterPeerNode(): void
    {
        $peer = $this->hub->registerPeer('peer_desktop_01', 'desktop', ['audio_duplex']);

        $this->assertSame('peer_desktop_01', $peer['peer_id']);
        $this->assertSame('desktop', $peer['device_type']);
        $this->assertSame('ONLINE', $peer['status']);
    }

    public function testPostOfferAndAnswerHandshake(): void
    {
        $offer = $this->hub->postOffer('peer_a', 'peer_b', 'v=0\r\nsdp_offer');
        $this->assertSame('OFFER_SENT', $offer['status']);
        $sessionId = $offer['session_id'];

        $answer = $this->hub->postAnswer($sessionId, 'v=0\r\nsdp_answer');
        $this->assertSame('ESTABLISHED', $answer['status']);
        $this->assertSame('v=0\r\nsdp_answer', $answer['answer_sdp']);
    }

    public function testAddAndRetrieveIceCandidates(): void
    {
        $offer = $this->hub->postOffer('peer_a', 'peer_b', 'v=0\r\nsdp');
        $sessionId = $offer['session_id'];

        $candidate = ['candidate' => 'candidate:1 1 UDP 12345 192.168.1.50 5000 typ host'];
        $this->hub->addIceCandidate($sessionId, 'peer_a', $candidate);

        $candidates = $this->hub->getIceCandidates($sessionId);
        $this->assertCount(1, $candidates);
        $this->assertSame('peer_a', $candidates[0]['from_peer']);
    }

    public function testEmptyPeerIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->hub->registerPeer('   ');
    }

    public function testPostAnswerToUnknownSessionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->hub->postAnswer('non_existent_session_id', 'v=0\r\nsdp');
    }
}
