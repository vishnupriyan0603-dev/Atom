<?php

namespace App\Controllers\Api;

use Atom\Orchestration\UnifiedPlatformGatewayCrossbar;
use Atom\Orchestration\PlatformSentinelAggregator;

/**
 * CommandCenter API Controller — Phase 50 (The Grand Milestone)
 */
class CommandCenter extends BaseApiController
{
    private static ?UnifiedPlatformGatewayCrossbar $crossbar = null;
    private static ?PlatformSentinelAggregator $sentinel = null;

    private function getCrossbar(): UnifiedPlatformGatewayCrossbar
    {
        if (self::$crossbar === null) {
            self::$crossbar = new UnifiedPlatformGatewayCrossbar();
        }
        return self::$crossbar;
    }

    private function getSentinel(): PlatformSentinelAggregator
    {
        if (self::$sentinel === null) {
            self::$sentinel = new PlatformSentinelAggregator($this->getCrossbar());
        }
        return self::$sentinel;
    }

    /**
     * GET /api/command-center/platform-status
     */
    public function platformStatus()
    {
        $status = $this->getCrossbar()->getPlatformStatus();
        return $this->respondSuccess($status, 'Platform-wide status matrix');
    }

    /**
     * POST /api/command-center/dispatch
     */
    public function dispatch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $cmd = $json['command'] ?? 'status';
        $payload = $json['payload'] ?? [];

        $res = $this->getCrossbar()->dispatchCommand($cmd, $payload);
        return $this->respondSuccess($res, 'Crossbar command dispatched');
    }

    /**
     * POST /api/command-center/run-diagnostics
     */
    public function runDiagnostics()
    {
        $diagnostics = $this->getSentinel()->runDiagnostics();
        return $this->respondSuccess($diagnostics, 'Platform diagnostics complete');
    }

    /**
     * POST /api/command-center/heal
     */
    public function heal()
    {
        $healResult = $this->getSentinel()->healPlatform();
        return $this->respondSuccess($healResult, 'Autonomous self-healing executed');
    }
}
