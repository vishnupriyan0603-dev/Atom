<?php

namespace App\Controllers\Api;

use Atom\Daemon\ProactiveDaemon;
use Atom\Daemon\BriefingEngine;

/**
 * Daemon API Controller — Phase 25
 *
 * Endpoints:
 * - GET  /api/v1/daemon/status            — Live daemon status and pulse metrics
 * - POST /api/v1/daemon/pulse             — Execute an immediate daemon life-cycle pulse
 * - GET  /api/v1/daemon/briefing          — Get current briefing
 * - POST /api/v1/daemon/briefing/generate — Generate a fresh briefing
 * - GET  /api/v1/daemon/healing-log       — Auto-healing action log
 */
class Daemon extends BaseApiController
{
    private static ?ProactiveDaemon $daemonInstance = null;

    private function getDaemon(): ProactiveDaemon
    {
        if (self::$daemonInstance === null) {
            self::$daemonInstance = new ProactiveDaemon();
        }
        return self::$daemonInstance;
    }

    /**
     * GET /api/v1/daemon/status
     */
    public function status()
    {
        $daemon = $this->getDaemon();
        return $this->respondSuccess($daemon->getStatus(), 'Daemon status retrieved');
    }

    /**
     * POST /api/v1/daemon/pulse
     */
    public function pulse()
    {
        $daemon = $this->getDaemon();
        $pulseResult = $daemon->pulse();
        return $this->respondSuccess($pulseResult, 'Daemon life-cycle pulse executed');
    }

    /**
     * GET /api/v1/daemon/briefing
     */
    public function briefing()
    {
        $type = $this->request->getGet('type') ?? 'morning';
        $briefingEngine = new BriefingEngine();
        $briefing = $briefingEngine->generateBriefing($type);

        return $this->respondSuccess($briefing, 'Briefing retrieved');
    }

    /**
     * POST /api/v1/daemon/briefing/generate
     */
    public function generateBriefing()
    {
        $json = $this->request->getJSON(true) ?? [];
        $type = $json['type'] ?? 'morning';

        $briefingEngine = new BriefingEngine();
        $briefing = $briefingEngine->generateBriefing($type, $json);

        return $this->respondSuccess($briefing, 'Briefing generated successfully');
    }

    /**
     * GET /api/v1/daemon/healing-log
     */
    public function healingLog()
    {
        $daemon = $this->getDaemon();
        return $this->respondSuccess([
            'history' => $daemon->getHealingEngine()->getHealingHistory(),
        ], 'Healing log retrieved');
    }
}
