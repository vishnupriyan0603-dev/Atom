<?php

namespace Atom\Automation;

use Atom\Security\SecretRedactor;

/**
 * DistributedJobStore — Phase 49
 * In-memory & persisted repository for distributed cron job schedules.
 */
class DistributedJobStore
{
    private SecretRedactor $redactor;
    private array $jobs = [];
    private DistributedCronSchedulerEngine $engine;

    public function __construct(?DistributedCronSchedulerEngine $engine = null, ?SecretRedactor $redactor = null)
    {
        $this->engine = $engine ?? new DistributedCronSchedulerEngine();
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->loadDefaultJobs();
    }

    public function addJob(array $job): array
    {
        $id = $job['id'] ?? ('cron_' . uniqid());
        $cronExpr = $job['cron_expression'] ?? '*/5 * * * *';
        $nextRun = $this->engine->calculateNextRun($cronExpr);

        $record = [
            'id' => $id,
            'name' => $job['name'] ?? 'Untitled Scheduled Task',
            'cron_expression' => $cronExpr,
            'target_action' => $job['target_action'] ?? 'system_heartbeat',
            'status' => 'SCHEDULED',
            'max_retries' => (int)($job['max_retries'] ?? 3),
            'attempts' => 0,
            'last_run' => null,
            'next_run' => $nextRun,
            'next_run_human' => date('Y-m-d H:i:s', $nextRun),
            'created_at' => time(),
        ];

        $this->jobs[$id] = $record;
        return $record;
    }

    public function getJob(string $id): ?array
    {
        return $this->jobs[$id] ?? null;
    }

    public function listJobs(): array
    {
        return array_values($this->jobs);
    }

    public function removeJob(string $id): bool
    {
        if (isset($this->jobs[$id])) {
            unset($this->jobs[$id]);
            return true;
        }
        return false;
    }

    public function updateJobStatus(string $id, string $status, ?array $extra = []): ?array
    {
        if (!isset($this->jobs[$id])) {
            return null;
        }

        $this->jobs[$id]['status'] = $status;
        $this->jobs[$id]['last_run'] = time();

        if (isset($extra['attempts'])) {
            $this->jobs[$id]['attempts'] = $extra['attempts'];
        }

        // Recalculate next run if completed
        if ($status === 'COMPLETED') {
            $nextRun = $this->engine->calculateNextRun($this->jobs[$id]['cron_expression']);
            $this->jobs[$id]['next_run'] = $nextRun;
            $this->jobs[$id]['next_run_human'] = date('Y-m-d H:i:s', $nextRun);
            $this->jobs[$id]['status'] = 'SCHEDULED';
            $this->jobs[$id]['attempts'] = 0;
        }

        return $this->jobs[$id];
    }

    public function count(): int
    {
        return count($this->jobs);
    }

    private function loadDefaultJobs(): void
    {
        $this->addJob([
            'id' => 'JOB_SYSTEM_ANOMALY_SCAN',
            'name' => 'System Anomaly & Resource Saturation Scan',
            'cron_expression' => '*/5 * * * *',
            'target_action' => 'run_anomaly_detector',
            'max_retries' => 3,
        ]);

        $this->addJob([
            'id' => 'JOB_SEMANTIC_INDEX_SYNC',
            'name' => 'HNSW Vector Embedding Space Synchronization',
            'cron_expression' => '*/15 * * * *',
            'target_action' => 'sync_vector_embeddings',
            'max_retries' => 3,
        ]);

        $this->addJob([
            'id' => 'JOB_VAULT_KEY_ROTATION_AUDIT',
            'name' => 'Zero-Knowledge Vault Key Rotation & Entropy Audit',
            'cron_expression' => '0 0 * * *',
            'target_action' => 'audit_vault_keys',
            'max_retries' => 5,
        ]);

        $this->addJob([
            'id' => 'JOB_EDGE_MESH_GOSSIP_PING',
            'name' => 'WebRTC Distributed Peer Mesh Heartbeat',
            'cron_expression' => '* * * * *',
            'target_action' => 'ping_mesh_peers',
            'max_retries' => 2,
        ]);
    }
}
