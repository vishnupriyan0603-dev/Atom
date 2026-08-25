<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Automation\DistributedJobStore;
use Atom\Automation\DistributedCronSchedulerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 49 — DistributedJobStore unit tests (6 tests).
 */
class DistributedJobStoreTest extends TestCase
{
    private DistributedJobStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new DistributedJobStore(new DistributedCronSchedulerEngine(), new SecretRedactor());
    }

    public function testDefaultDistributedJobsSeeded(): void
    {
        $this->assertGreaterThanOrEqual(4, $this->store->count());
        $job = $this->store->getJob('JOB_SYSTEM_ANOMALY_SCAN');

        $this->assertNotNull($job);
        $this->assertSame('SCHEDULED', $job['status']);
        $this->assertNotEmpty($job['next_run_human']);
    }

    public function testAddNewDistributedJob(): void
    {
        $newJob = $this->store->addJob([
            'id' => 'JOB_CUSTOM_CLEANUP',
            'name' => 'Custom Memory & Temp Cleanup',
            'cron_expression' => '*/30 * * * *',
            'target_action' => 'cleanup_temp_files',
        ]);

        $this->assertSame('JOB_CUSTOM_CLEANUP', $newJob['id']);
        $this->assertNotNull($this->store->getJob('JOB_CUSTOM_CLEANUP'));
    }

    public function testUpdateJobStatusToCompletedRecalculatesNextRun(): void
    {
        $job = $this->store->getJob('JOB_EDGE_MESH_GOSSIP_PING');
        $initialNextRun = $job['next_run'];

        $updated = $this->store->updateJobStatus('JOB_EDGE_MESH_GOSSIP_PING', 'COMPLETED', ['attempts' => 1]);

        $this->assertSame('SCHEDULED', $updated['status']);
        $this->assertNotNull($updated['last_run']);
    }

    public function testRemoveJobFromSchedule(): void
    {
        $this->store->addJob(['id' => 'JOB_TEMP', 'name' => 'Temp']);
        $this->assertTrue($this->store->removeJob('JOB_TEMP'));
        $this->assertNull($this->store->getJob('JOB_TEMP'));
    }

    public function testListJobsReturnsAllRecords(): void
    {
        $jobs = $this->store->listJobs();
        $this->assertIsArray($jobs);
        $this->assertCount($this->store->count(), $jobs);
    }

    public function testGetNonExistentJobReturnsNull(): void
    {
        $this->assertNull($this->store->getJob('NON_EXISTENT_CRON'));
    }
}
