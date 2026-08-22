<?php

namespace App\Services;

use App\Models\KnowledgeItemModel;

class KnowledgeService
{
    private KnowledgeItemModel $model;

    public function __construct()
    {
        $this->model = new KnowledgeItemModel();
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
            'title'      => $data['title'],
            'content'    => $data['content'] ?? null,
            'file_path'  => $data['file_path'] ?? null,
            'file_type'  => $data['file_type'] ?? null,
            'collection' => $data['collection'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'content', 'file_path', 'file_type', 'collection'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->model->update($id, $update);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
