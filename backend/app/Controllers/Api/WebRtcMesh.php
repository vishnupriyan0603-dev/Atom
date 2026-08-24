<?php

namespace App\Controllers\Api;

use Atom\Network\WebRTCMeshSignalingHub;
use Atom\Network\DataChannelStreamProtocol;
use Atom\Network\P2PEdgeSwarmNode;
use Atom\Network\MeshConsensusProtocol;

/**
 * WebRTC P2P Edge Mesh API Controller — Phase 37
 *
 * Endpoints:
 * - POST /api/v1/webrtc/peer/register   — Register P2P peer node
 * - POST /api/v1/webrtc/sdp/offer       — Post SDP offer
 * - POST /api/v1/webrtc/sdp/answer      — Post SDP answer
 * - POST /api/v1/webrtc/ice/candidate   — Add ICE candidate
 * - POST /api/v1/webrtc/gossip/sync     — Sync anti-entropy state deltas
 * - GET  /api/v1/webrtc/topology        — List peers and cluster status
 */
class WebRtcMesh extends BaseApiController
{
    private static ?WebRTCMeshSignalingHub $signalingInstance = null;
    private static ?DataChannelStreamProtocol $dcInstance = null;
    private static ?P2PEdgeSwarmNode $nodeInstance = null;
    private static ?MeshConsensusProtocol $consensusInstance = null;

    private function getSignaling(): WebRTCMeshSignalingHub
    {
        if (self::$signalingInstance === null) {
            self::$signalingInstance = new WebRTCMeshSignalingHub();
        }
        return self::$signalingInstance;
    }

    private function getDataChannel(): DataChannelStreamProtocol
    {
        if (self::$dcInstance === null) {
            self::$dcInstance = new DataChannelStreamProtocol();
        }
        return self::$dcInstance;
    }

    private function getNode(): P2PEdgeSwarmNode
    {
        if (self::$nodeInstance === null) {
            self::$nodeInstance = new P2PEdgeSwarmNode('node_local_hub');
        }
        return self::$nodeInstance;
    }

    private function getConsensus(): MeshConsensusProtocol
    {
        if (self::$consensusInstance === null) {
            self::$consensusInstance = new MeshConsensusProtocol();
        }
        return self::$consensusInstance;
    }

    /**
     * POST /api/v1/webrtc/peer/register
     */
    public function registerPeer()
    {
        $json = $this->request->getJSON(true) ?? [];
        $peerId = $json['peer_id'] ?? ('peer_' . bin2hex(random_bytes(4)));
        $deviceType = $json['device_type'] ?? 'desktop';
        $capabilities = $json['capabilities'] ?? ['datachannel', 'audio_duplex'];

        try {
            $peer = $this->getSignaling()->registerPeer($peerId, $deviceType, $capabilities);
            return $this->respondSuccess($peer, 'Peer registered in WebRTC mesh');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/webrtc/sdp/offer
     */
    public function sdpOffer()
    {
        $json = $this->request->getJSON(true) ?? [];
        $from = $json['from_peer'] ?? 'peer_browser';
        $to = $json['to_peer'] ?? 'peer_desktop';
        $sdp = $json['sdp'] ?? 'v=0\r\no=atom 123 456 IN IP4 0.0.0.0\r\ns=Atom WebRTC Session\r\nt=0 0\r\n';

        $session = $this->getSignaling()->postOffer($from, $to, $sdp);
        return $this->respondSuccess($session, 'SDP Offer recorded');
    }

    /**
     * POST /api/v1/webrtc/sdp/answer
     */
    public function sdpAnswer()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sessionId = $json['session_id'] ?? '';
        $sdp = $json['sdp'] ?? 'v=0\r\no=atom 456 789 IN IP4 0.0.0.0\r\ns=Atom WebRTC Answer\r\nt=0 0\r\n';

        try {
            $session = $this->getSignaling()->postAnswer($sessionId, $sdp);
            return $this->respondSuccess($session, 'SDP Answer recorded, P2P connection established');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/webrtc/ice/candidate
     */
    public function iceCandidate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sessionId = $json['session_id'] ?? '';
        $peerId = $json['peer_id'] ?? '';
        $candidate = $json['candidate'] ?? ['candidate' => 'candidate:1 1 UDP 2130706431 192.168.1.100 50000 typ host'];

        $this->getSignaling()->addIceCandidate($sessionId, $peerId, $candidate);
        return $this->respondSuccess(['status' => 'queued'], 'ICE Candidate added');
    }

    /**
     * POST /api/v1/webrtc/gossip/sync
     */
    public function gossipSync()
    {
        $json = $this->request->getJSON(true) ?? [];
        $remoteDigest = $json['digest'] ?? [];
        $consensus = $this->getConsensus();

        // Compute local deltas to send back
        $outgoingDeltas = $consensus->computeDeltas($remoteDigest);

        // Merge any incoming remote deltas
        $incomingDeltas = $json['deltas'] ?? [];
        $applied = $consensus->mergeDeltas($incomingDeltas);

        return $this->respondSuccess([
            'local_digest'    => $consensus->generateDigest(),
            'outgoing_deltas' => $outgoingDeltas,
            'merged_count'    => $applied,
        ], 'P2P Gossip state synchronization complete');
    }

    /**
     * GET /api/v1/webrtc/topology
     */
    public function topology()
    {
        return $this->respondSuccess([
            'peers'           => $this->getSignaling()->listPeers(),
            'node_role'       => $this->getNode()->getRole(),
            'current_term'    => $this->getNode()->getCurrentTerm(),
            'consensus_state' => $this->getConsensus()->getState(),
        ], 'WebRTC mesh topology retrieved');
    }
}
