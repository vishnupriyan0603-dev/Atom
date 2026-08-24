<?php

namespace App\Controllers\Api;

use Atom\Math\SymbolicEquationSolver;
use Atom\Math\MatrixEngine;
use Atom\Math\StatisticalAnalyzer;
use Atom\Algorithms\ComputationalGeometry;
use Atom\Algorithms\AlgorithmComplexityAnalyzer;

/**
 * Mathematical & Algorithmic Computation API Controller — Phase 31
 *
 * Endpoints:
 * - POST /api/v1/compute/solve       — Solve algebraic equations with step derivation
 * - POST /api/v1/compute/matrix      — Perform matrix linear algebra operations
 * - POST /api/v1/compute/statistics  — Descriptive statistics, regression, and correlation
 * - POST /api/v1/compute/geometry    — Geometric calculations and convex hull
 * - POST /api/v1/compute/complexity  — Static Big-O code complexity analysis
 */
class Computation extends BaseApiController
{
    /**
     * POST /api/v1/compute/solve
     */
    public function solve()
    {
        $json = $this->request->getJSON(true) ?? [];
        $equation = $json['equation'] ?? '';

        if (empty($equation)) {
            return $this->respondError('Missing equation parameter', 400);
        }

        try {
            $solver = new SymbolicEquationSolver();
            $result = $solver->solve($equation);
            return $this->respondSuccess($result, 'Equation solved successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/compute/matrix
     */
    public function matrix()
    {
        $json = $this->request->getJSON(true) ?? [];
        $operation = $json['operation'] ?? 'multiply';
        $matrixA = $json['matrix_a'] ?? [];
        $matrixB = $json['matrix_b'] ?? [];

        try {
            $engine = new MatrixEngine();
            $result = match ($operation) {
                'multiply'     => $engine->multiply($matrixA, $matrixB),
                'determinant'  => ['determinant' => $engine->determinant($matrixA)],
                'invert'       => ['inverse' => $engine->invert($matrixA)],
                'transpose'    => ['transposed' => $engine->transpose($matrixA)],
                'solve_system' => ['solution' => $engine->solveSystem($matrixA, $matrixB)],
                'transform_2d' => $engine->create2dTransform(
                    (float)($json['angle'] ?? 0),
                    (float)($json['scale_x'] ?? 1),
                    (float)($json['scale_y'] ?? 1),
                    (float)($json['trans_x'] ?? 0),
                    (float)($json['trans_y'] ?? 0)
                ),
                default        => throw new \InvalidArgumentException("Unsupported matrix operation: '{$operation}'"),
            };

            return $this->respondSuccess($result, 'Matrix computation completed successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/compute/statistics
     */
    public function statistics()
    {
        $json = $this->request->getJSON(true) ?? [];
        $mode = $json['mode'] ?? 'describe';
        $data = $json['data'] ?? [];
        $dataY = $json['data_y'] ?? [];

        try {
            $analyzer = new StatisticalAnalyzer();
            $result = match ($mode) {
                'describe'    => $analyzer->describe($data),
                'regression'  => $analyzer->linearRegression($data, $dataY),
                'correlation' => ['correlation' => $analyzer->correlation($data, $dataY)],
                default       => throw new \InvalidArgumentException("Unsupported statistics mode: '{$mode}'"),
            };

            return $this->respondSuccess($result, 'Statistical analysis completed successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/compute/geometry
     */
    public function geometry()
    {
        $json = $this->request->getJSON(true) ?? [];
        $operation = $json['operation'] ?? 'area';
        $points = $json['points'] ?? [];

        try {
            $geo = new ComputationalGeometry();
            $result = match ($operation) {
                'area'             => ['area' => $geo->polygonArea($points)],
                'convex_hull'      => ['hull' => $geo->convexHull($points)],
                'distance'         => ['distance' => $geo->distance($points[0] ?? [], $points[1] ?? [], $json['metric'] ?? 'euclidean')],
                'point_in_polygon' => ['inside' => $geo->isPointInPolygon($json['point'] ?? [], $points)],
                default            => throw new \InvalidArgumentException("Unsupported geometry operation: '{$operation}'"),
            };

            return $this->respondSuccess($result, 'Geometric calculation completed successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/compute/complexity
     */
    public function complexity()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';

        if (empty($code)) {
            return $this->respondError('Missing code parameter', 400);
        }

        try {
            $analyzer = new AlgorithmComplexityAnalyzer();
            $result = $analyzer->analyze($code);
            return $this->respondSuccess($result, 'Algorithm complexity analyzed successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }
}
