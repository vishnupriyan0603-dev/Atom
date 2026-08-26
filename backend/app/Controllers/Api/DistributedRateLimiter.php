<?php

namespace App\Controllers\Api;

use Atom\Security\DistributedRateLimiterMeshEngine;

/**
 * DistributedRateLimiter API Controller — Phase 99
 */
class DistributedRateLimiter extends BaseApiController
{
    private static ?DistributedRateLimiterMeshEngine $engine = null;

    private function getEngine(): DistributedRateLimiterMeshEngine
    {
        if (self::$engine === null) {
            self::$engine = new DistributedRateLimiterMeshEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/security/ratelimit/consume
     */
    public function consume()
    {
        $json = $this->request->getJSON(true) ?? [];
        $key = $json['client_key'] ?? 'api_key_usr_42';
        $cost = (int)($json['tokens_cost'] ?? 1);
        $tier = $json['tier'] ?? 'developer';

        $engine = $this->getEngine();
        $res = $engine->consume($key, $cost, $tier);

        return $this->respondSuccess($res, 'Rate limit token consumption evaluated');
    }

    /**
     * POST /api/security/ratelimit/sync
     */
    public function sync()
    {
        $json = $this->request->getJSON(true) ?? [];
        $nodeId = $json['node_id'] ?? 'node_edge_singapore';
        $deltas = $json['deltas'] ?? ['api_key_usr_42' => 2];

        $engine = $this->getEngine();
        $res = $engine->syncMeshNode($nodeId, $deltas);

        return $this->respondSuccess($res, 'Mesh node consumption synced');
    }

    /**
     * GET /api/security/ratelimit/mesh
     */
    public function mesh()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getMeshStats(), 'Distributed rate limiter mesh topology');
    }
}
