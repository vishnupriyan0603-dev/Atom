<?php

namespace Atom\Agent;

class AgentTask
{
    public int $id;
    public int $userId;
    public ?string $conversationId;
    public string $title;
    public string $objective;
    public string $status;
    public string $priority;
    public int $currentStep;
    public int $maxSteps;
    public int $maxToolCalls;
    public int $maxTokens;
    public int $maxRuntimeSeconds;
    public float $maxCost;
    public int $maxReplans;
    public string $riskLevel;
    public bool $requiresApproval;
    public ?string $createdAt;
    public ?string $startedAt;
    public ?string $completedAt;
    public ?string $cancelledAt;
    public ?string $error;
    public ?string $result;

    public function __construct(array $data)
    {
        $this->id                = (int)($data['id'] ?? 0);
        $this->userId            = (int)($data['user_id'] ?? 1);
        $this->conversationId    = $data['conversation_id'] ?? null;
        $this->title             = $data['title'] ?? 'Agent Task';
        $this->objective         = $data['objective'] ?? '';
        $this->status            = $data['status'] ?? 'pending';
        $this->priority          = $data['priority'] ?? 'normal';
        $this->currentStep       = (int)($data['current_step'] ?? 0);
        $this->maxSteps          = (int)($data['max_steps'] ?? 20);
        $this->maxToolCalls      = (int)($data['max_tool_calls'] ?? 10);
        $this->maxTokens         = (int)($data['max_tokens'] ?? 8000);
        $this->maxRuntimeSeconds = (int)($data['max_runtime_seconds'] ?? 300);
        $this->maxCost           = (float)($data['max_cost'] ?? 1.0);
        $this->maxReplans        = (int)($data['max_replans'] ?? 3);
        $this->riskLevel         = $data['risk_level'] ?? 'low';
        $this->requiresApproval  = !empty($data['requires_approval']);
        $this->createdAt         = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->startedAt         = $data['started_at'] ?? null;
        $this->completedAt       = $data['completed_at'] ?? null;
        $this->cancelledAt       = $data['cancelled_at'] ?? null;
        $this->error             = $data['error'] ?? null;
        $this->result            = $data['result'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->userId,
            'conversation_id'     => $this->conversationId,
            'title'               => $this->title,
            'objective'           => $this->objective,
            'status'              => $this->status,
            'priority'            => $this->priority,
            'current_step'        => $this->currentStep,
            'max_steps'           => $this->maxSteps,
            'max_tool_calls'      => $this->maxToolCalls,
            'max_tokens'          => $this->maxTokens,
            'max_runtime_seconds' => $this->maxRuntimeSeconds,
            'max_cost'            => $this->maxCost,
            'max_replans'         => $this->maxReplans,
            'risk_level'          => $this->riskLevel,
            'requires_approval'   => $this->requiresApproval ? 1 : 0,
            'created_at'          => $this->createdAt,
            'started_at'          => $this->startedAt,
            'completed_at'        => $this->completedAt,
            'cancelled_at'        => $this->cancelledAt,
            'error'               => $this->error,
            'result'              => $this->result,
        ];
    }
}
