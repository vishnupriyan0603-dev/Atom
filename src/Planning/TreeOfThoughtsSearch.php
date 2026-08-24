<?php

namespace Atom\Planning;

use Atom\Security\SecretRedactor;

/**
 * Tree of Thoughts Search — Phase 30
 *
 * Implements Graph-of-Thought (GoT) and Monte Carlo Tree Search (MCTS)
 * reasoning mechanisms. Generates multi-branch hypotheses, evaluates confidence,
 * prunes dead ends, backpropagates scores, and selects optimal paths.
 */
class TreeOfThoughtsSearch
{
    private static array $trees = [];
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Initializes a new thought tree for a given goal.
     */
    public function initializeTree(string $goal): array
    {
        $cleanGoal = trim($goal);
        if (empty($cleanGoal)) {
            throw new \InvalidArgumentException('Goal cannot be empty');
        }

        $redactedGoal = $this->redactor->redact($cleanGoal);
        $treeId = 'got_tree_' . substr(md5($redactedGoal . microtime(true)), 0, 10);
        $rootId = 'node_root';

        $rootNode = [
            'id'         => $rootId,
            'parent_id'  => null,
            'thought'    => 'Root Objective: ' . $redactedGoal,
            'action'     => 'decompose',
            'confidence' => 1.0,
            'value'      => 1.0,
            'visits'     => 1,
            'depth'      => 0,
            'status'     => 'selected',
            'children'   => [],
        ];

        $tree = [
            'tree_id'      => $treeId,
            'goal'         => $redactedGoal,
            'root_id'      => $rootId,
            'nodes'        => [$rootId => $rootNode],
            'active_path'  => [$rootId],
            'pruned_count' => 0,
            'created_at'   => date('c'),
        ];

        self::$trees[$treeId] = $tree;
        return $tree;
    }

    /**
     * Expands a node with candidate thought branches.
     */
    public function expandNode(string $treeId, string $nodeId, array $candidateThoughts): array
    {
        if (!isset(self::$trees[$treeId])) {
            throw new \InvalidArgumentException("Tree with ID '{$treeId}' not found");
        }
        if (!isset(self::$trees[$treeId]['nodes'][$nodeId])) {
            throw new \InvalidArgumentException("Node with ID '{$nodeId}' not found in tree");
        }

        $parent = self::$trees[$treeId]['nodes'][$nodeId];
        $created = [];

        foreach ($candidateThoughts as $idx => $thought) {
            $childId = $nodeId . '_b' . ($idx + 1);
            $redactedThought = $this->redactor->redact(is_string($thought) ? $thought : ($thought['thought'] ?? 'Branch Step'));
            $action = is_array($thought) && isset($thought['action']) ? $thought['action'] : 'execute';
            $initConfidence = is_array($thought) && isset($thought['confidence']) ? (float)$thought['confidence'] : 0.5;

            $node = [
                'id'         => $childId,
                'parent_id'  => $nodeId,
                'thought'    => $redactedThought,
                'action'     => $action,
                'confidence' => $initConfidence,
                'value'      => $initConfidence,
                'visits'     => 0,
                'depth'      => $parent['depth'] + 1,
                'status'     => 'exploring',
                'children'   => [],
            ];

            self::$trees[$treeId]['nodes'][$childId] = $node;
            self::$trees[$treeId]['nodes'][$nodeId]['children'][] = $childId;
            $created[] = $node;
        }

        return $created;
    }

    /**
     * Evaluates a thought node with confidence and metric scores.
     */
    public function evaluateThought(string $treeId, string $nodeId, float $confidence, array $metrics = []): array
    {
        if (!isset(self::$trees[$treeId]['nodes'][$nodeId])) {
            throw new \InvalidArgumentException("Node '{$nodeId}' not found");
        }

        $node = &self::$trees[$treeId]['nodes'][$nodeId];
        $node['confidence'] = max(0.0, min(1.0, $confidence));
        $node['visits']++;
        $node['metrics'] = $metrics;

        // Determine if node should be pruned or evaluated
        if ($node['confidence'] < 0.35) {
            $this->pruneBranch($treeId, $nodeId);
            $node['status'] = 'pruned';
        } else {
            $node['status'] = 'evaluated';
        }

        $this->backpropagate($treeId, $nodeId, $node['confidence']);

        return $node;
    }

    /**
     * Backpropagates evaluation scores up the ancestor chain.
     */
    public function backpropagate(string $treeId, string $nodeId, float $score): void
    {
        $currentId = $nodeId;

        while ($currentId !== null && isset(self::$trees[$treeId]['nodes'][$currentId])) {
            $node = &self::$trees[$treeId]['nodes'][$currentId];
            if ($node['id'] !== $nodeId) {
                $node['visits']++;
                // Running average value update
                $node['value'] = round(($node['value'] * ($node['visits'] - 1) + $score) / $node['visits'], 4);
            }
            $currentId = $node['parent_id'];
        }
    }

