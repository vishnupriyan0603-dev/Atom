<?php

namespace App\Services;

use App\Models\ChatModel;
use App\Models\MessageModel;

class ChatService
{
    private ChatModel $chatModel;
    private MessageModel $messageModel;

    public function __construct()
    {
        $this->chatModel    = new ChatModel();
        $this->messageModel = new MessageModel();
    }

    public function getAll(int $perPage = 50, ?int $userId = null)
    {
        return $this->chatModel->getAllWithCount($perPage, $userId);
    }

    public function getById(int $id, ?int $userId = null): ?object
    {
        $chat = $userId !== null
            ? $this->chatModel->findOwned($id, $userId)
            : $this->chatModel->find($id);

        if ($chat) {
            $chat->messages = $this->messageModel->getByChat($id, $userId);
        }
        return $chat;
    }

    public function create(array $data, ?int $userId = null): int
    {
        return $this->chatModel->insert([
            'user_id'   => $userId,
            'title'     => $data['title'] ?? 'New Chat',
            'model'     => $data['model'] ?? null,
            'provider'  => $data['provider'] ?? null,
            'is_pinned' => !empty($data['is_pinned']) ? 1 : 0,
            'folder_id' => $data['folder_id'] ?? null,
            'tags'      => $data['tags'] ?? null,
        ]);
    }

    public function update(int $id, array $data, ?int $userId = null): bool
    {
        if ($userId !== null && !$this->chatModel->findOwned($id, $userId)) {
            return false;
        }
        $allowed = ['title', 'model', 'provider', 'is_pinned', 'folder_id', 'tags'];
        $update  = array_intersect_key($data, array_flip($allowed));
        return $this->chatModel->update($id, $update);
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        if ($userId !== null && !$this->chatModel->findOwned($id, $userId)) {
            return false;
        }
        return $this->chatModel->delete($id);
    }

    public function getMessages(int $chatId, ?int $userId = null)
    {
        if ($userId !== null && !$this->chatModel->findOwned($chatId, $userId)) {
            return [];
        }
        return $this->messageModel->getByChat($chatId, $userId);
    }

    public function addMessage(array $data, ?int $userId = null): int
    {
        if ($userId !== null && !$this->chatModel->findOwned((int) $data['chat_id'], $userId)) {
            throw new \RuntimeException('Chat not found');
        }
        return $this->messageModel->insert([
            'chat_id'    => $data['chat_id'],
            'user_id'    => $userId,
            'role'       => $data['role'],
            'content'    => $data['content'],
            'tokens_in'  => $data['tokens_in'] ?? null,
            'tokens_out' => $data['tokens_out'] ?? null,
            'model'      => $data['model'] ?? null,
        ]);
    }
}
