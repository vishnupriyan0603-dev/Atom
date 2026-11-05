<?php

namespace App\Services;

use App\Models\AiModelModel;
use App\Models\ChatModel;
use App\Models\MessageModel;
use App\Models\SettingModel;

class AiChatService
{
    private ?ChatModel $chatModel = null;
    private ?MessageModel $messageModel = null;
    private ?AiModelModel $aiModelModel = null;

    public function __construct()
    {
    }

    private function getChatModel(): ?ChatModel
    {
        if ($this->chatModel === null) {
            try { $this->chatModel = new ChatModel(); } catch (\Throwable $e) { return null; }
        }
        return $this->chatModel;
    }

    private function getMessageModel(): ?MessageModel
    {
        if ($this->messageModel === null) {
            try { $this->messageModel = new MessageModel(); } catch (\Throwable $e) { return null; }
        }
        return $this->messageModel;
    }

    /**
     * Helper to instantiate the unified AtomBrain component.
     *
     * Delegates to Atom\Brain\AtomBrainFactory — the single source of truth
     * for provider/model wiring, shared with frontend/web/chat.php so both
     * surfaces resolve Groq/Gemini/OpenAI/Anthropic/local identically.
     */
    private function getAtomBrain(): \Atom\Brain\AtomBrain
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        return \Atom\Brain\AtomBrainFactory::create($workspaceRoot);
    }

    public function directComplete(string $model, string $provider, string $message): array
    {
        $brain = $this->getAtomBrain();
        $history = [];
        $responseContent = $brain->process($message, $history, $provider, $model);
        return [
            'success' => true,
            'data'    => [
                'content' => $responseContent
            ]
        ];
    }

    public function process(int $chatId, string $message, ?int $userId = null): array
    {
        $chatModel = $this->getChatModel();
        $messageModel = $this->getMessageModel();
        if (!$chatModel || !$messageModel) {
            return $this->directComplete('atom-neural-v2', 'ATOM', $message);
        }

        $chat = $chatModel->find($chatId);
        if (!$chat) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        // Data isolation: a chat may only be answered by its owner.
        if ($userId !== null && (int) $chat->user_id !== $userId) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'user',
            'content' => $message,
        ]);

        $dbHistory = $messageModel
            ->where('chat_id', $chatId)
            ->where('id !=', $messageModel->getInsertID())
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Limit history sent to the model to the last 30 messages (15 turns)
        // to avoid token bloat and context drift on long conversations.
        $dbHistory = array_slice($dbHistory, -30);

        $history = [];
        foreach ($dbHistory as $msg) {
            $history[] = [
                'role'    => $msg->role === 'assistant' ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        $brain = $this->getAtomBrain();
        $responseContent = $brain->process($message, $history, $chat->provider, $chat->model, $chatId);

        $this->messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'assistant',
            'content' => $responseContent,
            'model'   => $chat->model,
        ]);

        return [
            'success' => true,
            'data'    => [
                'role'    => 'assistant',
                'content' => $responseContent,
            ],
        ];
    }

    public function processPreview(int $chatId, string $message, ?int $userId = null): array
    {
        return $this->process($chatId, $message, $userId);
    }
}
