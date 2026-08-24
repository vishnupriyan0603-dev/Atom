<?php

namespace Atom\Agent;

class AgentStep
{
    public int $id;
    public int $taskId;
    public int $sequence;
    public string $type; // reasoning, tool_call, retrieval, memory, verification, human_approval, final_response
    public string $description;
    public string $status; // pending, running, completed, failed, skipped
    public ?string $toolName;
    public ?string $input;
    public ?string $output;
    public ?string $observation;
    public ?string $error;
    public ?string $startedAt;
    public ?string $completedAt;
    public int $retryCount;
    public bool $requiresApproval;

    public function __construct(array $data)
    {
        $this->id               = (int)($data['id'] ?? 0);
        $this->taskId           = (int)($data['task_id'] ?? 0);
        $this->sequence         = (int)($data['sequence'] ?? 1);
        $this->type             = $data['type'] ?? 'reasoning';
        $this->description      = $data['description'] ?? '';
        $this->status           = $data['status'] ?? 'pending';
        $this->toolName         = $data['tool_name'] ?? null;
        $this->input            = isset($data['input']) ? (is_array($data['input']) ? json_encode($data['input']) : (string)$data['input']) : null;
        $this->output           = isset($data['output']) ? (is_array($data['output']) ? json_encode($data['output']) : (string)$data['output']) : null;
        $this->observation      = $data['observation'] ?? null;
        $this->error            = $data['error'] ?? null;
        $this->startedAt        = $data['started_at'] ?? null;
        $this->completedAt      = $data['completed_at'] ?? null;
        $this->retryCount       = (int)($data['retry_count'] ?? 0);
        $this->requiresApproval = !empty($data['requires_approval']);
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'task_id'           => $this->taskId,
            'sequence'          => $this->sequence,
            'type'              => $this->type,
            'description'       => $this->description,
            'status'            => $this->status,
            'tool_name'         => $this->toolName,
            'input'             => $this->input,
            'output'            => $this->output,
            'observation'       => $this->observation,
            'error'             => $this->error,
            'started_at'        => $this->startedAt,
            'completed_at'      => $this->completedAt,
            'retry_count'       => $this->retryCount,
            'requires_approval' => $this->requiresApproval ? 1 : 0,
        ];
    }
}
