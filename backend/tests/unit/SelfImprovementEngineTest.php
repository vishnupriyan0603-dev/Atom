<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Atom\Brain\SelfImprovementEngine;
use Atom\Security\HumanApprovalGate;

final class SelfImprovementEngineTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    public function testLogEvaluationAndDetectFlaws(): void
    {
        $engine = new SelfImprovementEngine();
        $success = $engine->logEvaluation(1, 1, 'v1.0', 'ollama-llama3.1', 3, 'good', 0.95, 120, 150);
        $this->assertTrue($success);

        $flaws = $engine->detectFlaws();
        $this->assertSame('success', $flaws['status']);
        $this->assertGreaterThanOrEqual(1, $flaws['total_evaluations']);
    }

    public function testSandboxExperimentAndHumanApprovalGate(): void
    {
        $engine = new SelfImprovementEngine();
        $gate = new HumanApprovalGate();

        // 1. Create experiment
        $expId = $engine->createExperiment(
            'Test Prompt Sandbox Experiment',
            'prompt_template',
            ['temperature' => 0.7],
            ['temperature' => 0.4]
        );
        $this->assertGreaterThan(0, $expId);

        // 2. Evaluate benchmark (+15% improvement > +5% threshold) -> triggers human approval gate
        $evalResult = $engine->evaluateExperiment($expId, 0.80, 0.92);
        $this->assertSame('awaiting_human_approval', $evalResult['status']);

        $approvalId = $evalResult['approval_id'];
        $this->assertGreaterThan(0, $approvalId);

        // 3. Test Human Approval Gate authorization
        $pending = $gate->getPendingApprovals();
        $this->assertNotEmpty($pending);

        $approved = $gate->approve($approvalId, 'TEST_OPERATOR');
        $this->assertTrue($approved);
    }
}
