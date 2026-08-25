<?php

namespace App\Controllers\Api;

use Atom\Config\FeatureFlagRolloutEngine;

/**
 * FeatureFlags API Controller — Phase 77
 */
class FeatureFlags extends BaseApiController
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
     * GET /api/config/flags/list
     */
    public function list()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getAllFlags(), 'Feature flags retrieved');
    }

    /**
     * POST /api/config/flags/evaluate
     */
    public function evaluate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $flag = $json['flag'] ?? 'beta_voice_cloning';
        $user = $json['user_id'] ?? 'user_alex';
        $tenant = $json['tenant_id'] ?? 'default';

        $engine = $this->getEngine();
        $res = $engine->evaluate($flag, $user, $tenant);

        return $this->respondSuccess($res, 'Feature flag evaluated');
    }

    /**
     * POST /api/config/flags/set
     */
    public function setFlag()
    {
        $json = $this->request->getJSON(true) ?? [];
        $key = $json['key'] ?? 'new_feature_key';
        $enabled = (bool) ($json['enabled'] ?? true);
        $pct = (int) ($json['rollout_pct'] ?? 100);
        $tenants = $json['whitelist_tenants'] ?? [];
        $users = $json['whitelist_users'] ?? [];

        $engine = $this->getEngine();
        $ok = $engine->setFlag($key, $enabled, $pct, $tenants, $users);

        return $this->respondSuccess(['stored' => $ok, 'flag' => $key], 'Feature flag updated');
    }
}
