<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\FeatureFlagRolloutEngine;

/**
 * FeatureRollout API Controller — Phase 95
 */
class FeatureRollout extends BaseApiController
{
    private static ?FeatureFlagRolloutEngine $engine = null;

    private function getEngine(): FeatureFlagRolloutEngine
    {
        if (self::$engine === null) {
            self::$engine = new FeatureFlagRolloutEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/infrastructure/flags/evaluate
     */
    public function evaluate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $flagKey = $json['flag_key'] ?? 'quantum_encryption_handshake';
        $userId = $json['user_id'] ?? 'user_dev_42';
        $attributes = $json['attributes'] ?? ['role' => 'developer'];

        $engine = $this->getEngine();
        $res = $engine->evaluate($flagKey, $userId, $attributes);

        return $this->respondSuccess($res, 'Feature flag evaluated');
    }

    /**
     * GET /api/infrastructure/flags/list
     */
    public function list()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getAllFlags(), 'Active feature flags');
    }

    /**
     * POST /api/infrastructure/flags/toggle
     */
    public function toggle()
    {
        $json = $this->request->getJSON(true) ?? [];
        $flagKey = $json['flag_key'] ?? 'quantum_encryption_handshake';
        $enabled = isset($json['enabled']) ? (bool) $json['enabled'] : null;
        $rolloutPct = isset($json['rollout_pct']) ? (int) $json['rollout_pct'] : null;

        $engine = $this->getEngine();

        if ($enabled !== null) {
            $engine->toggleFlag($flagKey, $enabled);
        }

        if ($rolloutPct !== null) {
            $engine->setFlagRollout($flagKey, $rolloutPct);
        }

        return $this->respondSuccess($engine->getAllFlags(), 'Feature flag settings updated');
    }
}
