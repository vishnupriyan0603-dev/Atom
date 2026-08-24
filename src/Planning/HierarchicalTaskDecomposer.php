<?php

namespace Atom\Planning;

use Atom\Security\SecretRedactor;

/**
 * Hierarchical Task Decomposer — Phase 30
 *
 * Breaks down complex, long-horizon user goals into multi-level
 * hierarchical Directed Acyclic Graph (DAG) task trees with explicit
 * prerequisites, sub-goals, required tools, and risk levels.
 */
class HierarchicalTaskDecomposer
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Decomposes a high-level goal into a hierarchical task structure.
     *
     * @param string $goal User goal or objective.
     * @param array $context Environmental or user context.
     * @param int $maxDepth Maximum decomposition hierarchy depth (default: 3).
     * @return array Structured decomposition hierarchy.
     */
    public function decompose(string $goal, array $context = [], int $maxDepth = 3): array
    {
        $sanitizedGoal = trim($goal);
        if (empty($sanitizedGoal)) {
            throw new \InvalidArgumentException('Goal cannot be empty');
        }

        $redactedGoal = $this->redactor->redact($sanitizedGoal);
        $treeId = 'got_' . substr(md5($redactedGoal . microtime(true)), 0, 10);
        $effectiveDepth = max(1, min($maxDepth, 5));

        $nodes = [];
        $rootId = 'node_root';
        $nodes[$rootId] = [
            'id'           => $rootId,
            'parent_id'    => null,
            'title'        => $redactedGoal,
            'type'         => 'root_goal',
            'depth'        => 0,
            'status'       => 'pending',
            'dependencies' => [],
            'tool'         => null,
            'risk'         => 'low',
            'children'     => [],
        ];

        $lowerGoal = strtolower($redactedGoal);
        $isComplex = preg_match('/\b(build|create|deploy|refactor|analyze and|migrate|orchestrate|pipeline|setup)\b/i', $lowerGoal);

        if (!$isComplex) {
            // Simple single-level or direct reasoning task
            $subId = 'node_1';
            $nodes[$subId] = [
                'id'           => $subId,
                'parent_id'    => $rootId,
                'title'        => 'Execute reasoning for: ' . $redactedGoal,
                'type'         => 'reasoning',
                'depth'        => 1,
                'status'       => 'pending',
                'dependencies' => [$rootId],
                'tool'         => null,
                'risk'         => 'low',
                'children'     => [],
            ];
            $nodes[$rootId]['children'][] = $subId;
        } else {
            // Decompose into standard multi-stage hierarchy: Research -> Implementation -> Verification -> Delivery
            $stages = [
                [
                    'key'   => 'research',
                    'title' => 'Context Retrieval & Architecture Analysis',
                    'tool'  => 'search_code',
                    'risk'  => 'low',
                    'subs'  => ['Inspect workspace structure', 'Extract dependencies & config'],
                ],
                [
                    'key'   => 'implement',
                    'title' => 'Target Implementation & Transformation',
                    'tool'  => strpos($lowerGoal, 'create') !== false ? 'create_file' : 'patch_file',
                    'risk'  => 'medium',
                    'subs'  => ['Draft source code & components', 'Integrate with core registry'],
                ],
                [
                    'key'   => 'verify',
                    'title' => 'Verification & Test Suite Execution',
                    'tool'  => 'php_lint',
                    'risk'  => 'low',
                    'subs'  => ['Run syntax lint validation', 'Execute unit test pass'],
                ],
                [
                    'key'   => 'deliver',
                    'title' => 'State Commitment & Final Synthesis',
                    'tool'  => null,
                    'risk'  => 'low',
                    'subs'  => ['Generate completion summary'],
                ],
            ];

            $prevStageId = null;
            $stageIdx = 1;

            foreach ($stages as $stage) {
                $stageId = 'node_' . $stageIdx;
                $deps = $prevStageId ? [$prevStageId] : [$rootId];

                $nodes[$stageId] = [
                    'id'           => $stageId,
                    'parent_id'    => $rootId,
                    'title'        => $stage['title'],
                    'type'         => 'milestone',
                    'depth'        => 1,
                    'status'       => 'pending',
                    'dependencies' => $deps,
                    'tool'         => $stage['tool'],
                    'risk'         => $stage['risk'],
                    'children'     => [],
                ];
                $nodes[$rootId]['children'][] = $stageId;

                // Sub-tasks if effective depth > 1
                if ($effectiveDepth >= 2 && !empty($stage['subs'])) {
                    $subIdx = 1;
                    $prevSubId = null;
                    foreach ($stage['subs'] as $subTitle) {
                        $subTaskId = "{$stageId}_{$subIdx}";
                        $subDeps = $prevSubId ? [$prevSubId] : [$stageId];

                        $nodes[$subTaskId] = [
                            'id'           => $subTaskId,
                            'parent_id'    => $stageId,
                            'title'        => $subTitle,
                            'type'         => 'leaf_action',
                            'depth'        => 2,
                            'status'       => 'pending',
                            'dependencies' => $subDeps,
                            'tool'         => $stage['tool'],
                            'risk'         => $stage['risk'],
                            'children'     => [],
                        ];
                        $nodes[$stageId]['children'][] = $subTaskId;
                        $prevSubId = $subTaskId;
                        $subIdx++;
                    }
                }

                $prevStageId = $stageId;
                $stageIdx++;
            }
        }

        $executionOrder = $this->computeExecutionOrder($nodes);

        return [
            'tree_id'         => $treeId,
            'goal'            => $redactedGoal,
            'total_nodes'     => count($nodes),
            'max_depth'       => $effectiveDepth,
            'root_id'         => $rootId,
            'nodes'           => $nodes,
            'execution_order' => $executionOrder,
            'created_at'      => date('c'),
        ];
    }

    /**
     * Resolves topological execution order based on task dependencies.
     *
     * @param array $nodes Map of node objects.
     * @return array Ordered list of node IDs.
     */
    public function computeExecutionOrder(array $nodes): array
    {
        $ordered = [];
        $visited = [];

        $visit = function ($nodeId) use (&$visit, &$ordered, &$visited, $nodes) {
            if (isset($visited[$nodeId])) {
                return;
            }
            $visited[$nodeId] = true;

            if (isset($nodes[$nodeId]['dependencies'])) {
                foreach ($nodes[$nodeId]['dependencies'] as $depId) {
                    if (isset($nodes[$depId]) && !isset($visited[$depId])) {
                        $visit($depId);
                    }
                }
            }

            $ordered[] = $nodeId;
        };

        foreach (array_keys($nodes) as $nodeId) {
            if (!isset($visited[$nodeId])) {
                $visit($nodeId);
            }
        }

        return $ordered;
    }
}
