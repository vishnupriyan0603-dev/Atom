<?php

namespace Atom\Workflow;

class WorkflowExecution
{
    public int $id;
    public int $workflowId;
    public int $workflowVersionId;
    public int $ownerUserId;
    public string $status; // queued, running, waiting_approval, waiting_delay, paused, retrying, completed, failed, cancelled, timeout
    public ?string $idempotencyKey;
    public ?string $currentNodeKey;
    public array $variables;
    public ?string $createdAt;
    public ?string $startedAt;
    public ?string $completedAt;
    public ?string $error;

    public function __construct(array $data)
    {
        $this->id                = (int)($data['id'] ?? 0);
        $this->workflowId        = (int)($data['workflow_id'] ?? 0);
        $this->workflowVersionId = (int)($data['workflow_version_id'] ?? 1);
        $this->ownerUserId       = (int)($data['owner_user_id'] ?? 1);
        $this->status            = $data['status'] ?? 'running';
        $this->idempotencyKey    = $data['idempotency_key'] ?? null;
        $this->currentNodeKey    = $data['current_node_key'] ?? 'start';
        $this->variables         = is_array($data['variables'] ?? null) ? $data['variables'] : (json_decode($data['variables_json'] ?? '[]', true) ?: []);
        $this->createdAt         = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->startedAt         = $data['started_at'] ?? date('Y-m-d H:i:s');
        $this->completedAt       = $data['completed_at'] ?? null;
        $this->error             = $data['error'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'workflow_id'         => $this->workflowId,
            'workflow_version_id' => $this->workflowVersionId,
            'owner_user_id'       => $this->ownerUserId,
            'status'              => $this->status,
            'idempotency_key'     => $this->idempotencyKey,
            'current_node_key'    => $this->currentNodeKey,
            'variables_json'      => json_encode($this->variables),
            'created_at'          => $this->createdAt,
            'started_at'          => $this->startedAt,
            'completed_at'        => $this->completedAt,
            'error'               => $this->error,
        ];
    }
}
