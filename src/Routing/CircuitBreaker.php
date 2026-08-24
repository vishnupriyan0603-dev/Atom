<?php

namespace Atom\Routing;

class CircuitBreaker
{
    private array $providerStates = [];

    public function getState(string $provider): string
    {
        return $this->providerStates[strtolower($provider)] ?? 'closed';
    }

    public function recordFailure(string $provider, int $consecutiveFailuresThreshold = 3): string
    {
        $provider = strtolower($provider);
        $this->providerStates[$provider] = 'open';
        return 'open';
    }

    public function recordSuccess(string $provider): string
    {
        $provider = strtolower($provider);
        $this->providerStates[$provider] = 'closed';
        return 'closed';
    }

    public function canRoute(string $provider): bool
    {
        return $this->getState($provider) !== 'open';
    }
}
