<?php

namespace Atom\Memory;

class StructuredMemory
{
    public ?int $id;
    public int $userId;
    public string $type; // conversation, preference, fact, instruction, project, knowledge, temporary
    public string $content;
    public array $embedding;
    public int $importance; // 1 to 10
    public float $confidence; // 0.0 to 1.0
    public string $source;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?string $expiresAt;
    public int $accessCount;

    public function __construct(
        int $userId = 1,
        string $type = 'fact',
        string $content = '',
        array $embedding = [],
        int $importance = 5,
        float $confidence = 1.0,
        string $source = 'user_input',
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $expiresAt = null,
        int $accessCount = 0
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = strtolower($type);
        $this->content = $content;
        $this->embedding = $embedding;
        $this->importance = max(1, min(10, $importance));
        $this->confidence = max(0.0, min(1.0, $confidence));
        $this->source = $source;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?? date('Y-m-d H:i:s');
        $this->expiresAt = $expiresAt;
        $this->accessCount = $accessCount;
    }

    public static function fromArray(array $data): self
    {
        $embedding = [];
        if (!empty($data['embedding_json'])) {
            $embedding = json_decode($data['embedding_json'], true) ?: [];
        }

        return new self(
            userId: (int)($data['user_id'] ?? 1),
            type: $data['type'] ?? 'fact',
            content: $data['content'] ?? '',
            embedding: $embedding,
            importance: (int)($data['importance'] ?? 5),
            confidence: (float)($data['confidence'] ?? 1.0),
            source: $data['source'] ?? 'user_input',
            id: isset($data['id']) ? (int)$data['id'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            expiresAt: $data['expires_at'] ?? null,
            accessCount: (int)($data['access_count'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->userId,
            'type'           => $this->type,
            'content'        => $this->content,
            'embedding_json' => json_encode($this->embedding),
            'importance'     => $this->importance,
            'confidence'     => $this->confidence,
            'source'         => $this->source,
            'created_at'     => $this->createdAt,
            'updated_at'     => $this->updatedAt,
            'expires_at'     => $this->expiresAt,
            'access_count'   => $this->accessCount,
        ];
    }
}
