<?php

namespace Atom\Routing;

class RoutingPolicy
{
    public int $id;
    public int $ownerUserId;
    public string $name;
    public ?string $description;
    public string $targetType; // model, agent, workflow, swarm
    public bool $enabled;
    public int $priority;
    public string $defaultCandidate;
    public string $fallbackCandidate;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data)
    {
        $this->id                = (int)($data['id'] ?? 0);
        $this->ownerUserId       = (int)($data['owner_user_id'] ?? 1);
        $this->name              = $data['name'] ?? 'Adaptive Model Policy';
        $this->description       = $data['description'] ?? null;
        $this->targetType        = $data['target_type'] ?? 'model';
        $this->enabled           = !empty($data['enabled']);
        $this->priority          = (int)($data['priority'] ?? 10);
        $this->defaultCandidate  = $data['default_candidate'] ?? 'gemini-1.5-flash';
        $this->fallbackCandidate = $data['fallback_candidate'] ?? 'groq-llama3-70b';
        $this->createdAt         = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt         = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'owner_user_id'      => $this->ownerUserId,
            'name'               => $this->name,
            'description'        => $this->description,
            'target_type'        => $this->targetType,
            'enabled'            => $this->enabled ? 1 : 0,
            'priority'           => $this->priority,
            'default_candidate'  => $this->defaultCandidate,
            'fallback_candidate' => $this->fallbackCandidate,
            'created_at'         => $this->createdAt,
            'updated_at'         => $this->updatedAt,
        ];
    }
}
