<?php

namespace Atom\PersonalModel;

interface ModelInterface
{
    /**
     * Generates a response based on the context array.
     * $messages is an array of [['role' => 'user/system/assistant', 'content' => '...']]
     */
    public function generate(array $messages): ModelResponse;

    /**
     * Gets the model name.
     */
    public function getName(): string;

    /**
     * Checks if the model or provider is available and connected.
     */
    public function isAvailable(): bool;

    /**
     * Gets the provider name (e.g. 'Gemini', 'Ollama', 'LM Studio').
     */
    public function getProviderName(): string;
}
