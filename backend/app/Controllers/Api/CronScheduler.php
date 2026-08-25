<?php

namespace App\Controllers\Api;

use Atom\Automation\DistributedCronSchedulerEngine;
use Atom\Automation\DistributedJobStore;

/**
 * CronScheduler API Controller — Phase 49
 */
class CronScheduler extends BaseApiController
{
    private static ?DistributedCronSchedulerEngine $engine = null;
    private static ?DistributedJobStore $store = null;

    private function getEngine(): DistributedCronSchedulerEngine
    {
        if (self::$engine === null) {
            self::$engine = new DistributedCronSchedulerEngine();
        }
        return self::$engine;
    }

    private function getStore(): DistributedJobStore
    {
        if (self::$store === null) {
            self::$store = new DistributedJobStore($this->getEngine());
        }
        return self::$store;
    }

    /**
     * GET /api/cron/jobs
     */
    public function listJobs()
    {
        $store = $this->getStore();
        return $this->respondSuccess([
            'total_jobs' => $store->count(),
            'jobs' => $store->listJobs(),
        ], 'Scheduled distributed jobs listed');
    }

    /**
     * POST /api/cron/jobs
     */
    public function createJob()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (empty($json['name']) || empty($json['cron_expression'])) {
            return $this->respondError('Job name and cron expression are required', 400);
        }

        $store = $this->getStore();
        $job = $store->addJob($json);

        return $this->respondSuccess(['job' => $job], 'Distributed cron job scheduled');
    }

    /**
     * POST /api/cron/jobs/trigger
     */
    public function triggerJob()
    {
        $json = $this->request->getJSON(true) ?? [];
        $jobId = $json['job_id'] ?? '';

        $store = $this->getStore();
        $engine = $this->getEngine();

        $job = $store->getJob($jobId);
        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        $execution = $engine->executeJob($job);
        $updated = $store->updateJobStatus($jobId, $execution['status'], $execution);

        return $this->respondSuccess([
            'execution' => $execution,
            'job' => $updated,
        ], 'Cron job triggered immediately');
    }

    /**
     * POST /api/cron/lease/renew
     */
    public function renewLease()
    {
        $engine = $this->getEngine();
        $lease = $engine->acquireLeaderLease();

        return $this->respondSuccess($lease, 'Distributed leader lease renewed');
    }

    /**
     * GET /api/cron/cluster/status
     */
    public function clusterStatus()
    {
        $engine = $this->getEngine();
        $lease = $engine->acquireLeaderLease();

        return $this->respondSuccess([
            'cluster_leader' => $engine->getLeaderNodeId(),
            'current_node' => $engine->getCurrentNodeId(),
            'consensus_protocol' => 'Raft Distributed Lease',
            'lease_info' => $lease,
            'total_scheduled_jobs' => $this->getStore()->count(),
        ], 'Distributed cluster status');
    }

    /**
     * DELETE /api/cron/jobs/{id}
     */
    public function deleteJob($id = null)
    {
        if (!$id) {
            return $this->respondError('Job ID required', 400);
        }

        $store = $this->getStore();
        $deleted = $store->removeJob((string)$id);

        if ($deleted) {
            return $this->respondSuccess(['deleted' => true, 'id' => $id], 'Job removed from schedule');
        }
        return $this->respondError('Job not found', 404);
    }
}
