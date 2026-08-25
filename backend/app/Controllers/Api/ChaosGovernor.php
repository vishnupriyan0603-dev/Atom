<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\ChaosEngineeringMeshEngine;

/**
 * ChaosGovernor API Controller — Phase 81
 */
class ChaosGovernor extends BaseApiController
{
    private static ?ChaosEngineeringMeshEngine $engine = null;

    private function getEngine(): ChaosEngineeringMeshEngine
    {
        if (self::$engine === null) {
            self::$engine = new ChaosEngineeringMeshEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/infrastructure/chaos/experiments
     */
    public function experiments()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getActiveExperiments(), 'Chaos experiments retrieved');
    }

    /**
     * POST /api/infrastructure/chaos/start
     */
    public function start()
    {
        $json = $this->request->getJSON(true) ?? [];
        $id = $json['experiment_id'] ?? 'exp_' . bin2hex(random_bytes(3));
        $type = $json['fault_type'] ?? 'latency';
        $blast = (int) ($json['blast_radius_pct'] ?? 10);
        $targets = $json['targets'] ?? [];

        $engine = $this->getEngine();
        $res = $engine->startExperiment($id, $type, $blast, $targets);

        return $this->respondSuccess($res, 'Chaos experiment started');
    }

    /**
     * POST /api/infrastructure/chaos/stop
     */
    public function stop()
    {
        $json = $this->request->getJSON(true) ?? [];
        $id = $json['experiment_id'] ?? null;

        $engine = $this->getEngine();
        $ok = $engine->stopExperiment($id);

        return $this->respondSuccess(['stopped' => $ok, 'target' => $id ?? 'ALL'], 'Chaos stopped');
    }

    /**
     * POST /api/infrastructure/chaos/evaluate
     */
    public function evaluate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $reqId = $json['request_id'] ?? bin2hex(random_bytes(6));
        $endpoint = $json['endpoint'] ?? '/api/users/profile';

        $engine = $this->getEngine();
        $res = $engine->shouldInjectFault($reqId, $endpoint);

        return $this->respondSuccess($res, 'Chaos fault evaluation complete');
    }
}
