<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\SuperAgentCenturyMatrixEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 100 — SuperAgentCenturyMatrixEngine unit tests (6 tests).
 */
class SuperAgentCenturyMatrixEngineTest extends TestCase
{
    private SuperAgentCenturyMatrixEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SuperAgentCenturyMatrixEngine(new SecretRedactor());
    }

    public function testDispatchMatrixReturnsFullExecutionEnvelope(): void
    {
        $res = $this->engine->dispatchMatrix('Run full platform audit', 'test_user');

        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('plan_id', $res);
        $this->assertSame('100_PERCENT_OPERATIONAL_CENTURY_LANDMARK', $res['century_status']);
        $this->assertArrayHasKey('strategic_planner_agent', $res['agents']);
        $this->assertArrayHasKey('security_verifier_agent', $res['agents']);
        $this->assertArrayHasKey('execution_runner_agent', $res['agents']);
        $this->assertArrayHasKey('auditor_self_healing_agent', $res['agents']);
    }

    public function testDispatchEmptyPromptFailsGracefully(): void
    {
        $res = $this->engine->dispatchMatrix('');
        $this->assertFalse($res['success']);
        $this->assertSame('Task prompt cannot be empty', $res['error']);
    }

    public function testGetCenturyPlatformStatusReportsAllSubsystems(): void
    {
        $status = $this->engine->getCenturyPlatformStatus();

        $this->assertSame(100, $status['total_phases']);
        $this->assertEquals(100.0, $status['health_score']);
        $this->assertSame(8, $status['subsystems_count']);
        $this->assertArrayHasKey('Post-Quantum & ZKP Security', $status['subsystems']);
        $this->assertArrayHasKey('Voice & Audio DSP', $status['subsystems']);
    }

    public function testTargetDomainsFilteringPreserved(): void
    {
        $customDomains = ['Voice & Audio DSP', 'Post-Quantum & ZKP Security'];
        $res = $this->engine->dispatchMatrix('Execute target audio security check', 'admin', $customDomains);

        $this->assertTrue($res['success']);
        $this->assertSame($customDomains, $res['target_domains']);
    }

    public function testExecutionTimeIsFiniteAndPositive(): void
    {
        $res = $this->engine->dispatchMatrix('Compute speed test');
        $this->assertGreaterThan(0.0, $res['execution_time_ms']);
    }

    public function testCenturyMilestoneKeyPresent(): void
    {
        $status = $this->engine->getCenturyPlatformStatus();
        $this->assertSame('PHASE 100 GRAND FINALE ACHIEVED', $status['century_milestone']);
    }
}
