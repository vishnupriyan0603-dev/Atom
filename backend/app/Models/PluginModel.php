<?php

namespace App\Models;

use CodeIgniter\Model;

class PluginModel extends Model
{
    protected $table            = 'plugins';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'version', 'author', 'description', 'icon_path', 'is_enabled', 'installed_at'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'installed_at';
}
