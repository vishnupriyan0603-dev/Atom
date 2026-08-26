<?php

namespace App\Controllers\Api;

use Atom\Orchestration\SuperAgentCenturyMatrixEngine;

/**
 * CenturyMatrix API Controller — Phase 100 (Grand Century Landmark Finale)
 */
class CenturyMatrix extends BaseApiController
{
    private static ?SuperAgentCenturyMatrixEngine $engine = null;

    private function getEngine(): SuperAgentCenturyMatrixEngine
    {
        if (self::$engine === null) {
            self::$engine = new SuperAgentCenturyMatrixEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/orchestration/century/dispatch
     */
    public function dispatch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $prompt = $json['task_prompt'] ?? 'Execute full platform self-healing cross-check across all 100 phases';
        $initiator = $json['initiator'] ?? 'admin_super_user';
        $domains = $json['domains'] ?? [];

        $engine = $this->getEngine();
        $res = $engine->dispatchMatrix($prompt, $initiator, $domains);

        return $this->respondSuccess($res, 'Century Super-Agent Matrix workflow dispatched');
    }

    /**
     * GET /api/orchestration/century/status
     */
    public function status()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getCenturyPlatformStatus(), '100-Phase Grand Century Platform Status');
    }

    /**
     * GET /api/orchestration/century/subsystems
     */
    public function subsystems()
    {
        $engine = $this->getEngine();
        $status = $engine->getCenturyPlatformStatus();
        return $this->respondSuccess($status['subsystems'], '100-Phase Architectural Subsystems');
    }
}
