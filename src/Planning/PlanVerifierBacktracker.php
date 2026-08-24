<?php

namespace Atom\Planning;

use Atom\Security\SecretRedactor;

/**
 * Plan Verifier & Backtracker — Phase 30
 *
 * Validates intermediate execution results against postconditions, detects
 * reasoning flaws or dead ends, executes state rollbacks to valid ancestor
 * checkpoints, and switches execution to viable alternate branches.
 */
class PlanVerifierBacktracker
{
    private static array $snapshots = [];
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Verifies an execution step output against expected criteria.
     *
     * @param array $node Node definition.
     * @param mixed $output Step execution output.
     * @param array $criteria Custom verification assertions.
     * @return array Verification result.
     */
    public function verifyStep(array $node, mixed $output, array $criteria = []): array
    {
        $nodeId = $node['id'] ?? 'unknown';

        if ($output === null || $output === false || $output === '') {
            return [
                'verified'   => false,
                'node_id'    => $nodeId,
                'confidence' => 0.0,
                'flaw'       => 'Empty or null output received from step execution',
                'reason'     => 'Verification failed: Null output',
            ];
        }

        // Check for common error signatures
        if (is_string($output)) {
            $redacted = $this->redactor->redact($output);
            if (preg_match('/\b(fatal error|exception|parse error|syntax error|undefined function|permission denied)\b/i', $output)) {
                return [
                    'verified'   => false,
                    'node_id'    => $nodeId,
                    'confidence' => 0.1,
                    'flaw'       => 'Runtime failure or exception detected in step output',
                    'reason'     => 'Execution output matched error pattern',
                    'output'     => $redacted,
                ];
            }
        } elseif (is_array($output)) {
            if (isset($output['error']) && !empty($output['error'])) {
                return [
                    'verified'   => false,
                    'node_id'    => $nodeId,
                    'confidence' => 0.1,
                    'flaw'       => is_string($output['error']) ? $this->redactor->redact($output['error']) : 'Error payload returned',
                    'reason'     => 'Execution returned error status',
                ];
            }
            if (isset($output['success']) && $output['success'] === false) {
                return [
                    'verified'   => false,
                    'node_id'    => $nodeId,
                    'confidence' => 0.2,
                    'flaw'       => 'Step declared failure status (success == false)',
                    'reason'     => 'Explicit step failure',
                ];
            }
        }

        // Check user-specified criteria
        if (!empty($criteria['required_keys']) && is_array($output)) {
            foreach ($criteria['required_keys'] as $reqKey) {
                if (!array_key_exists($reqKey, $output)) {
                    return [
                        'verified'   => false,
                        'node_id'    => $nodeId,
                        'confidence' => 0.3,
                        'flaw'       => "Missing required schema key: '{$reqKey}'",
                        'reason'     => 'Schema mismatch',
                    ];
                }
            }
        }

        return [
            'verified'   => true,
            'node_id'    => $nodeId,
            'confidence' => 0.95,
            'flaw'       => null,
            'reason'     => 'All verification assertions passed',
        ];
    }

    /**
     * Backtracks tree execution from a failed node to the nearest viable ancestor with alternate branches.
     *
     * @param array $tree Reference to tree structure.
     * @param string $failedNodeId Node that failed verification.
     * @return array Backtracking result.
     */
    public function backtrack(array &$tree, string $failedNodeId): array
    {
        if (!isset($tree['nodes'][$failedNodeId])) {
            return [
                'backtracked'    => false,
                'reason'         => "Failed node '{$failedNodeId}' does not exist",
                'ancestor_id'    => null,
                'next_branch_id' => null,
                'reverted_nodes' => [],
            ];
        }

        $tree['nodes'][$failedNodeId]['status'] = 'failed';

        $reverted = [$failedNodeId];
        $currId = $tree['nodes'][$failedNodeId]['parent_id'];
        $viableAncestor = null;
        $nextBranchId = null;

        while ($currId !== null && isset($tree['nodes'][$currId])) {
            $ancestor = &$tree['nodes'][$currId];
            $children = $ancestor['children'] ?? [];

            // Search for unfailed, unpruned alternate child branch
            foreach ($children as $childId) {
                if ($childId === $failedNodeId || in_array($childId, $reverted, true)) {
                    continue;
                }
                if (isset($tree['nodes'][$childId]) && !in_array($tree['nodes'][$childId]['status'], ['failed', 'pruned'], true)) {
                    $viableAncestor = $currId;
                    $nextBranchId = $childId;
                    break 2;
                }
            }

            $reverted[] = $currId;
            $currId = $ancestor['parent_id'];
        }

        if ($nextBranchId !== null && isset($tree['nodes'][$nextBranchId])) {
            $tree['nodes'][$nextBranchId]['status'] = 'selected';
            return [
                'backtracked'    => true,
                'ancestor_id'    => $viableAncestor,
                'next_branch_id' => $nextBranchId,
                'reverted_nodes' => $reverted,
                'reason'         => "Backtracked from {$failedNodeId} to alternate branch {$nextBranchId}",
            ];
        }

        return [
            'backtracked'    => false,
            'ancestor_id'    => null,
            'next_branch_id' => null,
            'reverted_nodes' => $reverted,
            'reason'         => 'No viable alternate branches found in tree',
        ];
    }

    /**
     * Records a state checkpoint snapshot.
     */
    public function saveSnapshot(string $treeId, string $nodeId, array $state): void
    {
        self::$snapshots[$treeId][$nodeId] = [
            'tree_id'   => $treeId,
            'node_id'   => $nodeId,
            'state'     => $state,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Retrieves a stored snapshot.
     */
    public function getSnapshot(string $treeId, string $nodeId): ?array
    {
        return self::$snapshots[$treeId][$nodeId] ?? null;
    }

    /**
     * Clears all recorded snapshots.
     */
    public static function reset(): void
    {
        self::$snapshots = [];
    }
}
