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
    private function getAtomBrain(?callable $approvalProvider = null): \Atom\Brain\AtomBrain
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        return \Atom\Brain\AtomBrainFactory::create($workspaceRoot, $approvalProvider);
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
        if ($userId === null) {
            return ['success' => false, 'message' => 'Authentication is required for resumable chat actions'];
        }
        if (!$chatModel || !$messageModel) {
            return ['success' => false, 'message' => 'A resumable chat session is required for tool actions'];
        }

        $chat = $chatModel->findOwned($chatId, $userId);
        if (!$chat) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $approvalGate = new \Atom\Security\HumanApprovalGate();
        $existingApproval = $approvalGate->findPendingChatToolRequest($userId, $chatId);
        if ($existingApproval !== null) {
            return [
                'success' => false,
                'message' => 'Resolve the pending action before sending another message in this chat.',
                'pending_approval' => $this->formatPendingApproval($existingApproval),
            ];
        }

        $messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'user',
            'content' => $message,
        ]);
        $userMessageId = (int)$messageModel->getInsertID();

        $history = $this->loadHistoryBefore($chatId, $userId, $userMessageId);
        $pendingApproval = null;
        $approvalProvider = $this->buildChatApprovalProvider(
            $userId,
            $chatId,
            $userMessageId,
            $pendingApproval
        );
        $brain = $this->getAtomBrain($approvalProvider);
        $responseContent = $brain->process($message, $history, $chat->provider, $chat->model, $chatId);

        if ($pendingApproval !== null) {
            return [
                'success' => true,
                'data' => [
                    'role' => 'assistant',
                    'content' => 'ATOM is waiting for your approval before running this action.',
                    'pending_approval' => $pendingApproval,
                ],
            ];
        }

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

    public function resumeApprovedChatTool(array $approvalRequest, array $toolResult, int $userId): array
    {
        $parameters = json_decode((string)($approvalRequest['parameters'] ?? '{}'), true);
        if (!is_array($parameters)) {
            return ['success' => false, 'message' => 'Stored chat action context is invalid'];
        }

        $chatId = (int)($parameters['chat_id'] ?? 0);
        $userMessageId = (int)($parameters['user_message_id'] ?? 0);
        $toolArguments = $parameters['tool_arguments'] ?? null;
        if ($chatId <= 0 || $userMessageId <= 0 || !is_array($toolArguments)) {
            return ['success' => false, 'message' => 'Stored chat action context is incomplete'];
        }

        $chatModel = $this->getChatModel();
        $messageModel = $this->getMessageModel();
        $chat = $chatModel?->findOwned($chatId, $userId);
        if (!$chat || !$messageModel) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $sourceMessage = $messageModel
            ->where('id', $userMessageId)
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->where('role', 'user')
            ->first();
        if (!$sourceMessage) {
            return ['success' => false, 'message' => 'Original chat message was not found'];
        }

        $history = $this->loadHistoryBefore($chatId, $userId, $userMessageId);
        $executedRequests = (new \Atom\Security\HumanApprovalGate())
            ->getExecutedChatToolRequests($userId, $chatId, $userMessageId);
        foreach ($executedRequests as $request) {
            $name = substr((string)$request['action'], strlen('brain_chat_tool:'));
            $history[] = [
                'role' => 'assistant',
                'content' => 'Approved tool action ' . $name . ' returned: '
                    . (json_encode($request['decoded_result'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'),
            ];
        }

        $pendingApproval = null;
        $approvalProvider = $this->buildChatApprovalProvider(
            $userId,
            $chatId,
            $userMessageId,
            $pendingApproval
        );
        $brain = $this->getAtomBrain($approvalProvider);
        $content = $brain->process(
            (string)$sourceMessage->content,
            $history,
            $chat->provider,
            $chat->model,
            $chatId
        );

        if ($pendingApproval !== null) {
            return [
                'success' => true,
                'data' => [
                    'role' => 'assistant',
                    'content' => 'ATOM needs your approval for the next action.',
                    'pending_approval' => $pendingApproval,
                ],
            ];
        }

        $messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => 'assistant',
            'content' => $content,
            'model' => $chat->model,
        ]);

        return [
            'success' => true,
            'data' => ['role' => 'assistant', 'content' => $content],
        ];
    }

    public function recordRejectedChatTool(array $approvalRequest, int $userId): array
    {
        $parameters = json_decode((string)($approvalRequest['parameters'] ?? '{}'), true);
        if (!is_array($parameters)) {
            return ['success' => false, 'message' => 'Stored chat action context is invalid'];
        }
        $chatId = (int)($parameters['chat_id'] ?? 0);
        $chat = $this->getChatModel()?->findOwned($chatId, $userId);
        $messageModel = $this->getMessageModel();
        if (!$chat || !$messageModel) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $toolName = substr((string)($approvalRequest['action'] ?? ''), strlen('brain_chat_tool:'));
        $content = "Action '{$toolName}' was rejected. Nothing was executed.";
        $messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => 'assistant',
            'content' => $content,
            'model' => $chat->model,
        ]);

        return ['success' => true, 'data' => ['role' => 'assistant', 'content' => $content]];
    }

    private function loadHistoryBefore(int $chatId, int $userId, int $messageId): array
    {
        $messageModel = $this->getMessageModel();
        if (!$messageModel) {
            return [];
        }

        $rows = $messageModel
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->where('id <', $messageId)
            ->orderBy('id', 'ASC')
            ->findAll();
        $rows = array_slice($rows, -30);

        return array_map(static fn ($message): array => [
            'role' => $message->role === 'assistant' ? 'assistant' : 'user',
            'content' => $message->content,
        ], $rows);
    }

    private function buildChatApprovalProvider(
        int $userId,
        int $chatId,
        int $userMessageId,
        ?array &$pendingApproval
    ): callable {
        return function (
            string $toolName,
            array $arguments,
            ?\Atom\Tools\ToolDefinition $definition
        ) use ($userId, $chatId, $userMessageId, &$pendingApproval): bool {
            $description = (new \Atom\Tools\ToolManager())->describeAction($toolName, $arguments, $definition);
            $parameters = [
                'chat_id' => $chatId,
                'user_message_id' => $userMessageId,
                'tool_arguments' => $arguments,
            ];
            $serialized = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($serialized) || strlen($serialized) > 60000) {
                return false;
            }

            $risk = in_array($definition?->riskLevel, ['low', 'medium', 'high'], true)
                ? $definition->riskLevel
                : 'high';
            $requestId = (new \Atom\Security\HumanApprovalGate())->createToolApprovalRequest(
                $userId,
                'brain_chat',
                'brain_chat_tool:' . $toolName,
                $parameters,
                $risk,
                json_encode($description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Review this chat action.',
                15
            );
            if ($requestId <= 0) {
                return false;
            }

            $pendingApproval = [
                'approval_id' => $requestId,
                'status' => 'pending',
                'action' => $description,
            ];
            return false;
        };
    }

    private function formatPendingApproval(array $request): array
    {
        $action = json_decode((string)($request['reason'] ?? '{}'), true);
        return [
            'approval_id' => (int)$request['id'],
            'status' => 'pending',
            'action' => is_array($action) ? $action : [
                'action' => $request['action'] ?? 'unknown',
                'purpose' => $request['reason'] ?? 'Review this action.',
            ],
        ];
    }

    public function processPreview(int $chatId, string $message, ?int $userId = null): array
    {
        return $this->process($chatId, $message, $userId);
    }
}
