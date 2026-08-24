<?php

use PHPUnit\Framework\TestCase;
use Atom\Evaluation\SandboxExecutor;
use Atom\Evaluation\EvaluationRunner;

class EvaluationRunnerAndSandboxTest extends TestCase
{
    public function testSandboxExecutorEnforcesReadOnlyMode()
    {
        $sandbox = new SandboxExecutor();
        $ctx = $sandbox->enforceSandboxMode();

        $this->assertTrue($ctx['sandbox_active']);
        $this->assertFalse($ctx['allow_writes']);
        $this->assertTrue($ctx['mock_destructive']);
    }

    public function testEvaluationRunnerExecutesRunCleanly()
    {
        $runner = new EvaluationRunner();
        $run = $runner->runEvaluation(1, 'agent', '1');

        $this->assertEquals('completed', $run->status);
        $this->assertEquals(0.95, $run->aggregateScore);
    }
}
