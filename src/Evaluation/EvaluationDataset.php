<?php

namespace Atom\Evaluation;

class EvaluationDataset
{
    public int $id;
    public int $ownerUserId;
    public string $name;
    public ?string $description;
    public int $version;
    public string $status; // active, draft, archived
    public int $caseCount;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data)
    {
        $this->id          = (int)($data['id'] ?? 0);
        $this->ownerUserId = (int)($data['owner_user_id'] ?? 1);
        $this->name        = $data['name'] ?? 'Evaluation Dataset';
        $this->description = $data['description'] ?? null;
        $this->version     = (int)($data['version'] ?? 1);
        $this->status      = $data['status'] ?? 'active';
        $this->caseCount   = (int)($data['case_count'] ?? 0);
        $this->createdAt   = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt   = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'owner_user_id' => $this->ownerUserId,
            'name'          => $this->name,
            'description'   => $this->description,
            'version'       => $this->version,
            'status'        => $this->status,
            'case_count'    => $this->caseCount,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];
    }
}
