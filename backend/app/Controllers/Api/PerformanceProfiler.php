<?php

namespace App\Controllers\Api;

use Atom\Refactoring\AstPerformanceProfilerEngine;

/**
 * PerformanceProfiler API Controller — Phase 51
 */
class PerformanceProfiler extends BaseApiController
{
    /**
     * POST /api/profiler/analyze
     */
    public function analyze()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required for performance profiling', 400);
        }

        $engine = new AstPerformanceProfilerEngine();
        $result = $engine->analyze($code);

        return $this->respondSuccess($result, 'AST performance profiling and complexity analysis completed');
    }

    /**
     * POST /api/profiler/optimize
     */
    public function optimize()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty(trim($code))) {
            return $this->respondError('Source code is required for optimization', 400);
        }

        $engine = new AstPerformanceProfilerEngine();
        $result = $engine->optimize($code);

        return $this->respondSuccess($result, 'Performance optimization patch synthesized');
    }

    /**
     * GET /api/profiler/metrics
     */
    public function metrics()
    {
        return $this->respondSuccess([
            'complexity_tiers' => [
                ['tier' => 'O(1)', 'description' => 'Constant Time — Hash lookups, direct array access', 'rating' => 'EXCELLENT'],
                ['tier' => 'O(log N)', 'description' => 'Logarithmic — Binary search, HNSW graph traversal', 'rating' => 'EXCELLENT'],
                ['tier' => 'O(N)', 'description' => 'Linear — Single pass loop iteration', 'rating' => 'GOOD'],
                ['tier' => 'O(N^2)', 'description' => 'Quadratic — Nested loops, pairwise comparisons', 'rating' => 'BOTTLENECK'],
                ['tier' => 'O(N * DB_RTT)', 'description' => 'N+1 Database Queries inside Loop', 'rating' => 'CRITICAL'],
            ],
            'memory_leak_detectors' => [
                'Unclosed fopen() File Streams',
                'Unbounded in-memory loop buffers',
                'Circular object references',
            ],
        ], 'Performance complexity manifest');
    }
}
