<?php

namespace Atom\ModelGateway;

interface ModelGatewayInterface
{
    public function chat(AtomGatewayRequest $request): AtomGatewayResponse;

    public function stream(AtomGatewayRequest $request, callable $callback): void;

    public function embeddings(string|array $input, ?string $provider = null): array;

    public function modelInfo(string $provider, string $model): array;

    public function healthCheck(string $provider): bool;

    public function getCapabilities(string $provider): ProviderCapabilities;
}
