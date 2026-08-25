<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\MetacognitiveReasoningEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 80 Landmark — MetacognitiveReasoningEngine unit tests (6 tests).
 */
class MetacognitiveReasoningEngineTest extends TestCase
{
    private MetacognitiveReasoningEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new MetacognitiveReasoningEngine(new SecretRedactor());
    }

    public function testReflectOnValidRigorousThoughtChain(): void
    {
        $steps = [
            'Analyze problem parameters and input constraints',
            'Formulate hypothesis regarding boundary conditions',
            'Verify consistency with database schema',
            'Synthesize optimal response'
        ];

        $res = $this->engine->reflectOnThoughtChain($steps, 'optimize_query');

        $this->assertTrue($res['success']);
        $this->assertSame('optimize_query', $res['goal']);
        $this->assertSame(4, $res['total_steps_evaluated']);
        $this->assertSame(0, $res['flaws_count']);
        $this->assertSame('REASONING_RIGOROUS', $res['status']);
        $this->assertGreaterThan(90.0, $res['metacognitive_clarity_pct']);
    }

    public function testDetectCircularLoopInThoughtChain(): void
    {
        $steps = [
            'Step 1: Check cache validity',
            'Step 2: Database lookup on cache miss',
            'Step 1: Check cache validity', // repeated loop
        ];

        $res = $this->engine->reflectOnThoughtChain($steps);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['flaws_count']);
        $this->assertSame('CORRECTION_REQUIRED', $res['status']);
        $this->assertSame('CIRCULAR_LOOP_DETECTED', $res['steps'][2]['status']);
    }

    public function testDetectPrematureUnverifiedAssumption(): void
    {
        $steps = [
            'Obviously the user is authorized without token verification',
            'Proceed with privileged action'
        ];

        $res = $this->engine->reflectOnThoughtChain($steps);

        $this->assertTrue($res['success']);
        $this->assertSame('PREMATURE_ASSUMPTION', $res['steps'][0]['status']);
    }

    public function testPruneThoughtGraphLowConfidenceBranches(): void
    {
        $graph = [
            ['id' => 'node_1', 'thought' => 'Sound path', 'confidence' => 0.95, 'status' => 'VALID_STEP'],
            ['id' => 'node_2', 'thought' => 'Circular path', 'confidence' => 0.20, 'status' => 'CIRCULAR_LOOP_DETECTED'],
            ['id' => 'node_3', 'thought' => 'Weak hypothesis', 'confidence' => 0.40, 'status' => 'WEAK'],
        ];

        $pruned = $this->engine->pruneThoughtGraph($graph, 0.60);

        $this->assertTrue($pruned['success']);
        $this->assertSame(1, $pruned['retained_nodes_count']);
        $this->assertSame(2, $pruned['pruned_nodes_count']);
        $this->assertSame('node_1', $pruned['retained_graph'][0]['id']);
    }

    public function testEmptyStepsArrayFailsGracefully(): void
    {
        $res = $this->engine->reflectOnThoughtChain([]);
        $this->assertFalse($res['success']);
        $this->assertSame(0.0, $res['metacognitive_clarity_pct']);
    }

    public function testGetMetacognitiveMetrics(): void
    {
        $metrics = $this->engine->getMetacognitiveMetrics();

        $this->assertArrayHasKey('engine', $metrics);
        $this->assertArrayHasKey('active_heuristics', $metrics);
        $this->assertTrue($metrics['circular_loop_detection']);
    }
}
