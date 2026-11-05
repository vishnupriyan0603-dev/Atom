<?php

namespace Atom\DevOps;

class ServiceMeshRoutingGovernor
{
    private array $virtualServices = [];
    private array $circuitBreakers = [];

    /**
     * Configure virtual service traffic weighting between baseline and canary.
     */
    public function configureTrafficSplit(string $serviceName, int $primaryWeight = 90, int $canaryWeight = 10): array
    {
        if ($primaryWeight + $canaryWeight !== 100) {
            $canaryWeight = 100 - $primaryWeight;
        }

        $config = [
            'service' => $serviceName,
            'mtls_enforced' => true,
            'routes' => [
                ['destination' => 'v1-primary', 'weight_percent' => $primaryWeight],
                ['destination' => 'v2-canary', 'weight_percent' => $canaryWeight],
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->virtualServices[$serviceName] = $config;
        return $config;
    }

    /**
     * Evaluate circuit breaker health based on consecutive error counts.
     */
    public function evaluateCircuitBreaker(string $serviceName, int $consecutiveErrors = 0, int $threshold = 5): array
    {
        $tripped = ($consecutiveErrors >= $threshold);
        $state = $tripped ? 'OPEN (TRIPPED)' : ($consecutiveErrors > 0 ? 'HALF_OPEN' : 'CLOSED (HEALTHY)');

        $record = [
            'service' => $serviceName,
            'state' => $state,
            'consecutive_errors' => $consecutiveErrors,
            'threshold' => $threshold,
            'action_taken' => $tripped ? 'Traffic diverted to fallback mesh' : 'Normal traffic routing',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->circuitBreakers[$serviceName] = $record;
        return $record;
    }

    public function getStatus(): array
    {
        return [
            'virtual_services' => $this->virtualServices,
            'circuit_breakers' => $this->circuitBreakers,
        ];
    }
}