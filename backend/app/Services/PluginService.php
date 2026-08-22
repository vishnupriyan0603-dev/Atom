<?php

namespace App\Services;

use App\Models\PluginModel;

class PluginService
{
    private PluginModel $model;

    public function __construct()
    {
        $this->model = new PluginModel();
    }

    public function getAll()
    {
        return $this->model->orderBy('name', 'ASC')->findAll();
    }

    public function getById(int $id): ?object
    {
        return $this->model->find($id);
    }

    public function create(array $data): int
    {
        return $this->model->insert([
            'name'        => $data['name'],
            'version'     => $data['version'] ?? '1.0.0',
            'author'      => $data['author'] ?? null,
            'description' => $data['description'] ?? null,
            'icon_path'   => $data['icon_path'] ?? null,
            'is_enabled'  => !empty($data['is_enabled']) ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'version', 'author', 'description', 'icon_path', 'is_enabled'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->model->update($id, $update);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
