<?php

namespace App\Controllers\Api;

use App\Services\ChatService;

class Chats extends BaseApiController
{
    private ChatService $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?? 50);
        $chats   = $this->chatService->getAll($perPage, $this->currentUserId());
        return $this->respondSuccess($chats);
    }

    public function show($id = null)
    {
        $chat = $this->chatService->getById((int) $id, $this->currentUserId());
        if (!$chat) {
            return $this->respondError('Chat not found', 404);
        }
        return $this->respondSuccess($chat);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->respondError('No data provided');
        }
        $id = $this->chatService->create($data, $this->currentUserId());
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->respondError('No data provided');
        }
        $updated = $this->chatService->update((int) $id, $data, $this->currentUserId());
        if (!$updated) {
            return $this->respondError('Chat not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->chatService->delete((int) $id, $this->currentUserId());
        if (!$deleted) {
            return $this->respondError('Chat not found', 404);
        }
        return $this->respondNoContent();
    }

    public function messages($chatId = null)
    {
        $messages = $this->chatService->getMessages((int) $chatId, $this->currentUserId());
        return $this->respondSuccess($messages);
    }

    public function addMessage($chatId = null)
    {
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->respondError('No data provided');
        }
        $data['chat_id'] = (int) $chatId;
        try {
            $id = $this->chatService->addMessage($data, $this->currentUserId());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), 404);
        }
        return $this->respondCreated(['id' => $id]);
    }
}
