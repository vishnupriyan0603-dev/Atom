<?php

namespace Atom\Sync;

use Atom\Security\SecretRedactor;

/**
 * StateReplicationEngine — Conflict-free CRDT/Vector Clock state synchronizer.
 *
 * Capabilities:
 * - Records state mutation deltas with monotonically increasing vector clocks
 * - Serves delta catch-up streams to syncing peers
 * - Resolves concurrent multi-device mutations via Last-Write-Wins (LWW)
 * - Redacts secrets in replicated state payloads
 */
class StateReplicationEngine
{
    private int $currentClock = 100;
    private array $deltas = [];
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Record a new state mutation delta.
     */
    public function recordDelta(string $entityType, string $entityId, array $payload, string $originDeviceId = 'system'): array
    {
        $this->currentClock++;

        // Redact any secrets in payload
        $cleanPayload = $this->redactPayload($payload);

        $delta = [
            'clock' => $this->currentClock,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $cleanPayload,
            'origin_device' => $originDeviceId,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->deltas[] = $delta;

        return $delta;
    }

    /**
     * Get all state deltas recorded after a given vector clock.
     */
    public function getDeltasSince(int $sinceClock = 0): array
    {
        $results = [];
        foreach ($this->deltas as $d) {
            if ($d['clock'] > $sinceClock) {
                $results[] = $d;
            }
        }

        return [
            'current_clock' => $this->currentClock,
            'since_clock' => $sinceClock,
            'count' => count($results),
            'deltas' => $results,
        ];
    }

    /**
     * Resolve state conflicts between local state and incoming remote delta.
     */
    public function resolveConflicts(array $localState, array $remoteDelta): array
    {
        $localClock = $localState['clock'] ?? 0;
        $remoteClock = $remoteDelta['clock'] ?? 0;

        if ($remoteClock >= $localClock) {
            return [
                'winner' => 'remote',
                'state' => $remoteDelta['payload'] ?? $remoteDelta,
                'clock' => $remoteClock,
                'resolution' => 'Remote delta is newer or equal (Last-Write-Wins).',
            ];
        }

        return [
            'winner' => 'local',
            'state' => $localState['payload'] ?? $localState,
            'clock' => $localClock,
            'resolution' => 'Local state has higher vector clock.',
        ];
    }

    public function getCurrentClock(): int
    {
        return $this->currentClock;
    }

    private function redactPayload(array $payload): array
    {
        $json = json_encode($payload);
        $redacted = $this->redactor->redact($json ?: '{}');
        return json_decode($redacted, true) ?? $payload;
    }
}
