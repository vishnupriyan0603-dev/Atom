<?php

namespace Atom\ModelGateway;

class AtomGatewayRequest
{
    public string $provider;
    public string $model;
    public float $temperature;
    public int $maxTokens;
    public float $topP;
    public ?string $systemPrompt;
    public array $messages;
    public bool $streaming;
    public array $tools;
    public array $metadata;

    public function __construct(
        string $provider = 'groq',
        string $model = 'openai/gpt-oss-120b',
        array $messages = [],
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 2048,
        float $topP = 1.0,
        bool $streaming = false,
        array $tools = [],
        array $metadata = []
    ) {
        $this->provider = strtolower($provider);
        $this->model = $model;
        $this->messages = $messages;
        $this->systemPrompt = $systemPrompt;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
        $this->topP = $topP;
        $this->streaming = $streaming;
        $this->tools = $tools;
        $this->metadata = $metadata;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'] ?? 'groq',
            model: $data['model'] ?? 'openai/gpt-oss-120b',
            messages: $data['messages'] ?? [],
            systemPrompt: $data['system_prompt'] ?? null,
            temperature: (float)($data['temperature'] ?? 0.7),
            maxTokens: (int)($data['max_tokens'] ?? 2048),
            topP: (float)($data['top_p'] ?? 1.0),
            streaming: (bool)($data['streaming'] ?? false),
            tools: $data['tools'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'provider'      => $this->provider,
            'model'         => $this->model,
            'temperature'   => $this->temperature,
            'max_tokens'    => $this->maxTokens,
            'top_p'         => $this->topP,
            'system_prompt' => $this->systemPrompt,
            'messages'      => $this->messages,
            'streaming'     => $this->streaming,
            'tools'         => $this->tools,
            'metadata'      => $this->metadata,
        ];
    }
}
