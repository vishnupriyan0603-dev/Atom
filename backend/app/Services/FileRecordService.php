<?php

namespace App\Services;

use App\Models\FileRecordModel;

class FileRecordService
{
    private FileRecordModel $model;

    public function __construct()
    {
        $this->model = new FileRecordModel();
    }

    public function getAll(int $perPage = 50)
    {
        return $this->model->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    public function getById(int $id): ?object
    {
        return $this->model->find($id);
    }

    public function create(array $data): int
    {
        return $this->model->insert([
            'name'          => $data['name'],
            'original_name' => $data['original_name'] ?? $data['name'],
            'path'          => $data['path'],
            'size'          => $data['size'] ?? 0,
            'type'          => $data['type'] ?? null,
            'chat_id'       => $data['chat_id'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
