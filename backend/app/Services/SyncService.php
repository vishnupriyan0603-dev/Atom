<?php

namespace App\Services;

class SyncService
{
    private ChatService $chatService;
    private PromptService $promptService;
    private NoteService $noteService;
    private SettingService $settingService;
    private KnowledgeService $knowledgeService;

    public function __construct()
    {
        $this->chatService     = new ChatService();
        $this->promptService   = new PromptService();
        $this->noteService     = new NoteService();
        $this->settingService  = new SettingService();
        $this->knowledgeService = new KnowledgeService();
    }

    public function pullAll(): array
    {
        return [
            'chats'    => $this->chatService->getAll(1000),
            'prompts'  => $this->promptService->getAll(1000),
            'notes'    => $this->noteService->getAll(1000),
            'settings' => $this->settingService->getAll(),
        ];
    }

    public function pushChat(array $data): array
    {
        $id = $this->chatService->create($data);
        return ['id' => $id, 'synced' => true];
    }

    public function pushPrompt(array $data): array
    {
        $id = $this->promptService->create($data);
        return ['id' => $id, 'synced' => true];
    }

    public function pushNote(array $data): array
    {
        $id = $this->noteService->create($data);
        return ['id' => $id, 'synced' => true];
    }
}
