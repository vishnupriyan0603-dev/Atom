<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Automation\DistributedCronSchedulerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 49 — DistributedCronSchedulerEngine unit tests (6 tests).
 */
class DistributedCronSchedulerEngineTest extends TestCase
{
    private DistributedCronSchedulerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DistributedCronSchedulerEngine('node_test_01', 30, new SecretRedactor());
    }

    public function testCalculateNextRunEveryMinute(): void
    {
        $now = 1787640000;
        $next = $this->engine->calculateNextRun('* * * * *', $now);

        $this->assertSame($now + 60, $next);
    }

    public function testCalculateNextRunIntervalMinutes(): void
    {
        $now = strtotime('2026-08-25 15:02:00');
        $next = $this->engine->calculateNextRun('*/5 * * * *', $now);

        // Next 5-min interval after 15:02 is 15:05
        $this->assertSame(strtotime('2026-08-25 15:05:00'), $next);
    }

    public function testCalculateNextRunDailyMidnight(): void
    {
        $now = strtotime('2026-08-25 12:00:00');
        $next = $this->engine->calculateNextRun('0 0 * * *', $now);

        // Next midnight is 2026-08-26 00:00:00
        $this->assertSame(strtotime('2026-08-26 00:00:00'), $next);
    }

    public function testAcquireAndRenewLeaderLease(): void
    {
        $lease1 = $this->engine->acquireLeaderLease();
        $this->assertTrue($lease1['success']);
        $this->assertTrue($lease1['is_leader']);
        $this->assertSame('node_test_01', $lease1['leader_node_id']);

        // Renew same node
        $lease2 = $this->engine->acquireLeaderLease();
        $this->assertTrue($lease2['success']);
        $this->assertTrue($lease2['is_leader']);
    }

    public function testExecuteJobSuccess(): void
    {
        $job = ['id' => 'job_01', 'max_retries' => 3, 'attempts' => 0];
        $exec = $this->engine->executeJob($job);

        $this->assertSame('job_01', $exec['job_id']);
        $this->assertSame('COMPLETED', $exec['status']);
        $this->assertSame(1, $exec['attempts']);
        $this->assertSame('node_test_01', $exec['executed_by_node']);
    }

    public function testExecuteJobFailureTransitionsToDlq(): void
    {
        $poisonJob = ['id' => 'job_fail', 'max_retries' => 3, 'attempts' => 2, 'force_fail' => true];
        $exec = $this->engine->executeJob($poisonJob);

        $this->assertSame('DEAD_LETTER_QUEUE', $exec['status']);
        $this->assertSame(3, $exec['attempts']);
    }
}
