<?php

namespace App\Services;

use App\Models\MessageModel;
use App\Models\ChatModel;

class MessageService
{
    private MessageModel $messageModel;
    private ChatModel $chatModel;

    public function __construct()
    {
        $this->messageModel = new MessageModel();
        $this->chatModel    = new ChatModel();
    }

    public function getById(int $id, ?int $userId = null): ?object
    {
        $message = $this->messageModel->find($id);
        if (!$message || ($userId !== null && !$this->chatModel->findOwned((int) $message->chat_id, $userId))) {
            return null;
        }
        return $message;
    }

    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $message = $this->messageModel->find($id);
        if (!$message || ($userId !== null && !$this->chatModel->findOwned((int) $message->chat_id, $userId))) {
            return false;
        }
        $allowed = ['role', 'content', 'tokens_in', 'tokens_out', 'model'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->messageModel->update($id, $update);
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        $message = $this->messageModel->find($id);
        if (!$message || ($userId !== null && !$this->chatModel->findOwned((int) $message->chat_id, $userId))) {
            return false;
        }
        return $this->messageModel->delete($id);
    }
}
