<?php

namespace App\Controllers\Api;

use Atom\Network\ReverseProxyLoadBalancerEngine;

/**
 * ReverseProxy API Controller — Phase 86
 */
class ReverseProxy extends BaseApiController
{
    private static ?ReverseProxyLoadBalancerEngine $engine = null;

    private function getEngine(): ReverseProxyLoadBalancerEngine
    {
        if (self::$engine === null) {
            self::$engine = new ReverseProxyLoadBalancerEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/network/proxy/upstreams
     */
    public function upstreams()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getUpstreamStatus(), 'Upstream proxy nodes retrieved');
    }

    /**
     * POST /api/network/proxy/route
     */
    public function route()
    {
        $json = $this->request->getJSON(true) ?? [];
        $ip = $json['client_ip'] ?? '192.168.1.100';
        $path = $json['path'] ?? '/api/v1/orders';

        $engine = $this->getEngine();
        $res = $engine->routeRequest($ip, $path);

        return $this->respondSuccess($res, 'Request routed to upstream proxy');
    }

    /**
     * POST /api/network/proxy/configure
     */
    public function configure()
    {
        $json = $this->request->getJSON(true) ?? [];
        $algo = $json['algorithm'] ?? null;
        $nodeId = $json['node_id'] ?? null;
        $healthy = isset($json['healthy']) ? (bool) $json['healthy'] : null;

        $engine = $this->getEngine();

        if ($algo !== null) {
            $engine->setAlgorithm($algo);
        }

        if ($nodeId !== null && $healthy !== null) {
            $engine->setNodeHealth($nodeId, $healthy);
        }

        return $this->respondSuccess($engine->getUpstreamStatus(), 'Proxy configuration updated');
    }
}
