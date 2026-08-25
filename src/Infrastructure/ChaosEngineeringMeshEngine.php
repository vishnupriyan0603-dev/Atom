<?php

namespace Atom\Infrastructure;

use Atom\Security\SecretRedactor;

/**
 * ChaosEngineeringMeshEngine — Phase 81
 * Autonomous chaos engineering experiment runner, multi-vector fault injector, and blast-radius safety governor.
 */
class ChaosEngineeringMeshEngine
{
    private SecretRedactor $redactor;
    private array $activeExperiments = [];
    private bool $emergencyStop = false;
    private int $maxBlastRadiusPct = 25; // Max 25% traffic can be subjected to chaos

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleExperiment();
    }

    /**
     * Start a new controlled chaos experiment.
     */
    public function startExperiment(string $experimentId, string $faultType, int $blastRadiusPct = 10, array $targets = []): array
    {
        $cleanId = trim($this->redactor->redact($experimentId));
        $validFaults = ['latency', 'http_500_error', 'memory_pressure', 'packet_loss'];

        if (!in_array($faultType, $validFaults, true)) {
            return [
                'success' => false,
                'error' => "Invalid fault type. Allowed: " . implode(', ', $validFaults),
            ];
        }

        $clampedBlast = max(1, min($this->maxBlastRadiusPct, $blastRadiusPct));

        $this->activeExperiments[$cleanId] = [
            'experiment_id' => $cleanId,
            'fault_type' => $faultType,
            'blast_radius_pct' => $clampedBlast,
            'targets' => $targets,
            'status' => 'RUNNING',
            'injected_faults_count' => 0,
            'started_at' => microtime(true),
        ];

        return [
            'success' => true,
            'experiment_id' => $cleanId,
            'fault_type' => $faultType,
            'blast_radius_pct' => $clampedBlast,
            'status' => 'EXPERIMENT_ACTIVE',
        ];
    }

    /**
     * Evaluate if an incoming request should have a fault injected.
     */
    public function shouldInjectFault(string $requestId, string $targetEndpoint): array
    {
        if ($this->emergencyStop || empty($this->activeExperiments)) {
            return ['inject_fault' => false, 'fault_type' => 'none', 'reason' => 'NO_ACTIVE_CHAOS'];
        }

        $cleanReqId = trim($this->redactor->redact($requestId));

        foreach ($this->activeExperiments as $id => &$exp) {
            if ($exp['status'] !== 'RUNNING') {
                continue;
            }

            // Check if endpoint is targeted
            if (!empty($exp['targets']) && !in_array($targetEndpoint, $exp['targets'], true)) {
                continue;
            }

            // Deterministic hash based on request ID
            $hash = abs(crc32($cleanReqId . ':' . $id)) % 100;
            if ($hash < $exp['blast_radius_pct']) {
                $exp['injected_faults_count']++;
                return [
                    'inject_fault' => true,
                    'experiment_id' => $id,
                    'fault_type' => $exp['fault_type'],
                    'reason' => "FAULT_INJECTED_{$exp['fault_type']}",
                ];
            }
        }

        return ['inject_fault' => false, 'fault_type' => 'none', 'reason' => 'BLAST_RADIUS_EXCLUDED'];
    }

    /**
     * Stop a specific chaos experiment or trigger emergency stop across all.
     */
    public function stopExperiment(?string $experimentId = null): bool
    {
        if ($experimentId === null) {
            $this->emergencyStop = true;
            foreach ($this->activeExperiments as &$exp) {
                $exp['status'] = 'STOPPED_EMERGENCY';
            }
            return true;
        }

        if (isset($this->activeExperiments[$experimentId])) {
            $this->activeExperiments[$experimentId]['status'] = 'STOPPED';
            return true;
        }

        return false;
    }

    public function getActiveExperiments(): array
    {
        return [
            'emergency_stop_engaged' => $this->emergencyStop,
            'active_count' => count(array_filter($this->activeExperiments, fn($e) => $e['status'] === 'RUNNING')),
            'experiments' => array_values($this->activeExperiments),
        ];
    }

    private function seedSampleExperiment(): void
    {
        $this->startExperiment('exp_upstream_latency_test', 'latency', 10, ['/api/users/profile', '/api/orders']);
    }
}
