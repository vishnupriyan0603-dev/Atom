<?php

namespace App\Models;

use CodeIgniter\Model;

class AiModelModel extends Model
{
    protected $table            = 'ai_models';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'provider', 'api_endpoint', 'api_key', 'is_local', 'is_enabled', 'context_length'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
}
