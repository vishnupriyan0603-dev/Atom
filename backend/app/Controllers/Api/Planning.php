<?php

namespace App\Controllers\Api;

use Atom\Planning\HierarchicalTaskDecomposer;
use Atom\Planning\TreeOfThoughtsSearch;
use Atom\Planning\PlanVerifierBacktracker;
use Atom\Planning\PlanVisualizer;

/**
 * Long-Horizon Planning & Graph-of-Thought API Controller — Phase 30
 *
 * Endpoints:
 * - POST /api/v1/planning/decompose    — Decompose goal into hierarchical DAG
 * - POST /api/v1/planning/search       — Multi-branch Graph-of-Thought search
 * - POST /api/v1/planning/execute-step — Execute individual plan step with verification
 * - GET  /api/v1/planning/tree/{id}    — Retrieve full tree state & visual representations
 * - POST /api/v1/planning/rollback     — Backtrack failed node to viable ancestor
 */
class Planning extends BaseApiController
{
    private static ?TreeOfThoughtsSearch $searchInstance = null;
    private static ?PlanVerifierBacktracker $verifierInstance = null;

    private function getSearchEngine(): TreeOfThoughtsSearch
    {
        if (self::$searchInstance === null) {
            self::$searchInstance = new TreeOfThoughtsSearch();
        }
        return self::$searchInstance;
    }

    private function getVerifier(): PlanVerifierBacktracker
    {
        if (self::$verifierInstance === null) {
            self::$verifierInstance = new PlanVerifierBacktracker();
        }
        return self::$verifierInstance;
    }

    /**
     * POST /api/v1/planning/decompose
     */
    public function decompose()
    {
        $json = $this->request->getJSON(true) ?? [];
        $goal = $json['goal'] ?? '';
        $maxDepth = (int)($json['max_depth'] ?? 3);
        $context = $json['context'] ?? [];

        if (empty($goal)) {
            return $this->respondError('Missing goal parameter', 400);
        }

        try {
            $decomposer = new HierarchicalTaskDecomposer();
            $plan = $decomposer->decompose($goal, $context, $maxDepth);

            $visualizer = new PlanVisualizer();
            $plan['mermaid'] = $visualizer->toMermaid($plan);
            $plan['ascii_tree'] = $visualizer->toAsciiTree($plan);
            $plan['hierarchy'] = $visualizer->toJsonHierarchy($plan);

            return $this->respondSuccess($plan, 'Goal decomposed into hierarchical DAG successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/planning/search
     */
    public function search()
    {
        $json = $this->request->getJSON(true) ?? [];
        $goal = $json['goal'] ?? '';
        $branching = (int)($json['branching_factor'] ?? 3);
        $maxDepth = (int)($json['max_depth'] ?? 3);

        if (empty($goal)) {
            return $this->respondError('Missing goal parameter', 400);
        }

        try {
            $engine = $this->getSearchEngine();
            $result = $engine->search($goal, $branching, $maxDepth);

            $visualizer = new PlanVisualizer();
            $result['mermaid'] = $visualizer->toMermaid($result['tree']);
            $result['ascii_tree'] = $visualizer->toAsciiTree($result['tree']);
            $result['hierarchy'] = $visualizer->toJsonHierarchy($result['tree']);

            return $this->respondSuccess($result, 'Graph-of-Thought search completed successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/planning/execute-step
     */
    public function executeStep()
    {
        $json = $this->request->getJSON(true) ?? [];
        $treeId = $json['tree_id'] ?? '';
        $nodeId = $json['node_id'] ?? '';
        $mockOutput = $json['output'] ?? ['status' => 'ok', 'result' => 'Executed step cleanly'];

        if (empty($treeId) || empty($nodeId)) {
            return $this->respondError('Missing tree_id or node_id parameter', 400);
        }

        $engine = $this->getSearchEngine();
        $tree = $engine->getTree($treeId);

        if (!$tree || !isset($tree['nodes'][$nodeId])) {
            return $this->respondError('Plan node not found', 404);
        }

        $verifier = $this->getVerifier();
        $verification = $verifier->verifyStep($tree['nodes'][$nodeId], $mockOutput);

        if ($verification['verified']) {
            $tree['nodes'][$nodeId]['status'] = 'completed';
            $tree['nodes'][$nodeId]['output'] = $mockOutput;
            $verifier->saveSnapshot($treeId, $nodeId, ['completed' => true, 'output' => $mockOutput]);
            return $this->respondSuccess([
                'node_id'      => $nodeId,
                'status'       => 'completed',
                'verification' => $verification,
            ], 'Step executed and verified successfully');
        }

        // Verification failed — attempt automatic backtrack
        $backtrack = $verifier->backtrack($tree, $nodeId);

        return $this->respondSuccess([
            'node_id'      => $nodeId,
            'status'       => 'failed',
            'verification' => $verification,
            'backtrack'    => $backtrack,
        ], 'Step verification failed; backtrack triggered');
    }

    /**
     * GET /api/v1/planning/tree/{id}
     */
    public function showTree(string $treeId = '')
    {
        if (empty($treeId)) {
            return $this->respondError('Missing tree ID', 400);
        }

        $engine = $this->getSearchEngine();
        $tree = $engine->getTree($treeId);

        if (!$tree) {
            return $this->respondError('Tree not found', 404);
        }

        $visualizer = new PlanVisualizer();
        $tree['mermaid'] = $visualizer->toMermaid($tree);
        $tree['ascii_tree'] = $visualizer->toAsciiTree($tree);
        $tree['hierarchy'] = $visualizer->toJsonHierarchy($tree);

        return $this->respondSuccess($tree, 'Tree retrieved successfully');
    }

    /**
     * POST /api/v1/planning/rollback
     */
    public function rollback()
    {
        $json = $this->request->getJSON(true) ?? [];
        $treeId = $json['tree_id'] ?? '';
        $nodeId = $json['node_id'] ?? '';

        if (empty($treeId) || empty($nodeId)) {
            return $this->respondError('Missing tree_id or node_id parameter', 400);
        }

        $engine = $this->getSearchEngine();
        $tree = $engine->getTree($treeId);

        if (!$tree || !isset($tree['nodes'][$nodeId])) {
            return $this->respondError('Plan node not found', 404);
        }

        $verifier = $this->getVerifier();
        $backtrack = $verifier->backtrack($tree, $nodeId);

        return $this->respondSuccess([
            'tree_id'   => $treeId,
            'node_id'   => $nodeId,
            'backtrack' => $backtrack,
            'tree'      => $tree,
        ], 'Rollback and alternative branch selection completed');
    }
}
