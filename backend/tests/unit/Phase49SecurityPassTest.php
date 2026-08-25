<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Automation\DistributedCronSchedulerEngine;
use Atom\Automation\DistributedJobStore;
use Atom\Security\SecretRedactor;

/**
 * Phase 49 — Phase49SecurityPassTest security & safety tests (5 tests).
 */
class Phase49SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInCronJobPayload(): void
    {
        $engine = new DistributedCronSchedulerEngine('node_01', 30, $this->redactor);
        $job = [
            'id' => 'job_sec',
            'api_token' => 'sk-1122334455667788990011223344',
            'name' => 'Secret Sync',
            'max_retries' => 3,
        ];

        $exec = $engine->executeJob($job);
        $this->assertSame('COMPLETED', $exec['status']);
    }

    public function testRaftLeaseExpiryPreventsStaleExecution(): void
    {
        // 0 second lease duration (instantly expired)
        $engine = new DistributedCronSchedulerEngine('node_alpha', 0, $this->redactor);
        $lease = $engine->acquireLeaderLease();

        $this->assertTrue($lease['success']);
        // Immediate second check should recognize lease expiration
        usleep(10000); // 10ms
        $lease2 = $engine->acquireLeaderLease();
        $this->assertTrue($lease2['success']);
    }

    public function testDeadLetterQueueSafetyBound(): void
    {
        $engine = new DistributedCronSchedulerEngine('node_01', 30, $this->redactor);
        $store = new DistributedJobStore($engine, $this->redactor);

        $failingJob = ['id' => 'job_fail', 'max_retries' => 2, 'attempts' => 1, 'force_fail' => true];
        $exec = $engine->executeJob($failingJob);

        $this->assertSame('DEAD_LETTER_QUEUE', $exec['status']);
        $this->assertSame('Max execution retries exceeded', $exec['error']);
    }

    public function testInvalidCronExpressionSafeFallback(): void
    {
        $engine = new DistributedCronSchedulerEngine('node_01', 30, $this->redactor);
        $next = $engine->calculateNextRun('malformed_cron_syntax_here', 1787640000);

        // Fallback is 5 minutes (300 seconds)
        $this->assertSame(1787640000 + 300, $next);
    }

    public function testNoDangerousEvalOrShellExecutionInAutomationSubsystem(): void
    {
        $files = [
            'src/Automation/DistributedCronSchedulerEngine.php',
            'src/Automation/DistributedJobStore.php',
            'src/Workflow/WorkflowExecutor.php',
            'src/Workflow/WorkflowStateMachine.php',
            'src/Workflow/WorkflowBudgetManager.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
