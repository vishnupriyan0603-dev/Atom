<?php

namespace App\Services;

use App\Models\NoteModel;

class NoteService
{
    private NoteModel $model;

    public function __construct()
    {
        $this->model = new NoteModel();
    }

    public function getAll(int $perPage = 50)
    {
        return $this->model->orderBy('updated_at', 'DESC')->paginate($perPage);
    }

    public function getById(int $id): ?object
    {
        return $this->model->find($id);
    }

    public function create(array $data): int
    {
        return $this->model->insert([
            'title'       => $data['title'],
            'content'     => $data['content'] ?? '',
            'folder'      => $data['folder'] ?? null,
            'is_favorite' => !empty($data['is_favorite']) ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'content', 'folder', 'is_favorite'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->model->update($id, $update);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
