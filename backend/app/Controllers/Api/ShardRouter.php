<?php

namespace App\Controllers\Api;

use Atom\Database\ConsistentHashShardRouterEngine;

/**
 * ShardRouter API Controller — Phase 87
 */
class ShardRouter extends BaseApiController
{
    private static ?ConsistentHashShardRouterEngine $engine = null;

    private function getEngine(): ConsistentHashShardRouterEngine
    {
        if (self::$engine === null) {
            self::$engine = new ConsistentHashShardRouterEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/database/shards/nodes
     */
    public function nodes()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getRingStatus(), 'Shard ring topology retrieved');
    }

    /**
     * POST /api/database/shards/locate
     */
    public function locate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $key = $json['routing_key'] ?? 'tenant_enterprise_42';

        $engine = $this->getEngine();
        $res = $engine->locateShard($key);

        return $this->respondSuccess($res, 'Shard located for key');
    }

    /**
     * POST /api/database/shards/manage
     */
    public function manage()
    {
        $json = $this->request->getJSON(true) ?? [];
        $action = $json['action'] ?? 'add';
        $shardId = $json['shard_id'] ?? 'shard_delta';
        $host = $json['host'] ?? '10.0.10.4';
        $port = (int) ($json['port'] ?? 3306);
        $weight = (int) ($json['weight'] ?? 1);

        $engine = $this->getEngine();

        if ($action === 'remove') {
            $ok = $engine->removeShard($shardId);
            return $this->respondSuccess(['removed' => $ok, 'shard_id' => $shardId], 'Shard removed from ring');
        }

        $ok = $engine->addShard($shardId, $host, $port, $weight);
        return $this->respondSuccess(['added' => $ok, 'shard_id' => $shardId], 'Shard added to ring');
    }
}
