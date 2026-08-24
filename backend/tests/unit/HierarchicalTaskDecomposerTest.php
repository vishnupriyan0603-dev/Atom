<?php

use PHPUnit\Framework\TestCase;
use Atom\Planning\HierarchicalTaskDecomposer;

/**
 * Phase 30 — HierarchicalTaskDecomposer unit tests (5 tests).
 */
class HierarchicalTaskDecomposerTest extends TestCase
{
    private HierarchicalTaskDecomposer $decomposer;

    protected function setUp(): void
    {
        $this->decomposer = new HierarchicalTaskDecomposer();
    }

    public function testDecomposeComplexGoalGeneratesMultiStageHierarchy(): void
    {
        $goal = 'Build and deploy a real-time event streaming pipeline';
        $plan = $this->decomposer->decompose($goal, [], 2);

        $this->assertIsArray($plan);
        $this->assertArrayHasKey('tree_id', $plan);
        $this->assertArrayHasKey('nodes', $plan);
        $this->assertArrayHasKey('execution_order', $plan);
        $this->assertGreaterThanOrEqual(4, $plan['total_nodes']);
        $this->assertSame('node_root', $plan['root_id']);
        $this->assertSame($goal, $plan['goal']);
    }

    public function testComputeExecutionOrderRespectsTopologicalDependencies(): void
    {
        $goal = 'Create an encrypted data storage module with unit tests';
        $plan = $this->decomposer->decompose($goal, [], 2);

        $order = $plan['execution_order'];
        $this->assertIsArray($order);
        $this->assertContains('node_root', $order);
        $this->assertSame('node_root', $order[0]); // Root must precede subtasks

        // If node_2 has node_1 in dependencies, node_1 must appear before node_2
        $idx1 = array_search('node_1', $order, true);
        $idx2 = array_search('node_2', $order, true);
        if ($idx1 !== false && $idx2 !== false) {
            $this->assertLessThan($idx2, $idx1);
        }
    }

    public function testDecomposeSimpleGoalGeneratesSingleStepPlan(): void
    {
        $goal = 'What is the current time?';
        $plan = $this->decomposer->decompose($goal, [], 2);

        $this->assertSame(2, $plan['total_nodes']); // root + 1 reasoning step
        $this->assertArrayHasKey('node_1', $plan['nodes']);
        $this->assertSame('reasoning', $plan['nodes']['node_1']['type']);
    }

    public function testDepthLimitEnforcement(): void
    {
        $goal = 'Build high-performance microservices framework';
        $plan1 = $this->decomposer->decompose($goal, [], 1);
        $plan2 = $this->decomposer->decompose($goal, [], 2);

        $this->assertSame(1, $plan1['max_depth']);
        $this->assertSame(2, $plan2['max_depth']);
        $this->assertLessThan($plan2['total_nodes'], $plan1['total_nodes']);
    }

    public function testEmptyGoalThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->decomposer->decompose('   ');
    }
}
