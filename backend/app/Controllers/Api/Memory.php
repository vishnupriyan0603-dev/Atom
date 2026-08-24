<?php

namespace App\Controllers\Api;

use Atom\Memory\StructuredMemory;
use Atom\Memory\StructuredMemoryService;

class Memory extends BaseApiController
{
    private function getUserId(): int
    {
        return (int)($this->request->getGet('user_id') ?? 1);
    }

    public function list()
    {
        $userId = $this->getUserId();
        $type = $this->request->getGet('type');
        $service = new StructuredMemoryService();
        $memories = $service->retrieveRankedMemories($userId, $type);
        $data = array_map(fn($m) => $m->toArray(), $memories);
        return $this->respondSuccess($data);
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? [];
        $userId = (int)($json['user_id'] ?? $this->getUserId());
        $content = trim($json['content'] ?? '');
        $type = trim($json['type'] ?? 'fact');
        $importance = (int)($json['importance'] ?? 5);

        if (empty($content)) {
            return $this->respondError('content is required');
        }

        $service = new StructuredMemoryService();
        $memObj = new StructuredMemory(
            userId: $userId,
            type: $type,
            content: $content,
            importance: $importance
        );

        $saved = $service->saveMemory($memObj);

        if ($saved !== null) {
            return $this->respondSuccess($saved->toArray(), 'Memory saved');
        }

        return $this->respondError('Failed to save memory');
    }

    public function update($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Memory ID required');
        }

        $json = $this->request->getJSON(true) ?? [];
        $userId = (int)($json['user_id'] ?? $this->getUserId());
        $content = trim($json['content'] ?? '');
        $importance = isset($json['importance']) ? (int)$json['importance'] : null;

        if (empty($content)) {
            return $this->respondError('content is required');
        }

        $service = new StructuredMemoryService();
        $success = $service->editMemory($userId, (int)$id, $content, $importance);

        if ($success) {
            return $this->respondSuccess(null, "Memory #{$id} updated");
        }
        return $this->respondError("Failed to update memory #{$id}");
    }

    public function delete($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Memory ID required');
        }

        $userId = $this->getUserId();
        $service = new StructuredMemoryService();
        $success = $service->deleteMemory($userId, (int)$id);

        if ($success) {
            return $this->respondSuccess(null, "Memory #{$id} deleted");
        }
        return $this->respondError("Failed to delete memory #{$id}");
    }

    public function clear()
    {
        $userId = $this->getUserId();
        $service = new StructuredMemoryService();
        $success = $service->clearUserMemories($userId);

        if ($success) {
            return $this->respondSuccess(null, "All memories for user #{$userId} cleared");
        }
        return $this->respondError("Failed to clear memories");
    }
}
