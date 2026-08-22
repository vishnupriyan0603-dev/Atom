<?php

namespace App\Models;

use CodeIgniter\Model;

class KnowledgeItemModel extends Model
{
    protected $table            = 'knowledge_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['title', 'content', 'file_path', 'file_type', 'collection'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
}
