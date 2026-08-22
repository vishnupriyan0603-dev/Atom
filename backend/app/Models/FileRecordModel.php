<?php

namespace App\Models;

use CodeIgniter\Model;

class FileRecordModel extends Model
{
    protected $table            = 'file_records';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'original_name', 'path', 'size', 'type', 'chat_id'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
}
