<?php

namespace App\Controllers\Api;

use Atom\Database\DistributedCacheInvalidatorEngine;

/**
 * CacheInvalidator API Controller — Phase 70 Landmark Milestone
 */
class CacheInvalidator extends BaseApiController
{
    private static ?DistributedCacheInvalidatorEngine $engine = null;

    private function getEngine(): DistributedCacheInvalidatorEngine
    {
        if (self::$engine === null) {
            self::$engine = new DistributedCacheInvalidatorEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/database/cache/stats
     */
    public function stats()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getStats(), 'Cache metrics retrieved');
    }

    /**
     * POST /api/database/cache/set
     */
    public function setKey()
    {
        $json = $this->request->getJSON(true) ?? [];
        $key = $json['key'] ?? 'sample:key';
        $val = $json['value'] ?? ['data' => 'sample'];
        $ttl = (int) ($json['ttl'] ?? 300);
        $tenant = $json['tenant_id'] ?? 'default';
        $tags = $json['tags'] ?? [];

        $engine = $this->getEngine();
        $ok = $engine->set($key, $val, $ttl, $tenant, $tags);

        return $this->respondSuccess(['stored' => $ok, 'key' => $key], 'Key cached successfully');
    }

    /**
     * POST /api/database/cache/get
     */
    public function getKey()
    {
        $json = $this->request->getJSON(true) ?? [];
        $key = $json['key'] ?? '';

        $engine = $this->getEngine();
        $res = $engine->get($key);

        return $this->respondSuccess($res, 'Cache lookup performed');
    }

    /**
     * POST /api/database/cache/invalidate-tag
     */
    public function invalidateTag()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tag = $json['tag'] ?? '';
        $tenant = $json['tenant_id'] ?? null;

        $engine = $this->getEngine();
        $res = $engine->invalidateTag($tag, $tenant);

        return $this->respondSuccess($res, 'Tag invalidated across cache mesh');
    }
}
