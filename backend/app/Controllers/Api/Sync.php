<?php

namespace App\Controllers\Api;

use App\Services\SyncService;
use Atom\Sync\SyncEngine;

/**
 * Sync API Controller — Phase 28
 *
 * Endpoints:
 * - GET  /api/v1/sync/peers     — List active connected device peers
 * - POST /api/v1/sync/register  — Register or heartbeat a device peer
 * - POST /api/v1/sync/push      — Push state mutation delta (or legacy batch push)
 * - POST /api/v1/sync/pull      — Pull state deltas since vector clock (or legacy pull)
 * - POST /api/v1/sync/broadcast — Broadcast real-time event message
 */
class Sync extends BaseApiController
{
    private SyncService $syncService;
    private static ?SyncEngine $syncEngineInstance = null;

    public function __construct()
    {
        $this->syncService = new SyncService();
    }

    private function getEngine(): SyncEngine
    {
        if (self::$syncEngineInstance === null) {
            self::$syncEngineInstance = new SyncEngine();
        }
        return self::$syncEngineInstance;
    }

    /**
     * GET /api/v1/sync/peers
     */
    public function peers()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getSyncTopology(), 'Peer topology retrieved');
    }

    /**
     * POST /api/v1/sync/register
     */
    public function register()
    {
        $json = $this->request->getJSON(true) ?? [];
        $deviceId = $json['device_id'] ?? uniqid('device_', true);
        $clientType = $json['client_type'] ?? 'desktop_wpf';
        $deviceName = $json['device_name'] ?? 'ATOM Client';
        $ip = $this->request->getIPAddress() ?: '127.0.0.1';

        $engine = $this->getEngine();
        $peer = $engine->registerPeer($deviceId, $clientType, $deviceName, $ip);

        return $this->respondSuccess($peer, 'Peer registered successfully');
    }

    /**
     * POST /api/v1/sync/pull (Legacy GET / POST compatible)
     */
    public function pull()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (isset($json['since_clock'])) {
            $sinceClock = (int) $json['since_clock'];
            $engine = $this->getEngine();
            return $this->respondSuccess($engine->pullDeltas($sinceClock), 'Deltas retrieved');
        }

        // Fallback to legacy syncService pull
        return $this->respondSuccess($this->syncService->pullAll());
    }

    /**
     * POST /api/v1/sync/push (Supports delta push & legacy batch push)
     */
    public function push()
    {
        $data = $this->request->getJSON(true) ?? [];
        if (empty($data)) {
            return $this->respondError('No data provided');
        }

        // Phase 28 Delta Sync Format
        if (isset($data['entity_type'])) {
            $entityType = $data['entity_type'];
            $entityId = $data['entity_id'] ?? uniqid('ent_', true);
            $payload = $data['payload'] ?? [];
            $originDeviceId = $data['device_id'] ?? 'client';

            $engine = $this->getEngine();
            $delta = $engine->pushDelta($entityType, $entityId, $payload, $originDeviceId);

            return $this->respondSuccess($delta, 'Delta recorded and broadcasted');
        }

        // Legacy Batch Format
        $results = [];
        if (!empty($data['chats'])) {
            foreach ($data['chats'] as $chat) {
                $results['chats'][] = $this->syncService->pushChat($chat);
            }
        }
        if (!empty($data['prompts'])) {
            foreach ($data['prompts'] as $prompt) {
                $results['prompts'][] = $this->syncService->pushPrompt($prompt);
            }
        }
        if (!empty($data['notes'])) {
            foreach ($data['notes'] as $note) {
                $results['notes'][] = $this->syncService->pushNote($note);
            }
        }

        return $this->respondCreated($results, 'Sync completed');
    }

    /**
     * POST /api/v1/sync/broadcast
     */
    public function broadcast()
    {
        $json = $this->request->getJSON(true) ?? [];
        $event = $json['event'] ?? 'system:notification';
        $payload = $json['payload'] ?? [];

        $engine = $this->getEngine();
        $result = $engine->broadcastEvent($event, $payload);

        return $this->respondSuccess($result, 'Event broadcasted to all peers');
    }
}
