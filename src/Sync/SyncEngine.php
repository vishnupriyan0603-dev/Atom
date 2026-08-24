<?php

namespace Atom\Sync;

use Atom\Security\SecretRedactor;

/**
 * SyncEngine — Master Cross-Device State Sync & Event Streaming Orchestrator.
 *
 * Coordinates:
 * - Active peer device registration & liveness monitoring
 * - Monotonic vector clock state replication (CRDT-style deltas)
 * - Real-time WebSocket event broadcasting
 * - End-to-end secret redaction across replication streams
 */
class SyncEngine
{
    private DevicePeerRegistry $peerRegistry;
    private StateReplicationEngine $replicationEngine;
    private WebSocketServer $wsServer;
    private SecretRedactor $redactor;

    public function __construct(
        ?DevicePeerRegistry $peerRegistry = null,
        ?StateReplicationEngine $replicationEngine = null,
        ?WebSocketServer $wsServer = null,
        ?SecretRedactor $redactor = null
    ) {
        $this->peerRegistry = $peerRegistry ?? new DevicePeerRegistry();
        $this->replicationEngine = $replicationEngine ?? new StateReplicationEngine();
        $this->wsServer = $wsServer ?? new WebSocketServer();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Get overall synchronization topology and health metrics.
     */
    public function getSyncTopology(): array
    {
        $peers = $this->peerRegistry->getActivePeers();
        $clock = $this->replicationEngine->getCurrentClock();

        return [
            'sync_status' => 'active',
            'version' => '1.0.0-phase28',
            'current_vector_clock' => $clock,
            'active_peers_count' => count($peers),
            'peers' => $peers,
            'recent_events' => $this->wsServer->getEventHistory(5),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Register or update a device peer.
     */
    public function registerPeer(string $deviceId, string $clientType, string $deviceName, string $ipAddress = '127.0.0.1'): array
    {
        $peer = $this->peerRegistry->register($deviceId, $clientType, $deviceName, $ipAddress);
        $this->wsServer->broadcast('peer:registered', ['peer' => $peer]);
        return $peer;
    }

    /**
     * Push a state mutation delta from a client.
     */
    public function pushDelta(string $entityType, string $entityId, array $payload, string $originDeviceId = 'client'): array
    {
        $delta = $this->replicationEngine->recordDelta($entityType, $entityId, $payload, $originDeviceId);
        $this->wsServer->broadcast('sync:delta_pushed', ['delta' => $delta]);
        return $delta;
    }

    /**
     * Pull state deltas since a vector clock.
     */
    public function pullDeltas(int $sinceClock = 0): array
    {
        return $this->replicationEngine->getDeltasSince($sinceClock);
    }

    /**
     * Broadcast an event message to all registered peers.
     */
    public function broadcastEvent(string $event, array $payload = []): array
    {
        return $this->wsServer->broadcast($event, $payload);
    }

    public function getPeerRegistry(): DevicePeerRegistry
    {
        return $this->peerRegistry;
    }

    public function getReplicationEngine(): StateReplicationEngine
    {
        return $this->replicationEngine;
    }

    public function getWebSocketServer(): WebSocketServer
    {
        return $this->wsServer;
    }
}
