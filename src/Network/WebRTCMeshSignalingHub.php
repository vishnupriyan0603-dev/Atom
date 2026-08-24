<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * WebRTC Mesh Signaling Hub — Phase 37
 *
 * Peer registration, SDP Offer/Answer negotiation, and ICE candidate routing
 * for peer-to-peer (P2P) direct browser-to-desktop / browser-to-mobile mesh networks.
 */
class WebRTCMeshSignalingHub
{
    private array $peers = [];
    private array $signalingSessions = [];
    private array $iceCandidates = [];
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Registers a peer node in the P2P swarm mesh.
     */
    public function registerPeer(string $peerId, string $deviceType = 'desktop', array $capabilities = []): array
    {
        $peerId = trim($peerId);
        if (empty($peerId)) {
            throw new \InvalidArgumentException('Peer ID cannot be empty');
        }

        $peer = [
            'peer_id'      => $peerId,
            'device_type'  => $deviceType,
            'capabilities' => $capabilities,
            'status'       => 'ONLINE',
            'last_seen'    => microtime(true),
        ];

        $this->peers[$peerId] = $peer;
        return $peer;
    }

    /**
     * Posts an SDP Offer from initiator peer to target peer.
     */
    public function postOffer(string $fromPeerId, string $toPeerId, string $sdp): array
    {
        $sessionId = "sig_{$fromPeerId}_{$toPeerId}";
        $this->signalingSessions[$sessionId] = [
            'session_id'   => $sessionId,
            'from_peer'    => $fromPeerId,
            'to_peer'      => $toPeerId,
            'offer_sdp'    => $sdp,
            'answer_sdp'   => null,
            'status'       => 'OFFER_SENT',
            'timestamp'    => microtime(true),
        ];

        return $this->signalingSessions[$sessionId];
    }

    /**
     * Posts an SDP Answer responding to an offer.
     */
    public function postAnswer(string $sessionId, string $answerSdp): array
    {
        if (!isset($this->signalingSessions[$sessionId])) {
            throw new \InvalidArgumentException("Signaling session '{$sessionId}' not found");
        }

        $this->signalingSessions[$sessionId]['answer_sdp'] = $answerSdp;
        $this->signalingSessions[$sessionId]['status'] = 'ESTABLISHED';
        $this->signalingSessions[$sessionId]['timestamp'] = microtime(true);

        return $this->signalingSessions[$sessionId];
    }

    /**
     * Adds an ICE candidate for a peer session.
     */
    public function addIceCandidate(string $sessionId, string $peerId, array $candidate): void
    {
        if (!isset($this->iceCandidates[$sessionId])) {
            $this->iceCandidates[$sessionId] = [];
        }

        $this->iceCandidates[$sessionId][] = [
            'from_peer'  => $peerId,
            'candidate'  => $candidate,
            'timestamp'  => microtime(true),
        ];
    }

    /**
     * Retrieves all pending ICE candidates for a session.
     */
    public function getIceCandidates(string $sessionId): array
    {
        return $this->iceCandidates[$sessionId] ?? [];
    }

    /**
     * Lists active registered peers in the mesh.
     */
    public function listPeers(): array
    {
        return array_values($this->peers);
    }
}
