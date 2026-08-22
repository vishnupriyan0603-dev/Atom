<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatModel extends Model
{
    protected $table            = 'chats';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['title', 'model', 'provider', 'is_pinned', 'folder_id', 'tags'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllWithCount(int $perPage = 50)
    {
        return $this->select('chats.*, (SELECT COUNT(*) FROM messages WHERE messages.chat_id = chats.id) as message_count')
            ->orderBy('updated_at', 'DESC')
            ->paginate($perPage);
    }
}
