<?php

namespace Atom\Jobs;

class Job
{
    public ?int $id;
    public string $type; // e.g. document_indexing, embedding_generation, backup, evaluation
    public array $payload;
    public string $status; // queued, running, completed, failed, cancelled
    public int $attempts;
    public int $maxAttempts;
    public ?string $startedAt;
    public ?string $completedAt;
    public ?string $error;
    public ?string $createdAt;

    public function __construct(
        string $type,
        array $payload = [],
        string $status = 'queued',
        int $attempts = 0,
        int $maxAttempts = 3,
        ?int $id = null,
        ?string $startedAt = null,
        ?string $completedAt = null,
        ?string $error = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->type = strtolower($type);
        $this->payload = $payload;
        $this->status = strtolower($status);
        $this->attempts = $attempts;
        $this->maxAttempts = $maxAttempts;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
        $this->error = $error;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): self
    {
        $payload = [];
        if (!empty($data['payload'])) {
            $payload = is_array($data['payload']) ? $data['payload'] : (json_decode($data['payload'], true) ?: []);
        }

        return new self(
            type: $data['type'] ?? 'general',
            payload: $payload,
            status: $data['status'] ?? 'queued',
            attempts: (int)($data['attempts'] ?? 0),
            maxAttempts: (int)($data['max_attempts'] ?? 3),
            id: isset($data['id']) ? (int)$data['id'] : null,
            startedAt: $data['started_at'] ?? null,
            completedAt: $data['completed_at'] ?? null,
            error: $data['error'] ?? null,
            createdAt: $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'payload'      => json_encode($this->payload),
            'status'       => $this->status,
            'attempts'     => $this->attempts,
            'max_attempts' => $this->maxAttempts,
            'started_at'   => $this->startedAt,
            'completed_at' => $this->completedAt,
            'error'        => $this->error,
            'created_at'   => $this->createdAt,
        ];
    }
}
