<?php

namespace App\Services;

use App\Models\SettingModel;

class SettingService
{
    private SettingModel $model;

    public function __construct()
    {
        $this->model = new SettingModel();
    }

    public function getAll()
    {
        return $this->model->findAll();
    }

    public function getByKey(string $key): ?string
    {
        $setting = $this->model->getByKey($key);
        return $setting ? $setting->value : null;
    }

    public function set(string $key, string $value, string $type = 'string'): void
    {
        $this->model->upsert($key, $value, $type);
    }

    public function delete(string $key): bool
    {
        $setting = $this->model->getByKey($key);
        if ($setting) {
            return $this->model->delete($setting->id);
        }
        return false;
    }
}
