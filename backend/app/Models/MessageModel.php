<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table            = 'messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['chat_id', 'user_id', 'role', 'content', 'tokens_in', 'tokens_out', 'model'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    public function getByChat(int $chatId, ?int $userId = null)
    {
        if ($userId !== null) {
            $this->where('user_id', $userId);
        }
        return $this->where('chat_id', $chatId)->orderBy('created_at', 'ASC')->findAll();
    }

    public function deleteByChat(int $chatId): void
    {
        $this->where('chat_id', $chatId)->delete();
    }
}
