<?php

namespace Atom\DevOps;

class ChaosFaultInjectionEngine
{
    private array $activeFaults = [];
    private array $experimentHistory = [];

    /**
     * Inject a simulated fault into a target system operation.
     */
    public function simulateFault(string $faultType, callable $operation, array $options = []): array
    {
        $faultType = strtolower(trim($faultType));
        $t0 = microtime(true);
        $faultInjected = false;
        $recovered = false;
        $result = null;
        $error = null;

        switch ($faultType) {
            case 'latency':
            case 'latency_spike':
                $delayMs = (int)($options['delay_ms'] ?? 20); // Simulated delay
                usleep($delayMs * 1000);
                $faultInjected = true;
                break;

            case 'error_burst':
            case 'transient_500':
                $failureRate = (float)($options['failure_rate'] ?? 0.3);
                if ((mt_rand(1, 100) / 100.0) <= $failureRate) {
                    $faultInjected = true;
                    $error = 'Chaos Injected: Simulated transient upstream 503 error';
                }
                break;

            case 'memory_pressure':
                $faultInjected = true;
                break;
        }

        // Execute operation with resilience check
        if ($error === null) {
            try {
                $result = $operation();
                $recovered = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $recovered = false;
            }
        }

        $durationMs = (microtime(true) - $t0) * 1000.0;

        $experiment = [
            'fault_type' => $faultType,
            'fault_injected' => $faultInjected,
            'recovered' => $recovered,
            'duration_ms' => round($durationMs, 2),
            'resilience_score' => $recovered ? 100 : 0,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->experimentHistory[] = $experiment;
        return $experiment;
    }

    public function getHistory(): array
    {
        return $this->experimentHistory;
    }
}