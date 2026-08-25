<?php

namespace App\Controllers\Api;

use Atom\Database\QueryLoadSimulatorEngine;

/**
 * QueryLoadSimulator API Controller — Phase 61
 */
class QueryLoadSimulator extends BaseApiController
{
    private static ?QueryLoadSimulatorEngine $engine = null;

    private function getEngine(): QueryLoadSimulatorEngine
    {
        if (self::$engine === null) {
            self::$engine = new QueryLoadSimulatorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/database/load-simulator/run
     */
    public function run()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sql = $json['sql'] ?? 'SELECT * FROM orders WHERE user_id = 42 AND status = "PAID"';
        $iterations = (int) ($json['iterations'] ?? 100);

        $engine = $this->getEngine();
        $comparison = $engine->compareIndexingImpact($sql, $iterations);

        return $this->respondSuccess($comparison, 'Query load simulation benchmark completed');
    }

    /**
     * GET /api/database/load-simulator/presets
     */
    public function presets()
    {
        return $this->respondSuccess([
            'presets' => [
                ['name' => 'High Concurrency E-Commerce Orders', 'sql' => 'SELECT * FROM orders WHERE user_id = ? AND status = "COMPLETED" ORDER BY created_at DESC', 'default_iterations' => 200],
                ['name' => 'Zero-Trust Audit Log Query', 'sql' => 'SELECT * FROM audit_logs WHERE tenant_id = ? AND severity = "CRITICAL"', 'default_iterations' => 150],
                ['name' => 'Vector Spatial Neighbour Search', 'sql' => 'SELECT id, document_id FROM vector_embeddings WHERE model_type = "hnsw" LIMIT 50', 'default_iterations' => 300],
            ],
            'standard' => 'Statistical Concurrency Benchmarker (p50, p90, p99)',
        ], 'Database load simulator presets');
    }
}
