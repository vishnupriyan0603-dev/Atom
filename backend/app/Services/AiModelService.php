<?php

namespace App\Services;

use App\Models\AiModelModel;

class AiModelService
{
    private AiModelModel $model;

    public function __construct()
    {
        $this->model = new AiModelModel();
    }

    public function getAll()
    {
        return $this->model->orderBy('provider', 'ASC')->orderBy('name', 'ASC')->findAll();
    }

    public function getById(int $id): ?object
    {
        return $this->model->find($id);
    }

    public function create(array $data): int
    {
        return $this->model->insert([
            'name'           => $data['name'],
            'provider'       => $data['provider'],
            'api_endpoint'   => $data['api_endpoint'] ?? null,
            'api_key'        => $data['api_key'] ?? null,
            'is_local'       => !empty($data['is_local']) ? 1 : 0,
            'is_enabled'     => !empty($data['is_enabled']) ? 1 : 0,
            'context_length' => $data['context_length'] ?? 4096,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'provider', 'api_endpoint', 'api_key', 'is_local', 'is_enabled', 'context_length'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->model->update($id, $update);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
