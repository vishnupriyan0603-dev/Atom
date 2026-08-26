<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomGoalPlannerEngine;
use Atom\Security\SecretRedactor;

/**
 * Security and safety pass test suite for Atom Brain Phase 5 (Autonomous Goal Planner & Self-Correction).
 */
class AtomBrainPhase5SecurityPassTest extends TestCase
{
    private AtomGoalPlannerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomGoalPlannerEngine(new SecretRedactor());
    }

    public function testSecretRedactionInGoalDecomposition(): void
    {
        $secretGoal = 'Deploy service with API_KEY = "sk-proj-supersecretkey1234567890abcdef" to server 192.168.1.100';
        $res = $this->engine->createPlan($secretGoal);

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-proj-supersecretkey1234567890abcdef', $res['goal']);
        $this->assertStringNotContainsString('sk-proj-supersecretkey1234567890abcdef', json_encode($res['tasks']));
    }

    public function testMaxRetryLimitAndRollbackTrigger(): void
    {
        $planRes = $this->engine->createPlan('Test Fail', 'cicd_deploy');
        $plan = $planRes;

        // Fail 3 times (within retry budget)
        for ($i = 0; $i < 3; $i++) {
            $stepRes = $this->engine->advanceStep($plan, 'step_1', false, "Network packet drop #{$i}");
            $this->assertTrue($stepRes['success']);
            $plan = $stepRes['plan'];
            $this->assertEquals('self_correcting', $plan['tasks'][0]['status']);
        }

        // 4th failure -> exceeds max retry limit (3) -> triggers unrecoverable failure and rollback action
        $fourthFail = $this->engine->advanceStep($plan, 'step_1', false, 'Network packet drop #4');
        $this->assertTrue($fourthFail['success']);
        $finalTask = $fourthFail['plan']['tasks'][0];

        $this->assertEquals('failed_unrecoverable', $finalTask['status']);
        $this->assertStringContainsString('Max retry limit', $finalTask['error']);
        $this->assertNotEmpty($finalTask['rollback_action']);
    }

    public function testHighThroughputPlanDecomposition(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->engine->createPlan("Autonomous engineering goal #{$i} for benchmarking");
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.5, $elapsed, '50 goal decompositions should finish in under 1.5s');
    }

    public function testEmptyGoalAndInvalidStepHandling(): void
    {
        $emptyRes = $this->engine->createPlan('');
        $this->assertFalse($emptyRes['success']);

        $invalidStep = $this->engine->advanceStep(['tasks' => []], 'non_existent_task');
        $this->assertFalse($invalidStep['success']);
    }
}
