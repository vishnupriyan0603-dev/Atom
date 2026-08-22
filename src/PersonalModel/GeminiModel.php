<?php

namespace Atom\PersonalModel;

use Atom\LLM\LLMInterface;

class GeminiModel implements ModelInterface
{
    private LLMInterface $provider;
    private string $name;
    private string $providerName;

    public function __construct(LLMInterface $provider, string $name = 'gemini-1.5-flash', string $providerName = 'Gemini')
    {
        $this->provider     = $provider;
        $this->name         = $name;
        $this->providerName = $providerName;
    }

    public function generate(array $messages): ModelResponse
    {
        $res = $this->provider->chat($messages);
        return new ModelResponse(
            $res['success'],
            $res['content'] ?? '',
            $res['error'] ?? null
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function isAvailable(): bool
    {
        if (method_exists($this->provider, 'isAvailable')) {
            return $this->provider->isAvailable();
        }
        return false;
    }
}
