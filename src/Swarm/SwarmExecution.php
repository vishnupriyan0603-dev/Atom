<?php

namespace Atom\Swarm;

class SwarmExecution
{
    public int $id;
    public int $userId;
    public ?int $workflowExecutionId;
    public string $objective;
    public string $status; // queued, planning, running, waiting, verifying, synthesizing, waiting_approval, paused, completed, failed, cancelled, timeout
    public int $coordinatorAgentId;
    public int $maxAgents;
    public ?string $createdAt;
    public ?string $startedAt;
    public ?string $completedAt;
    public ?string $result;
    public ?string $error;

    public function __construct(array $data)
    {
        $this->id                   = (int)($data['id'] ?? 0);
        $this->userId               = (int)($data['user_id'] ?? 1);
        $this->workflowExecutionId  = isset($data['workflow_execution_id']) ? (int)$data['workflow_execution_id'] : null;
        $this->objective            = $data['objective'] ?? 'Multi-agent task objective';
        $this->status               = $data['status'] ?? 'running';
        $this->coordinatorAgentId   = (int)($data['coordinator_agent_id'] ?? 1);
        $this->maxAgents            = (int)($data['max_agents'] ?? 8);
        $this->createdAt            = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->startedAt            = $data['started_at'] ?? date('Y-m-d H:i:s');
        $this->completedAt          = $data['completed_at'] ?? null;
        $this->result               = $data['result'] ?? null;
        $this->error                = $data['error'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->userId,
            'workflow_execution_id' => $this->workflowExecutionId,
            'objective'             => $this->objective,
            'status'                => $this->status,
            'coordinator_agent_id'  => $this->coordinatorAgentId,
            'max_agents'            => $this->maxAgents,
            'created_at'            => $this->createdAt,
            'started_at'            => $this->startedAt,
            'completed_at'          => $this->completedAt,
            'result'                => $this->result,
            'error'                 => $this->error,
        ];
    }
}
