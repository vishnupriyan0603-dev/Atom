<?php

namespace App\Controllers\Api;

use App\Services\MessageService;

class Messages extends BaseApiController
{
    private MessageService $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    public function show($id = null)
    {
        $message = $this->messageService->getById((int) $id);
        if (!$message) {
            return $this->respondError('Message not found', 404);
        }
        return $this->respondSuccess($message);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->messageService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Message not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->messageService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Message not found', 404);
        }
        return $this->respondNoContent();
    }
}