    /**
     * Prunes a node branch and all of its descendants.
     */
    public function pruneBranch(string $treeId, string $nodeId): array
    {
        if (!isset(self::$trees[$treeId]['nodes'][$nodeId])) {
            return [];
        }

        $pruned = [];
        $stack = [$nodeId];

        while (!empty($stack)) {
            $currId = array_pop($stack);
            if (isset(self::$trees[$treeId]['nodes'][$currId])) {
                self::$trees[$treeId]['nodes'][$currId]['status'] = 'pruned';
                $pruned[] = $currId;
                foreach (self::$trees[$treeId]['nodes'][$currId]['children'] as $childId) {
                    $stack[] = $childId;
                }
            }
        }

        self::$trees[$treeId]['pruned_count'] += count($pruned);
        return $pruned;
    }

    /**
     * Computes and selects the optimal path from root to best evaluated leaf.
     */
    public function selectBestPath(string $treeId): array
    {
        if (!isset(self::$trees[$treeId])) {
            throw new \InvalidArgumentException("Tree '{$treeId}' not found");
        }

        $tree = self::$trees[$treeId];
        $rootId = $tree['root_id'];
        $path = [$rootId];
        $currId = $rootId;

        while (true) {
            $children = $tree['nodes'][$currId]['children'] ?? [];
            if (empty($children)) {
                break;
            }

            // Filter out pruned children
            $validChildren = array_filter($children, function ($childId) use ($tree) {
                return isset($tree['nodes'][$childId]) && $tree['nodes'][$childId]['status'] !== 'pruned';
            });

            if (empty($validChildren)) {
                break;
            }

            // Select child with highest score: value + confidence
            usort($validChildren, function ($a, $b) use ($tree) {
                $scoreA = ($tree['nodes'][$a]['value'] ?? 0) * 0.5 + ($tree['nodes'][$a]['confidence'] ?? 0) * 0.5;
                $scoreB = ($tree['nodes'][$b]['value'] ?? 0) * 0.5 + ($tree['nodes'][$b]['confidence'] ?? 0) * 0.5;
                return $scoreB <=> $scoreA;
            });

            $bestChild = reset($validChildren);
            $path[] = $bestChild;
            $currId = $bestChild;
        }

        // Mark path nodes as selected
        foreach ($path as $pId) {
            if (isset(self::$trees[$treeId]['nodes'][$pId])) {
                self::$trees[$treeId]['nodes'][$pId]['status'] = 'selected';
            }
        }

        self::$trees[$treeId]['active_path'] = $path;
        return $path;
    }

    /**
     * Autonomous multi-branch Graph-of-Thought search simulation.
     */
    public function search(string $goal, int $branchingFactor = 3, int $maxDepth = 3): array
    {
        $tree = $this->initializeTree($goal);
        $treeId = $tree['tree_id'];

        $queue = [$tree['root_id']];
        $currentDepth = 0;

        while (!empty($queue) && $currentDepth < $maxDepth) {
            $nextQueue = [];

            foreach ($queue as $parentNodeId) {
                if (!isset(self::$trees[$treeId]['nodes'][$parentNodeId])) {
                    continue;
                }
                if (self::$trees[$treeId]['nodes'][$parentNodeId]['status'] === 'pruned') {
                    continue;
                }

                // Generate branching candidates
                $candidates = [];
                for ($b = 1; $b <= $branchingFactor; $b++) {
                    $score = round(0.45 + (crc32($goal . $parentNodeId . $b) % 50) / 100, 2);
                    $candidates[] = [
                        'thought'    => "Hypothesis {$b} for {$parentNodeId}: Strategy variant {$b}",
                        'action'     => 'reason_and_execute',
                        'confidence' => $score,
                    ];
                }

                $newNodes = $this->expandNode($treeId, $parentNodeId, $candidates);

                foreach ($newNodes as $child) {
                    $eval = $this->evaluateThought($treeId, $child['id'], $child['confidence']);
                    if ($eval['status'] !== 'pruned') {
                        $nextQueue[] = $child['id'];
                    }
                }
            }

            $queue = $nextQueue;
            $currentDepth++;
        }

        $bestPath = $this->selectBestPath($treeId);

        return [
            'tree_id'      => $treeId,
            'goal'         => $tree['goal'],
            'total_nodes'  => count(self::$trees[$treeId]['nodes']),
            'pruned_nodes' => self::$trees[$treeId]['pruned_count'],
            'best_path'    => $bestPath,
            'tree'         => self::$trees[$treeId],
        ];
    }

    /**
     * Retrieves stored tree state.
     */
    public function getTree(string $treeId): ?array
    {
        return self::$trees[$treeId] ?? null;
    }

    /**
     * Clears all stored trees (useful in testing).
     */
    public static function reset(): void
    {
        self::$trees = [];
    }
}
