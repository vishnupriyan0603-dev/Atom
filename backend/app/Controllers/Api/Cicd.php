<?php

namespace App\Controllers\Api;

use Atom\Testing\TestSynthesizer;
use Atom\Testing\SelfCorrectionEngine;
use Atom\CiCd\PipelineRunner;

/**
 * CI/CD & Testing API Controller — Phase 29
 *
 * Endpoints:
 * - POST /api/v1/cicd/test/generate   — Synthesize PHPUnit test suite for code
 * - POST /api/v1/cicd/test/run        — Trigger test suite execution
 * - POST /api/v1/cicd/repair          — Diagnose test failure & synthesize patch
 * - GET  /api/v1/cicd/pipelines       — Retrieve recent pipeline execution logs
 * - POST /api/v1/cicd/pipeline/trigger — Trigger multi-stage CI/CD pipeline
 */
class Cicd extends BaseApiController
{
    private static ?PipelineRunner $runnerInstance = null;

    private function getRunner(): PipelineRunner
    {
        if (self::$runnerInstance === null) {
            self::$runnerInstance = new PipelineRunner();
        }
        return self::$runnerInstance;
    }

    /**
     * POST /api/v1/cicd/test/generate
     */
    public function generateTest()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';
        $className = $json['class_name'] ?? '';

        if (empty($code)) {
            return $this->respondError('Missing code parameter', 400);
        }

        $synthesizer = new TestSynthesizer();
        $result = $synthesizer->synthesizeTest($code, $className);

        return $this->respondSuccess($result, 'Test suite synthesized successfully');
    }

    /**
     * POST /api/v1/cicd/test/run
     */
    public function runTests()
    {
        $runner = $this->getRunner();
        $result = $runner->runPipeline(['unit_tests']);

        return $this->respondSuccess($result, 'Test run completed');
    }

    /**
     * POST /api/v1/cicd/repair
     */
    public function repair()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';
        $error = $json['error'] ?? '';

        if (empty($code) || empty($error)) {
            return $this->respondError('Missing code or error parameter', 400);
        }

        $engine = new SelfCorrectionEngine();
        $result = $engine->synthesizePatch($code, $error);

        return $this->respondSuccess($result, 'Self-correction patch synthesized');
    }

    /**
     * GET /api/v1/cicd/pipelines
     */
    public function pipelines()
    {
        $runner = $this->getRunner();
        return $this->respondSuccess([
            'recent_runs' => $runner->getRecentPipelines(10),
        ], 'Pipeline runs retrieved');
    }

    /**
     * POST /api/v1/cicd/pipeline/trigger
     */
    public function triggerPipeline()
    {
        $json = $this->request->getJSON(true) ?? [];
        $stages = $json['stages'] ?? [];

        $runner = $this->getRunner();
        $result = $runner->runPipeline($stages);

        return $this->respondSuccess($result, 'CI/CD pipeline executed successfully');
    }
}
