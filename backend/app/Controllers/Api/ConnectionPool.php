<?php

namespace App\Controllers\Api;

use Atom\Database\ConnectionPoolGovernorEngine;

/**
 * ConnectionPool API Controller — Phase 79
 */
class ConnectionPool extends BaseApiController
{
    private static ?ConnectionPoolGovernorEngine $engine = null;

    private function getEngine(): ConnectionPoolGovernorEngine
    {
        if (self::$engine === null) {
            self::$engine = new ConnectionPoolGovernorEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/database/pool/status
     */
    public function status()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getPoolStatus(), 'Connection pool status retrieved');
    }

    /**
     * POST /api/database/pool/lease
     */
    public function lease()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tenant = $json['tenant_id'] ?? 'default';
        $ctx = $json['context'] ?? 'api_request';

        $engine = $this->getEngine();
        $res = $engine->leaseConnection($tenant, $ctx);

        return $this->respondSuccess($res, 'Connection leased');
    }

    /**
     * POST /api/database/pool/release
     */
    public function release()
    {
        $json = $this->request->getJSON(true) ?? [];
        $handleId = $json['handle_id'] ?? '';

        $engine = $this->getEngine();
        $ok = $engine->releaseConnection($handleId);

        return $this->respondSuccess(['released' => $ok, 'handle_id' => $handleId], 'Connection released');
    }

    /**
     * POST /api/database/pool/reclaim-leaks
     */
    public function reclaimLeaks()
    {
        $engine = $this->getEngine();
        $res = $engine->reclaimLeakedConnections();

        return $this->respondSuccess($res, 'Leaked connections reclaimed');
    }
}
