<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomGoalPlannerEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for AtomGoalPlannerEngine (Atom Brain Phase 5).
 */
class AtomGoalPlannerEngineTest extends TestCase
{
    private AtomGoalPlannerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomGoalPlannerEngine(new SecretRedactor());
    }

    public function testCreatePlanFromOpenEndedGoal(): void
    {
        $res = $this->engine->createPlan('Refactor User Authentication module to JWT tokens');
        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['plan_id']);
        $this->assertCount(4, $res['tasks']);
        $this->assertEquals(0, $res['completed_tasks']);
        $this->assertEquals(0.0, $res['progress_percent']);
        $this->assertEquals('initialized', $res['status']);
    }

    public function testCreatePlanFromPresetTemplate(): void
    {
        $res = $this->engine->createPlan('Database Task', 'db_migration');
        $this->assertTrue($res['success']);
        $this->assertEquals('Database Optimization & Migration', $res['template_used']);
        $this->assertCount(4, $res['tasks']);
        $this->assertEquals('step_1', $res['tasks'][0]['id']);
        $this->assertEmpty($res['tasks'][0]['dependencies']);
        $this->assertEquals(['step_1'], $res['tasks'][1]['dependencies']);
    }

    public function testAdvanceStepSequenceAndDependencyCheck(): void
    {
        $planRes = $this->engine->createPlan('Test Plan', 'db_migration');
        $plan = $planRes;

        // Try executing step 2 before step 1 -> should fail due to unmet dependencies
        $step2Fail = $this->engine->advanceStep($plan, 'step_2', true);
        $this->assertFalse($step2Fail['success']);
        $this->assertStringContainsString('dependencies not completed', $step2Fail['error']);

        // Execute step 1 -> succeeds
        $step1Res = $this->engine->advanceStep($plan, 'step_1', true);
        $this->assertTrue($step1Res['success']);
        $this->assertEquals(1, $step1Res['plan']['completed_tasks']);
        $this->assertEquals(25.0, $step1Res['plan']['progress_percent']);

        // Now execute step 2 -> succeeds
        $step2Res = $this->engine->advanceStep($step1Res['plan'], 'step_2', true);
        $this->assertTrue($step2Res['success']);
        $this->assertEquals(2, $step2Res['plan']['completed_tasks']);
        $this->assertEquals(50.0, $step2Res['plan']['progress_percent']);
    }

    public function testAutomatedSelfCorrectionOnFailure(): void
    {
        $planRes = $this->engine->createPlan('Test Plan', 'security_audit');
        $plan = $planRes;

        // Step 1 fails with lock timeout
        $step1Fail = $this->engine->advanceStep($plan, 'step_1', false, 'Database query lock timeout exceeded');
        $this->assertTrue($step1Fail['success']);
        $task = $step1Fail['plan']['tasks'][0];

        $this->assertEquals('self_correcting', $task['status']);
        $this->assertEquals(1, $task['retry_count']);
        $this->assertNotNull($task['recovery_strategy']);
        $this->assertStringContainsString('lock with jitter backoff', $task['recovery_strategy']['remediation_plan']);
    }

    public function testGetTemplatesList(): void
    {
        $templates = $this->engine->getTemplates();
        $this->assertIsArray($templates);
        $this->assertArrayHasKey('db_migration', $templates);
        $this->assertArrayHasKey('security_audit', $templates);
        $this->assertArrayHasKey('test_coverage', $templates);
        $this->assertArrayHasKey('cicd_deploy', $templates);
        $this->assertArrayHasKey('google_internet_research', $templates);
    }

    public function testGoogleInternetResearchTemplate(): void
    {
        $res = $this->engine->createPlan('Internet Research on Vector Embeddings', 'google_internet_research');
        $this->assertTrue($res['success']);
        $this->assertEquals('Google Search & Live Internet Information Harvester', $res['template_used']);
        $this->assertCount(4, $res['tasks']);
        $this->assertEquals('step_1', $res['tasks'][0]['id']);
        $this->assertEquals('google_auth_check', $res['tasks'][0]['action']);
        $this->assertEquals(['step_1'], $res['tasks'][1]['dependencies']);
    }

    public function testExecuteGoogleSearchHarvest(): void
    {
        $harvest = $this->engine->executeGoogleSearchHarvest('PHP 8.4 benchmark and features');
        $this->assertTrue($harvest['success']);
        $this->assertEquals('PHP 8.4 benchmark and features', $harvest['query']);
        $this->assertGreaterThanOrEqual(1, $harvest['total_results']);
        $this->assertNotEmpty($harvest['results']);
        $this->assertArrayHasKey('title', $harvest['results'][0]);
        $this->assertArrayHasKey('link', $harvest['results'][0]);
        $this->assertArrayHasKey('snippet', $harvest['results'][0]);
    }
}
