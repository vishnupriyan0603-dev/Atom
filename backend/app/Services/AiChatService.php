<?php

namespace App\Services;

use App\Models\AiModelModel;
use App\Models\ChatModel;
use App\Models\MessageModel;
use App\Models\SettingModel;

class AiChatService
{
    private ChatModel $chatModel;
    private MessageModel $messageModel;
    private AiModelModel $aiModelModel;

    public function __construct()
    {
        $this->chatModel     = new ChatModel();
        $this->messageModel  = new MessageModel();
        $this->aiModelModel  = new AiModelModel();
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
        $chat = $this->chatModel->find($chatId);
        if (!$chat) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        // Data isolation: a chat may only be answered by its owner.
        if ($userId !== null && (int) $chat->user_id !== $userId) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $this->messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'user',
            'content' => $message,
        ]);

        $dbHistory = $this->messageModel
            ->where('chat_id', $chatId)
            ->where('id !=', $this->messageModel->getInsertID())
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
