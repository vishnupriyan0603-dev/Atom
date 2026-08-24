<?php

use PHPUnit\Framework\TestCase;
use Atom\Planning\TreeOfThoughtsSearch;

/**
 * Phase 30 — TreeOfThoughtsSearch unit tests (5 tests).
 */
class TreeOfThoughtsSearchTest extends TestCase
{
    private TreeOfThoughtsSearch $search;

    protected function setUp(): void
    {
        TreeOfThoughtsSearch::reset();
        $this->search = new TreeOfThoughtsSearch();
    }

    public function testInitializeTreeCreatesValidRootNode(): void
    {
        $tree = $this->search->initializeTree('Refactor database connection pool');

        $this->assertArrayHasKey('tree_id', $tree);
        $this->assertArrayHasKey('root_id', $tree);
        $this->assertSame('node_root', $tree['root_id']);
        $this->assertArrayHasKey('node_root', $tree['nodes']);
        $this->assertSame('selected', $tree['nodes']['node_root']['status']);
    }

    public function testExpandNodeGeneratesCandidateBranches(): void
    {
        $tree = $this->search->initializeTree('Test goal');
        $treeId = $tree['tree_id'];

        $candidates = [
            ['thought' => 'Branch Alpha', 'confidence' => 0.8],
            ['thought' => 'Branch Beta', 'confidence' => 0.6],
        ];

        $newNodes = $this->search->expandNode($treeId, 'node_root', $candidates);

        $this->assertCount(2, $newNodes);
        $this->assertSame('node_root_b1', $newNodes[0]['id']);
        $this->assertSame('node_root_b2', $newNodes[1]['id']);
    }

    public function testEvaluateThoughtAndBackpropagation(): void
    {
        $tree = $this->search->initializeTree('Test goal');
        $treeId = $tree['tree_id'];

        $this->search->expandNode($treeId, 'node_root', [['thought' => 'Branch 1', 'confidence' => 0.7]]);
        $evalNode = $this->search->evaluateThought($treeId, 'node_root_b1', 0.9);

        $this->assertSame('evaluated', $evalNode['status']);
        $this->assertSame(0.9, $evalNode['confidence']);

        $updatedTree = $this->search->getTree($treeId);
        $this->assertGreaterThan(0, $updatedTree['nodes']['node_root']['visits']);
    }

    public function testPruneBranchRemovesSubtree(): void
    {
        $tree = $this->search->initializeTree('Test goal');
        $treeId = $tree['tree_id'];

        $this->search->expandNode($treeId, 'node_root', [
            ['thought' => 'Branch Low Confidence', 'confidence' => 0.2]
        ]);

        // Evaluate with low confidence (< 0.35) -> should trigger pruning
        $evalNode = $this->search->evaluateThought($treeId, 'node_root_b1', 0.2);

        $this->assertSame('pruned', $evalNode['status']);
    }

    public function testSearchReturnsBestPathAndFullTree(): void
    {
        $res = $this->search->search('Optimize cache eviction policies', 2, 2);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('tree_id', $res);
        $this->assertArrayHasKey('best_path', $res);
        $this->assertNotEmpty($res['best_path']);
        $this->assertSame('node_root', $res['best_path'][0]);
        $this->assertGreaterThan(1, $res['total_nodes']);
    }
}
