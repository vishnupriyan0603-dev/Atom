<?php

namespace App\Controllers\Api;

use App\Services\SyncService;

class Sync extends BaseApiController
{
    private SyncService $syncService;

    public function __construct()
    {
        $this->syncService = new SyncService();
    }

    public function pull()
    {
        return $this->respondSuccess($this->syncService->pullAll());
    }

    public function push()
    {
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->respondError('No data provided');
        }

        $results = [];

        if (!empty($data['chats'])) {
            foreach ($data['chats'] as $chat) {
                $results['chats'][] = $this->syncService->pushChat($chat);
            }
        }

        if (!empty($data['prompts'])) {
            foreach ($data['prompts'] as $prompt) {
                $results['prompts'][] = $this->syncService->pushPrompt($prompt);
            }
        }

        if (!empty($data['notes'])) {
            foreach ($data['notes'] as $note) {
                $results['notes'][] = $this->syncService->pushNote($note);
            }
        }

        return $this->respondCreated($results, 'Sync completed');
    }
}
