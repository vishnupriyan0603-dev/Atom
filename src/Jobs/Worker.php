<?php

namespace Atom\Jobs;

class Worker
{
    private JobQueue $queue;

    public function __construct(?JobQueue $queue = null)
    {
        $this->queue = $queue ?? new JobQueue();
    }

    /**
     * Processes the next available job in the queue.
     */
    public function processNextJob(): ?array
    {
        $job = $this->queue->getNextQueuedJob();
        if ($job === null || $job->id === null) {
            return null;
        }

        $this->queue->markRunning($job->id);

        try {
            $result = $this->executeJobPayload($job);
            $this->queue->markCompleted($job->id);
            return [
                'success' => true,
                'job_id'  => $job->id,
                'type'    => $job->type,
                'output'  => $result,
            ];
        } catch (\Throwable $e) {
            $this->queue->markFailed($job->id, $e->getMessage());
            return [
                'success' => false,
                'job_id'  => $job->id,
                'type'    => $job->type,
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function executeJobPayload(Job $job): string
    {
        switch ($job->type) {
            case 'document_indexing':
            case 'embedding_generation':
                return "Processed document indexing job payload.";
            case 'backup':
                return "Processed database export backup job payload.";
            case 'model_evaluation':
            case 'learning_analysis':
                return "Processed evaluation benchmark job payload.";
            default:
                return "Executed general background job '{$job->type}'.";
        }
    }
}
