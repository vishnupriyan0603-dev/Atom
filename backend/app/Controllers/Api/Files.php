<?php

namespace App\Controllers\Api;

use App\Services\FileRecordService;

class Files extends BaseApiController
{
    private FileRecordService $fileService;

    public function __construct()
    {
        $this->fileService = new FileRecordService();
    }

    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?? 50);
        return $this->respondSuccess($this->fileService->getAll($perPage));
    }

    public function show($id = null)
    {
        $file = $this->fileService->getById((int) $id);
        if (!$file) {
            return $this->respondError('File not found', 404);
        }
        return $this->respondSuccess($file);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['name'])) {
            return $this->respondError('Name is required');
        }
        $id = $this->fileService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function delete($id = null)
    {
        $deleted = $this->fileService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('File not found', 404);
        }
        return $this->respondNoContent();
    }
}
