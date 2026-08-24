<?php

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\DependencyGraphAnalyzer;

/**
 * Phase 35 — DependencyGraphAnalyzer unit tests (5 tests).
 */
class DependencyGraphAnalyzerTest extends TestCase
{
    private DependencyGraphAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new DependencyGraphAnalyzer();
    }

    public function testCouplingAndInstabilityMetrics(): void
    {
        $graph = [
            'Controller' => ['ServiceA', 'ServiceB'],
            'ServiceA'   => ['Repository'],
            'ServiceB'   => ['Repository'],
            'Repository' => [],
        ];

        $res = $this->analyzer->analyze($graph);

        $this->assertSame(4, $res['total_nodes']);
        // Controller has Ce = 2, Ca = 0 -> Instability = 1.0 (Volatile)
        $this->assertEqualsWithDelta(1.0, $res['nodes']['Controller']['instability_index'], 0.01);
        // Repository has Ca = 2, Ce = 0 -> Instability = 0.0 (Stable)
        $this->assertEqualsWithDelta(0.0, $res['nodes']['Repository']['instability_index'], 0.01);
        $this->assertSame('STABLE', $res['nodes']['Repository']['stability_class']);
    }

    public function testDetectCircularDependencyCycle(): void
    {
        // A -> B -> C -> A
        $graph = [
            'A' => ['B'],
            'B' => ['C'],
            'C' => ['A'],
        ];

        $res = $this->analyzer->analyze($graph);

        $this->assertTrue($res['has_cycles']);
        $this->assertNotEmpty($res['circular_cycles']);
    }

    public function testAcyclicGraphHasNoCycles(): void
    {
        $graph = [
            'Root'   => ['Layer1'],
            'Layer1' => ['Layer2'],
            'Layer2' => [],
        ];

        $res = $this->analyzer->analyze($graph);

        $this->assertFalse($res['has_cycles']);
        $this->assertEmpty($res['circular_cycles']);
    }

    public function testEmptyGraphReturnsEmptyStructure(): void
    {
        $res = $this->analyzer->analyze([]);

        $this->assertSame(0, $res['total_nodes']);
        $this->assertFalse($res['has_cycles']);
        $this->assertEmpty($res['nodes']);
    }

    public function testSingleIsolatedNode(): void
    {
        $graph = ['Standalone' => []];
        $res = $this->analyzer->analyze($graph);

        $this->assertSame(1, $res['total_nodes']);
        $this->assertSame(0, $res['nodes']['Standalone']['afferent_coupling']);
        $this->assertSame(0, $res['nodes']['Standalone']['efferent_coupling']);
        $this->assertSame(0.0, $res['nodes']['Standalone']['instability_index']);
    }
}
