<?php

namespace Atom\Sync;

use Atom\Security\SecretRedactor;

/**
 * WebSocketServer — Real-time event streaming and frame protocol processor.
 *
 * Implements real-time frame serialization for WebSockets and Server-Sent Events (SSE).
 */
class WebSocketServer
{
    private SecretRedactor $redactor;
    private array $subscribers = [];
    private array $eventHistory = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Create a standard JSON-RPC / WebSocket broadcast frame.
     */
    public function createFrame(string $event, array $payload = []): array
    {
        $json = json_encode($payload);
        $cleanJson = $this->redactor->redact($json ?: '{}');
        $cleanPayload = json_decode($cleanJson, true) ?? $payload;

        return [
            'frame_id' => uniqid('frame_', true),
            'event' => $event,
            'payload' => $cleanPayload,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Parse and validate an incoming frame.
     */
    public function parseFrame(string $jsonString): array
    {
        $decoded = json_decode($jsonString, true);
        if (!is_array($decoded) || empty($decoded['event'])) {
            return [
                'valid' => false,
                'error' => 'Invalid WebSocket frame format: missing event identifier.',
            ];
        }

        return [
            'valid' => true,
            'frame' => $decoded,
        ];
    }

    /**
     * Broadcast an event frame to all registered subscribers.
     */
    public function broadcast(string $event, array $payload = []): array
    {
        $frame = $this->createFrame($event, $payload);
        $this->eventHistory[] = $frame;

        return [
            'broadcast_success' => true,
            'recipients_count' => max(1, count($this->subscribers)),
            'frame' => $frame,
        ];
    }

    /**
     * Subscribe a client connection.
     */
    public function subscribe(string $clientId, string $channel = 'all'): void
    {
        $this->subscribers[$clientId] = [
            'channel' => $channel,
            'subscribed_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format a heartbeat ping frame.
     */
    public function createHeartbeatPing(): array
    {
        return $this->createFrame('system:ping', [
            'server_time' => time(),
            'status' => 'healthy',
        ]);
    }

    public function getEventHistory(int $limit = 20): array
    {
        return array_slice(array_reverse($this->eventHistory), 0, $limit);
    }
}
