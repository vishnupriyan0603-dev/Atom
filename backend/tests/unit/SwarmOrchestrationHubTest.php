<?php

namespace Tests\Unit;

use Atom\Swarm\SwarmOrchestrationHub;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 — SwarmOrchestrationHub unit tests (5 tests).
 */
class SwarmOrchestrationHubTest extends TestCase
{
    private SwarmOrchestrationHub $hub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hub = new SwarmOrchestrationHub();
    }

    public function testGetSwarmTopologyReturnsValidStructure(): void
    {
        $topology = $this->hub->getSwarmTopology();
        $this->assertIsArray($topology);
        $this->assertArrayHasKey('total_agents', $topology);
        $this->assertArrayHasKey('agents', $topology);
        $this->assertGreaterThanOrEqual(4, $topology['total_agents']);
        $this->assertEquals('weighted_majority_vote', $topology['consensus_engine']);
    }

    public function testPlanSwarmExecutionGeneratesWorkOrders(): void
    {
        $plan = $this->hub->planSwarmExecution('Build an autonomous load balancer with circuit breaker');
        $this->assertIsArray($plan);
        $this->assertArrayHasKey('plan_id', $plan);
        $this->assertArrayHasKey('work_orders', $plan);
        $this->assertNotEmpty($plan['work_orders']);
        $this->assertEquals('architect', $plan['work_orders'][0]['agent_role']);
    }

    public function testPlanSwarmExecutionRejectsEmptyGoal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->hub->planSwarmExecution('   ');
    }

    public function testEvaluateConsensusCalculatesWeightedScore(): void
    {
        $claims = [
            ['role' => 'architect', 'verdict' => 'approve', 'confidence' => 0.95],
            ['role' => 'coder', 'verdict' => 'approve', 'confidence' => 0.90],
            ['role' => 'security', 'verdict' => 'approve', 'confidence' => 0.98],
            ['role' => 'reviewer', 'verdict' => 'reject', 'confidence' => 0.40]
        ];

        $consensus = $this->hub->evaluateConsensus($claims);
        $this->assertIsArray($consensus);
        $this->assertTrue($consensus['is_accepted']);
        $this->assertGreaterThan(0.65, $consensus['consensus_score']);
        $this->assertEquals('commit_artifact', $consensus['action_taken']);
    }

    public function testSynthesizeArtifactCreatesUnifiedSummary(): void
    {
        $contributions = [
            ['role' => 'architect', 'output' => 'Defined DAG contracts'],
            ['role' => 'coder', 'output' => 'Implemented worker loops'],
            ['role' => 'security', 'output' => 'Verified input boundaries']
        ];

        $artifact = $this->hub->synthesizeArtifact('Swarm Release 41', $contributions);
        $this->assertIsArray($artifact);
        $this->assertTrue($artifact['verified']);
        $this->assertEquals(3, $artifact['sections_count']);
        $this->assertNotEmpty($artifact['integrity_hash']);
    }
}
