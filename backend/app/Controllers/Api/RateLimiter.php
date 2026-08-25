<?php

namespace App\Controllers\Api;

use Atom\Auth\TokenBucketRateLimiterEngine;

/**
 * RateLimiter API Controller — Phase 56
 */
class RateLimiter extends BaseApiController
{
    private static ?TokenBucketRateLimiterEngine $engine = null;

    private function getEngine(): TokenBucketRateLimiterEngine
    {
        if (self::$engine === null) {
            self::$engine = new TokenBucketRateLimiterEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/rate-limiter/check
     */
    public function check()
    {
        $json = $this->request->getJSON(true) ?? [];
        $clientId = $json['client_id'] ?? 'client_anonymous';
        $tokens = (int) ($json['tokens'] ?? 1);
        $tier = $json['tier'] ?? 'default';

        $engine = $this->getEngine();
        $result = $engine->consume($clientId, $tokens, $tier);

        return $this->respondSuccess($result, 'Rate limit checked');
    }

    /**
     * GET /api/rate-limiter/metrics
     */
    public function metrics()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getMetrics(), 'Rate limiter metrics');
    }
}
