<?php

namespace App\Controllers\Api;

use App\Services\PromptService;

class Prompts extends BaseApiController
{
    private PromptService $promptService;

    public function __construct()
    {
        $this->promptService = new PromptService();
    }

    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?? 50);
        return $this->respondSuccess($this->promptService->getAll($perPage));
    }

    public function show($id = null)
    {
        $prompt = $this->promptService->getById((int) $id);
        if (!$prompt) {
            return $this->respondError('Prompt not found', 404);
        }
        return $this->respondSuccess($prompt);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['title'])) {
            return $this->respondError('Title is required');
        }
        $id = $this->promptService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->promptService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Prompt not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->promptService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Prompt not found', 404);
        }
        return $this->respondNoContent();
    }
}
