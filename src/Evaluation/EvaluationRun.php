<?php

namespace Atom\Evaluation;

class EvaluationRun
{
    public int $id;
    public int $datasetId;
    public string $targetType; // agent, workflow, swarm, model, tool
    public string $targetId;
    public string $status; // queued, running, completed, failed, cancelled
    public int $totalCases;
    public int $completedCases;
    public int $failedCases;
    public float $aggregateScore;
    public ?string $createdAt;
    public ?string $completedAt;

    public function __construct(array $data)
    {
        $this->id             = (int)($data['id'] ?? 0);
        $this->datasetId      = (int)($data['dataset_id'] ?? 1);
        $this->targetType     = $data['target_type'] ?? 'agent';
        $this->targetId       = (string)($data['target_id'] ?? '1');
        $this->status         = $data['status'] ?? 'completed';
        $this->totalCases     = (int)($data['total_cases'] ?? 0);
        $this->completedCases = (int)($data['completed_cases'] ?? 0);
        $this->failedCases    = (int)($data['failed_cases'] ?? 0);
        $this->aggregateScore = (float)($data['aggregate_score'] ?? 1.0);
        $this->createdAt      = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->completedAt    = $data['completed_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'dataset_id'      => $this->datasetId,
            'target_type'     => $this->targetType,
            'target_id'       => $this->targetId,
            'status'          => $this->status,
            'total_cases'     => $this->totalCases,
            'completed_cases' => $this->completedCases,
            'failed_cases'    => $this->failedCases,
            'aggregate_score' => $this->aggregateScore,
            'created_at'      => $this->createdAt,
            'completed_at'    => $this->completedAt,
        ];
    }
}
