<?php

namespace Atom\Infrastructure;

/**
 * Circuit Breaker Orchestrator — Phase 40
 *
 * Implements resilient 3-state circuit breaker protection (CLOSED, OPEN, HALF_OPEN)
 * to prevent cascading microservice and database failures.
 */
class CircuitBreakerOrchestrator
{
    public const STATE_CLOSED    = 'CLOSED';    // Healthy, pass all traffic
    public const STATE_OPEN      = 'OPEN';      // Failing, reject traffic immediately
    public const STATE_HALF_OPEN = 'HALF_OPEN'; // Probing, test single request

    private string $serviceName;
    private string $state = self::STATE_CLOSED;
    private int $failureThreshold;
    private float $cooldownSeconds;
    private int $failureCount = 0;
    private float $lastTrippedTime = 0.0;

    public function __construct(string $serviceName = 'default_service', int $failureThreshold = 3, float $cooldownSeconds = 5.0)
    {
        $this->serviceName = $serviceName;
        $this->failureThreshold = max(1, $failureThreshold);
        $this->cooldownSeconds = max(0.1, $cooldownSeconds);
    }

    public function getState(): string
    {
        // Check if OPEN circuit has cooled down and should become HALF_OPEN
        if ($this->state === self::STATE_OPEN && (microtime(true) - $this->lastTrippedTime) >= $this->cooldownSeconds) {
            $this->state = self::STATE_HALF_OPEN;
        }
        return $this->state;
    }

    /**
     * Checks if traffic is allowed through the circuit.
     */
    public function allowExecution(): bool
    {
        $st = $this->getState();
        return ($st === self::STATE_CLOSED || $st === self::STATE_HALF_OPEN);
    }

    /**
     * Records a successful execution and resets failure count.
     */
    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = self::STATE_CLOSED;
    }

    /**
     * Records a failed execution and trips the circuit if threshold exceeded.
     */
    public function recordFailure(): void
    {
        $this->failureCount++;
        if ($this->failureCount >= $this->failureThreshold || $this->state === self::STATE_HALF_OPEN) {
            $this->state = self::STATE_OPEN;
            $this->lastTrippedTime = microtime(true);
        }
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }
}
