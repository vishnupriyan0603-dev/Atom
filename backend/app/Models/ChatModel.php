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
    protected $allowedFields    = ['user_id', 'title', 'model', 'provider', 'is_pinned', 'folder_id', 'tags'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllWithCount(int $perPage = 50, ?int $userId = null)
    {
        if ($userId !== null) {
            $this->where('chats.user_id', $userId);
        }
        return $this->select('chats.*, (SELECT COUNT(*) FROM messages WHERE messages.chat_id = chats.id) as message_count')
            ->orderBy('updated_at', 'DESC')
            ->paginate($perPage);
    }

    public function findOwned(int $id, int $userId): ?object
    {
        return $this->where('id', $id)->where('user_id', $userId)->first();
    }
}
