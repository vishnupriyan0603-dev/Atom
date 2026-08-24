<?php

namespace Atom\Governance;

class Policy
{
    public int $id;
    public int $ownerId;
    public string $name;
    public ?string $description;
    public string $status; // active, disabled, archived
    public int $priority;
    public int $version;
    public string $scope;
    public array $rules;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data)
    {
        $this->id          = (int)($data['id'] ?? 0);
        $this->ownerId     = (int)($data['owner_id'] ?? 1);
        $this->name        = $data['name'] ?? 'Default Policy';
        $this->description = $data['description'] ?? null;
        $this->status      = $data['status'] ?? 'active';
        $this->priority    = (int)($data['priority'] ?? 10);
        $this->version     = (int)($data['version'] ?? 1);
        $this->scope       = $data['scope'] ?? 'system';

        $rulesData = is_array($data['rules'] ?? null) ? $data['rules'] : (json_decode($data['rules_json'] ?? '[]', true) ?: []);
        $this->rules = array_map(fn($r) => new PolicyRule($r), $rulesData);

        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'owner_id'    => $this->ownerId,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'version'     => $this->version,
            'scope'       => $this->scope,
            'rules_json'  => json_encode(array_map(fn($r) => [
                'action' => $r->action, 'resource' => $r->resource, 'effect' => $r->effect, 'conditions' => $r->conditions
            ], $this->rules)),
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }
}
