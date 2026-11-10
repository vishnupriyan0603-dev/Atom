<?php

namespace App\Controllers\Api;

use App\Services\AiChatService;
use App\Models\AiModelModel;

class AiChat extends BaseApiController
{
    private AiChatService $aiChatService;
    private ?AiModelModel $aiModelModel = null;

    public function __construct()
    {
        $this->aiChatService = new AiChatService();
    }

    public function complete()
    {
        $raw = $this->request->getBody();
        $data = !empty($raw) ? json_decode($raw, true) : [];
        if (!is_array($data) || empty($data['message'])) {
            return $this->respondError('Message is required', 400);
        }

        $model    = $data['model']    ?? 'llama3.1';
        $provider = $data['provider'] ?? 'Ollama';

        $result = $this->aiChatService->directComplete($model, $provider, $data['message']);

        if (!$result['success']) {
            return $this->respondError($result['message'], 500);
        }

        return $this->respondSuccess($result['data'], 'Completion generated');
    }

    public function listModels()
    {
        try {
            if ($this->aiModelModel === null) {
                $this->aiModelModel = new AiModelModel();
            }
            $models = $this->aiModelModel->where('is_enabled', 1)->findAll();
            return $this->respondSuccess($models);
        } catch (\Throwable $e) {
            return $this->respondSuccess([]);
        }
    }

    public function send(int $chatId = null)
    {
        if (!$chatId) {
            return $this->respondError('Chat ID is required', 400);
        }

        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['message'])) {
            return $this->respondError('Message is required', 400);
        }

        $result = $this->aiChatService->process($chatId, $data['message'], $this->currentUserId());

        if (!$result['success']) {
            return $this->respondError($result['message'], 404);
        }

        if (isset($result['data']['pending_approval'])) {
            return $this->respondSuccess($result['data'], 'Action approval required', 202);
        }
        return $this->respondSuccess($result['data'], 'Message sent');
    }

    public function preview(int $chatId = null)
    {
        if (!$chatId) {
            return $this->respondError('Chat ID is required', 400);
        }

        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['message'])) {
            return $this->respondError('Message is required', 400);
        }

        $result = $this->aiChatService->processPreview($chatId, $data['message'], $this->currentUserId());

        if (!$result['success']) {
            return $this->respondError($result['message'], 404);
        }

        if (isset($result['data']['pending_approval'])) {
            return $this->respondSuccess($result['data'], 'Action approval required', 202);
        }
        return $this->respondSuccess($result['data'], 'Preview response');
    }

    public function stream(int $chatId = null)
    {
        if (!$chatId) {
            return $this->respondError('Chat ID is required', 400);
        }

        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['message'])) {
            return $this->respondError('Message is required', 400);
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $result = $this->aiChatService->processPreview($chatId, $data['message'], $this->currentUserId());

        if ($result['success'] && isset($result['data']['pending_approval'])) {
            echo "event: approval_required\n";
            echo "data: " . json_encode(['pending_approval' => $result['data']['pending_approval']]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        } elseif ($result['success'] && isset($result['data']['content'])) {
            $content = $result['data']['content'];
            $chunks = str_split($content, 12);
            foreach ($chunks as $chunk) {
                echo "data: " . json_encode(['token' => $chunk]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                usleep(20000);
            }
            echo "data: [DONE]\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        } else {
            echo "data: " . json_encode(['error' => $result['message'] ?? 'Streaming error']) . "\n\n";
        }
        exit;
    }
}
