<?php

namespace App\Controllers\Api;

use App\Services\PluginService;

class Plugins extends BaseApiController
{
    private PluginService $pluginService;

    public function __construct()
    {
        $this->pluginService = new PluginService();
    }

    public function index()
    {
        return $this->respondSuccess($this->pluginService->getAll());
    }

    public function show($id = null)
    {
        $plugin = $this->pluginService->getById((int) $id);
        if (!$plugin) {
            return $this->respondError('Plugin not found', 404);
        }
        return $this->respondSuccess($plugin);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['name'])) {
            return $this->respondError('Name is required');
        }
        $id = $this->pluginService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->pluginService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Plugin not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->pluginService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Plugin not found', 404);
        }
        return $this->respondNoContent();
    }
}
