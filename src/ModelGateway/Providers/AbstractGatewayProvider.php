<?php

namespace Atom\ModelGateway\Providers;

use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;

abstract class AbstractGatewayProvider
{
    protected string $name;
    protected ProviderCapabilities $capabilities;

    public function __construct(string $name, ?ProviderCapabilities $capabilities = null)
    {
        $this->name = strtolower($name);
        $this->capabilities = $capabilities ?? new ProviderCapabilities();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCapabilities(): ProviderCapabilities
    {
        return $this->capabilities;
    }

    abstract public function chat(AtomGatewayRequest $request): AtomGatewayResponse;

    abstract public function stream(AtomGatewayRequest $request, callable $callback): void;

    abstract public function embeddings(string|array $input): array;

    abstract public function modelInfo(string $model): array;

    abstract public function healthCheck(): bool;
}
