<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Atom\Jobs\JobQueue;
use Atom\Jobs\Worker;

final class BackgroundJobsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';

    public function testJobDispatchAndWorkerProcessing(): void
    {
        $queue = new JobQueue();
        $worker = new Worker($queue);

        // Dispatch job
        $job = $queue->dispatch('document_indexing', ['doc_id' => 42], maxAttempts: 3);
        $this->assertGreaterThan(0, $job->id);
        $this->assertEquals('queued', $job->status);

        // Process job
        $result = $worker->processNextJob();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($job->id, $result['job_id']);

        // Check job is completed
        $completedJobs = $queue->getJobs('completed');
        $this->assertNotEmpty($completedJobs);
        $this->assertEquals('completed', $completedJobs[0]->status);
    }

    public function testJobRetryAndCancellation(): void
    {
        $queue = new JobQueue();

        $job = $queue->dispatch('backup', ['type' => 'full'], maxAttempts: 2);
        $this->assertGreaterThan(0, $job->id);

        // Cancel job
        $cancelled = $queue->cancelJob($job->id);
        $this->assertTrue($cancelled);

        $cancelledJobs = $queue->getJobs('cancelled');
        $this->assertNotEmpty($cancelledJobs);

        // Retry job
        $retried = $queue->retryJob($job->id);
        $this->assertTrue($retried);

        $queuedJobs = $queue->getJobs('queued');
        $this->assertNotEmpty($queuedJobs);
    }
}
