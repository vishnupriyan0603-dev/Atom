<?php

namespace App\Controllers\Api;

use Atom\Security\GeoFencingFirewallEngine;

/**
 * GeoFirewall API Controller — Phase 64
 */
class GeoFirewall extends BaseApiController
{
    private static ?GeoFencingFirewallEngine $engine = null;

    private function getEngine(): GeoFencingFirewallEngine
    {
        if (self::$engine === null) {
            self::$engine = new GeoFencingFirewallEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/security/geofence/evaluate
     */
    public function evaluate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $ip = $json['ip'] ?? $this->request->getIPAddress();
        $mode = $json['mode'] ?? 'allowlist';

        $engine = $this->getEngine();
        $evaluation = $engine->evaluateAccess($ip, $mode);

        return $this->respondSuccess($evaluation, 'Geo-fence policy evaluated');
    }

    /**
     * POST /api/security/geofence/lookup
     */
    public function lookup()
    {
        $json = $this->request->getJSON(true) ?? [];
        $ip = $json['ip'] ?? '127.0.0.1';

        $engine = $this->getEngine();
        $geo = $engine->resolveIp($ip);

        return $this->respondSuccess($geo, 'IP geolocation resolved');
    }

    /**
     * GET /api/security/geofence/policy
     */
    public function policy()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getPolicy(), 'Geo-fence policy rules');
    }
}
