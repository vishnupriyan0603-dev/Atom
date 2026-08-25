<?php

namespace App\Controllers\Api;

use Atom\Refactoring\DependencyGraphEngine;
use Atom\Refactoring\DecouplingSynthesizer;

/**
 * DependencyGraph API Controller — Phase 43
 */
class DependencyGraph extends BaseApiController
{
    /**
     * GET /api/dependency/graph
     */
    public function graph()
    {
        $engine = new DependencyGraphEngine();
        $result = $engine->analyzeGraph([]);

        return $this->respondSuccess($result, 'Dependency graph loaded successfully');
    }

    /**
     * POST /api/dependency/scan
     */
    public function scan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sourceMap = $json['graph'] ?? $json['sources'] ?? [];

        $engine = new DependencyGraphEngine();
        $result = $engine->analyzeGraph($sourceMap);

        return $this->respondSuccess($result, 'Codebase dependency scan completed');
    }

    /**
     * POST /api/dependency/cycles
     */
    public function cycles()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sourceMap = $json['graph'] ?? [];

        $engine = new DependencyGraphEngine();
        $result = $engine->analyzeGraph($sourceMap);

        return $this->respondSuccess([
            'total_cycles' => count($result['circular_cycles']),
            'has_cycles' => $result['has_cycles'],
            'cycles' => $result['circular_cycles'],
        ], 'Circular reference analysis completed');
    }

    /**
     * POST /api/dependency/decouple
     */
    public function decouple()
    {
        $json = $this->request->getJSON(true) ?? [];
        $cycle = $json['cycle'] ?? [];
        $strategy = $json['strategy'] ?? 'interface_inversion';

        if (empty($cycle)) {
            return $this->respondError('Cycle path array is required', 400);
        }

        $synthesizer = new DecouplingSynthesizer();
        $result = $synthesizer->synthesizeDecoupling($cycle, ['strategy' => $strategy]);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Decoupling synthesis failed', 400);
        }

        return $this->respondSuccess($result, 'Automated decoupling patch synthesized');
    }

    /**
     * GET /api/dependency/metrics
     */
    public function metrics()
    {
        $engine = new DependencyGraphEngine();
        $result = $engine->analyzeGraph([]);

        return $this->respondSuccess([
            'total_nodes' => $result['total_nodes'],
            'total_edges' => $result['total_edges'],
            'abstractness' => $result['abstractness_index'],
            'has_cycles' => $result['has_cycles'],
            'cycle_count' => count($result['circular_cycles']),
        ], 'Dependency metrics retrieved');
    }
}
