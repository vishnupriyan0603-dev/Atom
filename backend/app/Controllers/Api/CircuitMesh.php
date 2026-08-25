<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\DynamicCircuitBreakerEngine;

/**
 * CircuitMesh API Controller — Phase 85
 */
class CircuitMesh extends BaseApiController
{
    private static ?DynamicCircuitBreakerEngine $engine = null;

    private function getEngine(): DynamicCircuitBreakerEngine
    {
        if (self::$engine === null) {
            self::$engine = new DynamicCircuitBreakerEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/infrastructure/circuit/services
     */
    public function services()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getAllCircuits(), 'Circuit breaker statuses retrieved');
    }

    /**
     * POST /api/infrastructure/circuit/execute
     */
    public function execute()
    {
        $json = $this->request->getJSON(true) ?? [];
        $service = $json['service_name'] ?? 'payment_gateway_api';
        $shouldFail = (bool) ($json['simulate_failure'] ?? false);
        $fallback = $json['fallback_value'] ?? ['status' => 'FALLBACK_STALE_DATA_RETURNED'];

        $engine = $this->getEngine();
        $res = $engine->execute($service, function () use ($shouldFail) {
            if ($shouldFail) {
                throw new \RuntimeException("Simulated upstream connection timeout");
            }
            return ['status' => 'LIVE_TRANSACTION_COMPLETED', 'timestamp' => microtime(true)];
        }, $fallback);

        return $this->respondSuccess($res, 'Operation executed through circuit breaker');
    }

    /**
     * POST /api/infrastructure/circuit/reset
     */
    public function reset()
    {
        $json = $this->request->getJSON(true) ?? [];
        $service = $json['service_name'] ?? 'payment_gateway_api';
        $target = $json['target_state'] ?? 'CLOSED';

        $engine = $this->getEngine();
        $ok = $engine->setCircuitState($service, $target);

        return $this->respondSuccess(['updated' => $ok, 'service' => $service, 'state' => $target], 'Circuit state updated');
    }
}
