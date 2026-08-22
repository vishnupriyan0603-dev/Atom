<?php

namespace Atom\LLM;

interface LLMInterface
{
    /**
     * Sends messages to the LLM.
     * $messages is an array of messages: [['role' => 'user', 'content' => '...']]
     * Returns an array: ['success' => bool, 'content' => string, 'error' => ?string]
     */
    public function chat(array $messages): array;

    /**
     * Checks whether the provider endpoint is reachable.
     * Should use a lightweight request with a short timeout (≤5s).
     */
    public function isAvailable(): bool;
}
