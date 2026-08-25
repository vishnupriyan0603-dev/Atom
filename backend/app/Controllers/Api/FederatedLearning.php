<?php

namespace App\Controllers\Api;

use Atom\AI\FederatedLearningEngine;

/**
 * FederatedLearning API Controller — Phase 55
 */
class FederatedLearning extends BaseApiController
{
    private static ?FederatedLearningEngine $engine = null;

    private function getEngine(): FederatedLearningEngine
    {
        if (self::$engine === null) {
            self::$engine = new FederatedLearningEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/federated-learning/weights
     */
    public function getWeights()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'global_weights' => $engine->getGlobalWeights(),
            'algorithm' => 'Federated Averaging (FedAvg) with Laplacian Differential Privacy',
        ], 'Global model weights retrieved');
    }

    /**
     * POST /api/federated-learning/aggregate
     */
    public function aggregate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $updates = $json['updates'] ?? [];

        if (empty($updates)) {
            // Seed 2 default mock edge updates if none provided
            $updates = [
                ['node_id' => 'edge_node_alpha', 'weights' => ['layer_dense_0' => [0.45, -0.12, 0.85, 0.33], 'layer_dense_1' => [0.14, 0.71, -0.20, 0.58]]],
                ['node_id' => 'edge_node_beta', 'weights' => ['layer_dense_0' => [0.40, -0.18, 0.90, 0.29], 'layer_dense_1' => [0.10, 0.76, -0.24, 0.52]]],
            ];
        }

        $engine = $this->getEngine();
        $result = $engine->aggregateWeights($updates);

        return $this->respondSuccess($result, 'Federated model weights aggregated with differential privacy');
    }
}
