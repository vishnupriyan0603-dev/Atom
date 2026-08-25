<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\CanaryTrafficSplitEngine;

/**
 * CanaryGovernor API Controller — Phase 71
 */
class CanaryGovernor extends BaseApiController
{
    private static ?CanaryTrafficSplitEngine $engine = null;

    private function getEngine(): CanaryTrafficSplitEngine
    {
        if (self::$engine === null) {
            self::$engine = new CanaryTrafficSplitEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/infrastructure/canary/status
     */
    public function status()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getStatus(), 'Canary status retrieved');
    }

    /**
     * POST /api/infrastructure/canary/route
     */
    public function route()
    {
        $json = $this->request->getJSON(true) ?? [];
        $reqId = $json['request_id'] ?? bin2hex(random_bytes(8));
        $tenant = $json['tenant_id'] ?? 'default';
        $headers = $json['headers'] ?? [];

        $engine = $this->getEngine();
        $route = $engine->routeRequest($reqId, $tenant, $headers);

        return $this->respondSuccess($route, 'Canary route resolved');
    }

    /**
     * POST /api/infrastructure/canary/update-weights
     */
    public function updateWeights()
    {
        $json = $this->request->getJSON(true) ?? [];
        $weight = (int) ($json['canary_weight_pct'] ?? 10);

        $engine = $this->getEngine();
        $engine->setCanaryWeight($weight);

        return $this->respondSuccess($engine->getStatus(), 'Canary weights updated');
    }

    /**
     * POST /api/infrastructure/canary/record-telemetry
     */
    public function recordTelemetry()
    {
        $json = $this->request->getJSON(true) ?? [];
        $success = (bool) ($json['success'] ?? true);

        $engine = $this->getEngine();
        $res = $engine->recordCanaryTelemetry($success);

        return $this->respondSuccess($res, 'Canary telemetry recorded');
    }
}
