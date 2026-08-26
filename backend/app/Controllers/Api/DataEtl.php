<?php

namespace App\Controllers\Api;

use Atom\Database\DataPipelineEtlOrchestratorEngine;

/**
 * DataEtl API Controller — Phase 93
 */
class DataEtl extends BaseApiController
{
    private static ?DataPipelineEtlOrchestratorEngine $engine = null;

    private function getEngine(): DataPipelineEtlOrchestratorEngine
    {
        if (self::$engine === null) {
            self::$engine = new DataPipelineEtlOrchestratorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/database/etl/execute
     */
    public function execute()
    {
        $json = $this->request->getJSON(true) ?? [];
        $records = $json['records'] ?? [
            ['id' => 1, 'email' => '  ALICE@DOMAIN.COM  ', 'active' => true],
            ['id' => 2, 'email' => 'BOB@DOMAIN.COM', 'active' => false],
            ['id' => 3, 'email' => 'Carol@Domain.Com', 'active' => true],
        ];
        $pipelineId = $json['pipeline_id'] ?? 'user_activity_sanitizer';

        $engine = $this->getEngine();
        $res = $engine->executePipeline($records, $pipelineId);

        return $this->respondSuccess($res, 'ETL pipeline batch executed');
    }

    /**
     * GET /api/database/etl/pipelines
     */
    public function pipelines()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getAvailablePipelines(), 'Available ETL pipelines');
    }
}
