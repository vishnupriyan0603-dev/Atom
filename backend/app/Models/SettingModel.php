<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['key', 'value', 'type'];

    public function getByKey(string $key): ?object
    {
        return $this->where('key', $key)->first();
    }

    public function upsert(string $key, string $value, string $type = 'string'): void
    {
        $existing = $this->getByKey($key);
        if ($existing) {
            $this->update($existing->id, ['value' => $value, 'type' => $type]);
        } else {
            $this->insert(['key' => $key, 'value' => $value, 'type' => $type]);
        }
    }
}
