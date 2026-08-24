<?php

namespace Atom\Sync;

/**
 * DevicePeerRegistry — Tracks active connected client peers and device metadata.
 *
 * Supports peer registration for:
 * - 'desktop_wpf'     — C# WPF Desktop Client
 * - 'mobile_flutter'  — Flutter iOS/Android Client
 * - 'web_admin'       — PHP/JS Web Admin Control Plane
 * - 'cli_assistant'   — Terminal CLI Client
 */
class DevicePeerRegistry
{
    private array $peers = [];

    public function __construct()
    {
        // Register standard local peers by default
        $this->register('peer_desktop_01', 'desktop_wpf', 'ATOM Desktop (WPF)', '127.0.0.1');
        $this->register('peer_mobile_01', 'mobile_flutter', 'ATOM Mobile (Flutter)', '192.168.1.105');
        $this->register('peer_web_01', 'web_admin', 'ATOM Web Admin', '127.0.0.1');
    }

    /**
     * Register or update a device peer.
     */
    public function register(string $deviceId, string $clientType, string $deviceName, string $ipAddress = '127.0.0.1'): array
    {
        $peer = [
            'device_id' => $deviceId,
            'client_type' => $clientType,
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'status' => 'online',
            'registered_at' => $this->peers[$deviceId]['registered_at'] ?? date('Y-m-d H:i:s'),
            'last_heartbeat' => date('Y-m-d H:i:s'),
        ];

        $this->peers[$deviceId] = $peer;
        return $peer;
    }

    /**
     * Send heartbeat to keep a peer active.
     */
    public function heartbeat(string $deviceId): bool
    {
        if (isset($this->peers[$deviceId])) {
            $this->peers[$deviceId]['last_heartbeat'] = date('Y-m-d H:i:s');
            $this->peers[$deviceId]['status'] = 'online';
            return true;
        }
        return false;
    }

    /**
     * Get list of all registered peers.
     */
    public function getActivePeers(): array
    {
        return array_values($this->peers);
    }

    /**
     * Find a peer by device ID.
     */
    public function getPeer(string $deviceId): ?array
    {
        return $this->peers[$deviceId] ?? null;
    }

    /**
     * Unregister or remove a peer.
     */
    public function unregister(string $deviceId): bool
    {
        if (isset($this->peers[$deviceId])) {
            unset($this->peers[$deviceId]);
            return true;
        }
        return false;
    }
}
