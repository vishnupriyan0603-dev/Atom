<?php

namespace Atom\Infrastructure;

use Atom\Security\SecretRedactor;

/**
 * DynamicCircuitBreakerEngine — Phase 85
 * Distributed dynamic circuit breaker mesh, tri-state state machine (CLOSED, OPEN, HALF_OPEN), and automated fallback synthesizers.
 */
class DynamicCircuitBreakerEngine
{
    private SecretRedactor $redactor;
    private array $circuits = [];
    private float $failureThresholdPct = 50.0; // Open circuit if error rate >= 50%
    private float $cooldownSeconds = 5.0; // Cooldown before trying HALF_OPEN
    private int $minRequestsForEvaluation = 5;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleCircuits();
    }

    /**
     * Register or configure a protected service circuit.
     */
    public function registerCircuit(string $serviceName, float $failureThresholdPct = 50.0, float $cooldownSeconds = 5.0): bool
    {
        $cleanName = trim(strtolower($this->redactor->redact($serviceName)));

        $this->circuits[$cleanName] = [
            'service_name' => $cleanName,
            'state' => 'CLOSED', // CLOSED, OPEN, HALF_OPEN
            'failure_threshold_pct' => max(1.0, min(100.0, $failureThresholdPct)),
            'cooldown_seconds' => max(0.5, $cooldownSeconds),
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'consecutive_failures' => 0,
            'last_state_change' => microtime(true),
            'last_failure_time' => 0.0,
        ];

        return true;
    }

    /**
     * Execute a protected operation with circuit breaker state management and fallback handling.
     *
     * @param string $serviceName
     * @param callable $operation Operation to execute
     * @param mixed $fallbackValue Fallback value if circuit is OPEN or operation throws
     * @return array Result envelope
     */
    public function execute(string $serviceName, callable $operation, mixed $fallbackValue = 'SERVICE_DEGRADED_FALLBACK'): array
    {
        $cleanName = trim(strtolower($this->redactor->redact($serviceName)));

        if (!isset($this->circuits[$cleanName])) {
            $this->registerCircuit($cleanName);
        }

        $circuit = &$this->circuits[$cleanName];
        $now = microtime(true);

        // Check if OPEN circuit should transition to HALF_OPEN
        if ($circuit['state'] === 'OPEN') {
            if (($now - $circuit['last_state_change']) >= $circuit['cooldown_seconds']) {
                $circuit['state'] = 'HALF_OPEN';
                $circuit['last_state_change'] = $now;
            } else {
                return [
                    'success' => false,
                    'circuit_state' => 'OPEN',
                    'result' => $fallbackValue,
                    'reason' => 'CIRCUIT_OPEN_FAST_FAIL',
                ];
            }
        }

        $circuit['total_requests']++;

        try {
            $result = $operation();
            $circuit['successful_requests']++;
            $circuit['consecutive_failures'] = 0;

            // If we succeeded in HALF_OPEN, close the circuit
            if ($circuit['state'] === 'HALF_OPEN') {
                $circuit['state'] = 'CLOSED';
                $circuit['last_state_change'] = $now;
            }

            return [
                'success' => true,
                'circuit_state' => $circuit['state'],
                'result' => $result,
                'reason' => 'EXECUTION_SUCCESS',
            ];
        } catch (\Throwable $e) {
            $circuit['failed_requests']++;
            $circuit['consecutive_failures']++;
            $circuit['last_failure_time'] = $now;

            // If failed in HALF_OPEN, reopen circuit immediately
            if ($circuit['state'] === 'HALF_OPEN') {
                $circuit['state'] = 'OPEN';
                $circuit['last_state_change'] = $now;
            } else {
                // Check if threshold exceeded in CLOSED state
                if ($circuit['total_requests'] >= $this->minRequestsForEvaluation) {
                    $errorRate = ($circuit['failed_requests'] / $circuit['total_requests']) * 100.0;
                    if ($errorRate >= $circuit['failure_threshold_pct']) {
                        $circuit['state'] = 'OPEN';
                        $circuit['last_state_change'] = $now;
                    }
                }
            }

            return [
                'success' => false,
                'circuit_state' => $circuit['state'],
                'result' => $fallbackValue,
                'error' => $e->getMessage(),
                'reason' => 'OPERATION_FAILED_FALLBACK_TRIGGERED',
            ];
        }
    }

    /**
     * Force reset or trip a circuit state.
     */
    public function setCircuitState(string $serviceName, string $targetState): bool
    {
        $cleanName = trim(strtolower($this->redactor->redact($serviceName)));
        $validStates = ['CLOSED', 'OPEN', 'HALF_OPEN'];

        if (!in_array($targetState, $validStates, true) || !isset($this->circuits[$cleanName])) {
            return false;
        }

        $this->circuits[$cleanName]['state'] = $targetState;
        $this->circuits[$cleanName]['last_state_change'] = microtime(true);

        if ($targetState === 'CLOSED') {
            $this->circuits[$cleanName]['consecutive_failures'] = 0;
        }

        return true;
    }

    public function getAllCircuits(): array
    {
        $now = microtime(true);
        $result = [];

        foreach ($this->circuits as $name => $c) {
            $total = max(1, $c['total_requests']);
            $errorRate = round(($c['failed_requests'] / $total) * 100, 1);
            $timeInState = round($now - $c['last_state_change'], 2);

            $result[] = array_merge($c, [
                'error_rate_pct' => $errorRate,
                'time_in_state_s' => $timeInState,
            ]);
        }

        return $result;
    }

    private function seedSampleCircuits(): void
    {
        $this->registerCircuit('payment_gateway_api', 40.0, 5.0);
        $this->registerCircuit('upstream_weather_service', 50.0, 3.0);
        $this->registerCircuit('external_sms_provider', 30.0, 10.0);
    }
}
