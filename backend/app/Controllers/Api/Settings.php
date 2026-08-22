<?php

namespace App\Controllers\Api;

use App\Services\SettingService;

class Settings extends BaseApiController
{
    private SettingService $settingService;

    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    public function index()
    {
        return $this->respondSuccess($this->settingService->getAll());
    }

    public function show($key = null)
    {
        $value = $this->settingService->getByKey($key);
        if ($value === null) {
            return $this->respondError('Setting not found', 404);
        }
        return $this->respondSuccess(['key' => $key, 'value' => $value]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['key'])) {
            return $this->respondError('Key is required');
        }
        $this->settingService->set($data['key'], $data['value'] ?? '', $data['type'] ?? 'string');
        return $this->respondCreated(['key' => $data['key']]);
    }

    public function update($key = null)
    {
        $data = $this->request->getJSON(true);
        $this->settingService->set($key, $data['value'] ?? '', $data['type'] ?? 'string');
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($key = null)
    {
        $deleted = $this->settingService->delete($key);
        if (!$deleted) {
            return $this->respondError('Setting not found', 404);
        }
        return $this->respondNoContent();
    }
}
