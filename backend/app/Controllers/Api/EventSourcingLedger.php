<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\EventSourcingCqrsLedgerEngine;

/**
 * EventSourcingLedger API Controller — Phase 102
 */
class EventSourcingLedger extends BaseApiController
{
    private static ?EventSourcingCqrsLedgerEngine $engine = null;

    private function getEngine(): EventSourcingCqrsLedgerEngine
    {
        if (self::$engine === null) {
            self::$engine = new EventSourcingCqrsLedgerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/v1/infrastructure/events/dispatch
     * Dispatches a command and appends an immutable event to the ledger stream.
     */
    public function dispatchCommand()
    {
        $json = $this->request->getJSON(true) ?? [];
        $aggregateId = trim($json['aggregate_id'] ?? 'workspace-atom-core');
        $commandType = trim($json['command_type'] ?? ($json['command'] ?? ''));
        $payload = $json['payload'] ?? [];
        $expectedVersion = (int)($json['expected_version'] ?? 0);

        if (empty($commandType)) {
            return $this->respondError('Command type is required', 400);
        }

        $engine = $this->getEngine();
        $res = $engine->dispatchCommand($aggregateId, $commandType, $payload, $expectedVersion);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, "Command {$commandType} dispatched and recorded in event stream");
        }

        $statusCode = !empty($res['conflict']) ? 409 : 400;
        return $this->respondError($res['error'] ?? 'Command dispatch failed', $statusCode, $res);
    }

    /**
     * GET /api/v1/infrastructure/events/stream
     * Returns the event stream for a specific aggregate.
     */
    public function stream()
    {
        $aggregateId = $this->request->getGet('aggregate_id') ?? 'workspace-atom-core';
        $fromVersion = (int)($this->request->getGet('from_version') ?? 1);
        $toVersion = $this->request->getGet('to_version') !== null ? (int)$this->request->getGet('to_version') : null;

        $engine = $this->getEngine();
        $res = $engine->getEventStream($aggregateId, $fromVersion, $toVersion);

        return $this->respondSuccess($res, 'Event stream retrieved');
    }

    /**
     * POST /api/v1/infrastructure/events/timetravel
     * Reconstructs historical state at target version.
     */
    public function timeTravel()
    {
        $json = $this->request->getJSON(true) ?? [];
        $aggregateId = trim($json['aggregate_id'] ?? 'workspace-atom-core');
        $version = (int)($json['version'] ?? 1);

        $engine = $this->getEngine();
        $res = $engine->timeTravelToVersion($aggregateId, $version);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, "State reconstructed at version {$version}");
        }

        return $this->respondError($res['error'] ?? 'Time-travel reconstruction failed', 400);
    }

    /**
     * GET /api/v1/infrastructure/events/verify
     * Verifies SHA-256 cryptographic chain integrity of an aggregate ledger.
     */
    public function verify()
    {
        $aggregateId = $this->request->getGet('aggregate_id') ?? 'workspace-atom-core';

        $engine = $this->getEngine();
        $res = $engine->verifyLedgerIntegrity($aggregateId);

        return $this->respondSuccess($res, 'Ledger cryptographic integrity verified');
    }

    /**
     * GET /api/v1/infrastructure/events/projections
     * Returns all live materialized view projections.
     */
    public function projections()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getProjections(), 'Materialized projections retrieved');
    }
}
