<?php

namespace App\Controllers\Api;

use App\Services\AiModelService;

class AiModels extends BaseApiController
{
    private AiModelService $aiModelService;

    public function __construct()
    {
        $this->aiModelService = new AiModelService();
    }

    public function index()
    {
        return $this->respondSuccess($this->aiModelService->getAll());
    }

    public function show($id = null)
    {
        $model = $this->aiModelService->getById((int) $id);
        if (!$model) {
            return $this->respondError('Model not found', 404);
        }
        return $this->respondSuccess($model);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['name'])) {
            return $this->respondError('Name is required');
        }
        $id = $this->aiModelService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->aiModelService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Model not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->aiModelService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Model not found', 404);
        }
        return $this->respondNoContent();
    }
}
