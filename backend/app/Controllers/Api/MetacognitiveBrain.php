<?php

namespace App\Controllers\Api;

use Atom\Brain\MetacognitiveReasoningEngine;

/**
 * MetacognitiveBrain API Controller — Phase 80 Landmark Milestone
 */
class MetacognitiveBrain extends BaseApiController
{
    private static ?MetacognitiveReasoningEngine $engine = null;

    private function getEngine(): MetacognitiveReasoningEngine
    {
        if (self::$engine === null) {
            self::$engine = new MetacognitiveReasoningEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/brain/metacognition/reflect
     */
    public function reflect()
    {
        $json = $this->request->getJSON(true) ?? [];
        $steps = $json['steps'] ?? [
            'Analyze problem parameters and input constraints',
            'Formulate hypothesis regarding edge-case boundaries',
            'Verify consistency with database schema rules',
            'Synthesize optimal response'
        ];
        $goal = $json['goal'] ?? 'solve_task';

        $engine = $this->getEngine();
        $res = $engine->reflectOnThoughtChain($steps, $goal);

        return $this->respondSuccess($res, 'Metacognitive reflection complete');
    }

    /**
     * POST /api/brain/metacognition/prune-graph
     */
    public function pruneGraph()
    {
        $json = $this->request->getJSON(true) ?? [];
        $graph = $json['graph'] ?? [];
        $threshold = (float) ($json['min_confidence'] ?? 0.60);

        $engine = $this->getEngine();
        $res = $engine->pruneThoughtGraph($graph, $threshold);

        return $this->respondSuccess($res, 'Thought graph pruned');
    }

    /**
     * GET /api/brain/metacognition/metrics
     */
    public function metrics()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getMetacognitiveMetrics(), 'Metacognitive metrics');
    }
}
