<?php

namespace App\Controllers\Api;

use App\Services\NoteService;

class Notes extends BaseApiController
{
    private NoteService $noteService;

    public function __construct()
    {
        $this->noteService = new NoteService();
    }

    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?? 50);
        return $this->respondSuccess($this->noteService->getAll($perPage));
    }

    public function show($id = null)
    {
        $note = $this->noteService->getById((int) $id);
        if (!$note) {
            return $this->respondError('Note not found', 404);
        }
        return $this->respondSuccess($note);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['title'])) {
            return $this->respondError('Title is required');
        }
        $id = $this->noteService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->noteService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Note not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->noteService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Note not found', 404);
        }
        return $this->respondNoContent();
    }
}
