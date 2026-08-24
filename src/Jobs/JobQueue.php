<?php

namespace Atom\Jobs;

use CodeIgniter\Database\BaseConnection;

class JobQueue
{
    private function getDb(): BaseConnection
    {
        return \Config\Database::connect();
    }

    public function dispatch(string $type, array $payload = [], int $maxAttempts = 3): Job
    {
        $job = new Job(type: $type, payload: $payload, maxAttempts: $maxAttempts);
        $db = $this->getDb();

        $data = $job->toArray();
        unset($data['id']);

        if ($db->table($db->prefixTable('atom_jobs'), true)->insert($data)) {
            $job->id = (int)$db->insertID();
        }

        return $job;
    }

    public function getNextQueuedJob(): ?Job
    {
        $db = $this->getDb();
        $row = $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('status', 'queued')
                  ->where('attempts < max_attempts', null, false)
                  ->orderBy('id', 'ASC')
                  ->get()
                  ->getRowArray();

        return $row ? Job::fromArray($row) : null;
    }

    public function markRunning(int $jobId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('id', $jobId)
                  ->set('attempts', 'attempts + 1', false)
                  ->set('status', 'running')
                  ->set('started_at', date('Y-m-d H:i:s'))
                  ->update();
    }

    public function markCompleted(int $jobId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('id', $jobId)
                  ->update([
                      'status'       => 'completed',
                      'completed_at' => date('Y-m-d H:i:s'),
                      'error'        => null,
                  ]);
    }

    public function markFailed(int $jobId, string $error): bool
    {
        $db = $this->getDb();
        $row = $db->table($db->prefixTable('atom_jobs'), true)->where('id', $jobId)->get()->getRowArray();
        $attempts = (int)($row['attempts'] ?? 1);
        $maxAttempts = (int)($row['max_attempts'] ?? 3);

        $newStatus = ($attempts >= $maxAttempts) ? 'failed' : 'queued';

        return $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('id', $jobId)
                  ->update([
                      'status' => $newStatus,
                      'error'  => $error,
                  ]);
    }

    public function retryJob(int $jobId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('id', $jobId)
                  ->update([
                      'status'     => 'queued',
                      'attempts'   => 0,
                      'error'      => null,
                      'started_at' => null,
                  ]);
    }

    public function cancelJob(int $jobId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_jobs'), true)
                  ->where('id', $jobId)
                  ->update([
                      'status' => 'cancelled',
                  ]);
    }

    public function getJobs(?string $status = null, int $limit = 50): array
    {
        $db = $this->getDb();
        $builder = $db->table($db->prefixTable('atom_jobs'), true);
        if ($status !== null && $status !== '') {
            $builder->where('status', strtolower($status));
        }

        $rows = $builder->orderBy('id', 'DESC')->limit($limit)->get()->getResultArray();
        return array_map(fn($r) => Job::fromArray($r), $rows);
    }
}
