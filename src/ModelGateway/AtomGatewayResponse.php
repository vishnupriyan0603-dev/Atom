<?php

namespace Atom\ModelGateway;

class AtomGatewayResponse
{
    public bool $success;
    public string $content;
    public string $provider;
    public string $model;
    public int $tokensUsed;
    public int $latencyMs;
    public array $toolCalls;
    public ?string $error;
    public array $rawResponse;

    public function __construct(
        bool $success,
        string $content = '',
        string $provider = 'unknown',
        string $model = 'unknown',
        int $tokensUsed = 0,
        int $latencyMs = 0,
        array $toolCalls = [],
        ?string $error = null,
        array $rawResponse = []
    ) {
        $this->success = $success;
        $this->content = $content;
        $this->provider = $provider;
        $this->model = $model;
        $this->tokensUsed = $tokensUsed;
        $this->latencyMs = $latencyMs;
        $this->toolCalls = $toolCalls;
        $this->error = $error;
        $this->rawResponse = $rawResponse;
    }

    public static function error(string $errorMessage, string $provider = 'unknown', string $model = 'unknown'): self
    {
        return new self(
            success: false,
            content: '',
            provider: $provider,
            model: $model,
            error: $errorMessage
        );
    }

    public static function success(
        string $content,
        string $provider,
        string $model,
        int $tokensUsed = 0,
        int $latencyMs = 0,
        array $toolCalls = [],
        array $rawResponse = []
    ): self {
        return new self(
            success: true,
            content: $content,
            provider: $provider,
            model: $model,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            toolCalls: $toolCalls,
            rawResponse: $rawResponse
        );
    }

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'content'     => $this->content,
            'provider'    => $this->provider,
            'model'       => $this->model,
            'tokens_used' => $this->tokensUsed,
            'latency_ms'  => $this->latencyMs,
            'tool_calls'  => $this->toolCalls,
            'error'       => $this->error,
        ];
    }
}
