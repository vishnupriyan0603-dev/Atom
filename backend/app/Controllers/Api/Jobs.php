<?php

namespace App\Controllers\Api;

use Atom\Jobs\JobQueue;
use Atom\Jobs\Worker;

class Jobs extends BaseApiController
{
    public function list()
    {
        $status = $this->request->getGet('status');
        $queue = new JobQueue();
        $jobs = $queue->getJobs($status);
        $data = array_map(fn($j) => $j->toArray(), $jobs);
        return $this->respondSuccess($data);
    }

    public function dispatch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $type = trim($json['type'] ?? 'general');
        $payload = $json['payload'] ?? [];
        $maxAttempts = (int)($json['max_attempts'] ?? 3);

        if (empty($type)) {
            return $this->respondError('job type is required');
        }

        $queue = new JobQueue();
        $job = $queue->dispatch($type, $payload, $maxAttempts);

        return $this->respondSuccess($job->toArray(), 'Job dispatched to background queue');
    }

    public function processNext()
    {
        $worker = new Worker();
        $result = $worker->processNextJob();

        if ($result !== null) {
            return $this->respondSuccess($result, 'Next queued job executed');
        }

        return $this->respondSuccess(null, 'No queued jobs available');
    }

    public function retry($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Job ID required');
        }

        $queue = new JobQueue();
        $success = $queue->retryJob((int)$id);

        if ($success) {
            return $this->respondSuccess(null, "Job #{$id} reset to queued state");
        }
        return $this->respondError("Failed to retry job #{$id}");
    }

    public function cancel($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Job ID required');
        }

        $queue = new JobQueue();
        $success = $queue->cancelJob((int)$id);

        if ($success) {
            return $this->respondSuccess(null, "Job #{$id} cancelled");
        }
        return $this->respondError("Failed to cancel job #{$id}");
    }
}
