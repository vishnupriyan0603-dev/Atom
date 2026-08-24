<?php

namespace Atom\Workflow;

class Workflow
{
    public int $id;
    public int $ownerUserId;
    public string $name;
    public ?string $description;
    public string $status; // draft, published, disabled, archived
    public int $currentVersion;
    public ?string $webhookKey;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?string $publishedAt;
    public ?string $disabledAt;

    public function __construct(array $data)
    {
        $this->id             = (int)($data['id'] ?? 0);
        $this->ownerUserId    = (int)($data['owner_user_id'] ?? 1);
        $this->name           = $data['name'] ?? 'Autonomous Workflow';
        $this->description    = $data['description'] ?? null;
        $this->status         = $data['status'] ?? 'published';
        $this->currentVersion = (int)($data['current_version'] ?? 1);
        $this->webhookKey     = $data['webhook_key'] ?? null;
        $this->createdAt      = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt      = $data['updated_at'] ?? date('Y-m-d H:i:s');
        $this->publishedAt    = $data['published_at'] ?? null;
        $this->disabledAt     = $data['disabled_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'owner_user_id'   => $this->ownerUserId,
            'name'            => $this->name,
            'description'     => $this->description,
            'status'          => $this->status,
            'current_version' => $this->currentVersion,
            'webhook_key'     => $this->webhookKey,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
            'published_at'    => $this->publishedAt,
            'disabled_at'     => $this->disabledAt,
        ];
    }
}
