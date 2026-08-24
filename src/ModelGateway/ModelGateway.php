<?php

namespace Atom\ModelGateway;

use Atom\ModelGateway\Providers\AbstractGatewayProvider;
use Atom\ModelGateway\Providers\OllamaGatewayProvider;
use Atom\ModelGateway\Providers\LlamaCppGatewayProvider;
use Atom\ModelGateway\Providers\OpenAICompatibleGatewayProvider;
use Atom\ModelGateway\Providers\GeminiGatewayProvider;

class ModelGateway implements ModelGatewayInterface
{
    /** @var array<string, AbstractGatewayProvider> */
    private array $providers = [];
    private bool $fallbackEnabled = true;
    private ?string $defaultFallbackProvider = 'openai';

    public function __construct(bool $fallbackEnabled = true, ?string $defaultFallbackProvider = 'openai')
    {
        $this->fallbackEnabled = $fallbackEnabled;
        $this->defaultFallbackProvider = $defaultFallbackProvider ? strtolower($defaultFallbackProvider) : null;
    }

    public function registerProvider(string $alias, AbstractGatewayProvider $provider): void
    {
        $this->providers[strtolower($alias)] = $provider;
    }

    public function getProvider(string $alias): ?AbstractGatewayProvider
    {
        return $this->providers[strtolower($alias)] ?? null;
    }

    public function chat(AtomGatewayRequest $request): AtomGatewayResponse
    {
        $providerName = strtolower($request->provider);
        $provider = $this->getProvider($providerName);

        if ($provider === null) {
            return AtomGatewayResponse::error("Unregistered model provider: '{$providerName}'", $providerName, $request->model);
        }

        $response = $provider->chat($request);

        // Failover / Fallback logic if primary fails
        if (!$response->success && $this->fallbackEnabled) {
            $fallbackAlias = $this->defaultFallbackProvider;
            if ($fallbackAlias !== null && $fallbackAlias !== $providerName && isset($this->providers[$fallbackAlias])) {
                $fallbackProvider = $this->providers[$fallbackAlias];
                if ($fallbackProvider->healthCheck()) {
                    $fallbackRequest = clone $request;
                    $fallbackRequest->provider = $fallbackAlias;
                    $fallbackResponse = $fallbackProvider->chat($fallbackRequest);
                    if ($fallbackResponse->success) {
                        return $fallbackResponse;
                    }
                }
            }

            // Try any remaining healthy providers
            foreach ($this->providers as $alias => $altProvider) {
                if ($alias === $providerName || $alias === $fallbackAlias) {
                    continue;
                }
                if ($altProvider->healthCheck()) {
                    $altRequest = clone $request;
                    $altRequest->provider = $alias;
                    $altResponse = $altProvider->chat($altRequest);
                    if ($altResponse->success) {
                        return $altResponse;
                    }
                }
            }
        }

        return $response;
    }

    public function stream(AtomGatewayRequest $request, callable $callback): void
    {
        $providerName = strtolower($request->provider);
        $provider = $this->getProvider($providerName);

        if ($provider !== null) {
            $provider->stream($request, $callback);
        }
    }

    public function embeddings(string|array $input, ?string $provider = null): array
    {
        $providerName = strtolower($provider ?: 'openai');
        $p = $this->getProvider($providerName) ?? reset($this->providers);

        if ($p !== false && $p !== null) {
            return $p->embeddings($input);
        }

        return [];
    }

    public function modelInfo(string $provider, string $model): array
    {
        $p = $this->getProvider($provider);
        if ($p !== null) {
            return $p->modelInfo($model);
        }
        return ['provider' => $provider, 'model' => $model, 'type' => 'unknown'];
    }

    public function healthCheck(string $provider): bool
    {
        $p = $this->getProvider($provider);
        return $p !== null ? $p->healthCheck() : false;
    }

    public function getCapabilities(string $provider): ProviderCapabilities
    {
        $p = $this->getProvider($provider);
        return $p !== null ? $p->getCapabilities() : new ProviderCapabilities();
    }
}
