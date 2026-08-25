<?php

namespace Atom\Infrastructure;

use Atom\Security\SecretRedactor;

/**
 * CanaryTrafficSplitEngine — Phase 71
 * Autonomous canary deployment governor, weighted traffic split router, and automated rollback circuit breaker.
 */
class CanaryTrafficSplitEngine
{
    private SecretRedactor $redactor;
    private int $canaryWeightPct = 10; // 10% canary, 90% stable
    private string $stableVersion = 'v1.4.0-stable';
    private string $canaryVersion = 'v1.5.0-canary';
    private array $canaryTenants = ['tenant_beta', 'internal_qa'];

    private int $canaryRequests = 0;
    private int $canaryErrors = 0;
    private float $maxErrorRateThreshold = 0.05; // 5% error threshold triggers automatic rollback
    private bool $circuitTripped = false;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Route an incoming request based on tenant affinity, sticky headers, or hash-based weighted splitting.
     */
    public function routeRequest(string $requestId, string $tenantId = 'default', array $headers = []): array
    {
        $cleanReqId = trim($this->redactor->redact($requestId));

        // 1. Check if circuit breaker tripped
        if ($this->circuitTripped || $this->canaryWeightPct === 0) {
            return [
                'target_version' => $this->stableVersion,
                'is_canary' => false,
                'reason' => $this->circuitTripped ? 'CIRCUIT_TRIPPED_AUTO_ROLLED_BACK' : 'CANARY_WEIGHT_ZERO',
                'stable_version' => $this->stableVersion,
                'canary_version' => $this->canaryVersion,
            ];
        }

        // 2. Explicit Canary Override Header
        if (isset($headers['X-Canary-Override']) && strtolower($headers['X-Canary-Override']) === 'true') {
            $this->canaryRequests++;
            return [
                'target_version' => $this->canaryVersion,
                'is_canary' => true,
                'reason' => 'OVERRIDE_HEADER_MATCH',
                'stable_version' => $this->stableVersion,
                'canary_version' => $this->canaryVersion,
            ];
        }

        // 3. Canary Tenant Affinity
        if (in_array(strtolower($tenantId), $this->canaryTenants, true)) {
            $this->canaryRequests++;
            return [
                'target_version' => $this->canaryVersion,
                'is_canary' => true,
                'reason' => 'TENANT_AFFINITY_MATCH',
                'stable_version' => $this->stableVersion,
                'canary_version' => $this->canaryVersion,
            ];
        }

        // 4. Weighted Hash Distribution
        $hash = abs(crc32($cleanReqId . $tenantId)) % 100;
        $isCanary = $hash < $this->canaryWeightPct;

        if ($isCanary) {
            $this->canaryRequests++;
        }

        return [
            'target_version' => $isCanary ? $this->canaryVersion : $this->stableVersion,
            'is_canary' => $isCanary,
            'reason' => $isCanary ? 'WEIGHTED_HASH_CANARY' : 'WEIGHTED_HASH_STABLE',
            'stable_version' => $this->stableVersion,
            'canary_version' => $this->canaryVersion,
        ];
    }

    /**
     * Record outcome of a canary request and trigger circuit breaker if error rate exceeds threshold.
     */
    public function recordCanaryTelemetry(bool $isSuccess): array
    {
        if (!$isSuccess) {
            $this->canaryErrors++;
        }

        $errorRate = $this->canaryRequests > 0 ? ($this->canaryErrors / $this->canaryRequests) : 0.0;

        if ($this->canaryRequests >= 10 && $errorRate >= $this->maxErrorRateThreshold) {
            $this->circuitTripped = true;
            $this->canaryWeightPct = 0; // automatic rollback
        }

        return [
            'canary_requests' => $this->canaryRequests,
            'canary_errors' => $this->canaryErrors,
            'error_rate_pct' => round($errorRate * 100, 2),
            'circuit_tripped' => $this->circuitTripped,
            'status' => $this->circuitTripped ? 'ROLLED_BACK' : 'HEALTHY',
        ];
    }

    /**
     * Adjust canary traffic percentage (0 - 100%).
     */
    public function setCanaryWeight(int $pct): bool
    {
        $this->canaryWeightPct = max(0, min(100, $pct));
        if ($this->canaryWeightPct > 0) {
            $this->circuitTripped = false;
        }
        return true;
    }

    public function resetCircuitBreaker(): void
    {
        $this->circuitTripped = false;
        $this->canaryRequests = 0;
        $this->canaryErrors = 0;
    }

    public function getStatus(): array
    {
        $errorRate = $this->canaryRequests > 0 ? ($this->canaryErrors / $this->canaryRequests) : 0.0;

        return [
            'canary_weight_pct' => $this->canaryWeightPct,
            'stable_weight_pct' => 100 - $this->canaryWeightPct,
            'stable_version' => $this->stableVersion,
            'canary_version' => $this->canaryVersion,
            'canary_tenants' => $this->canaryTenants,
            'canary_requests' => $this->canaryRequests,
            'canary_errors' => $this->canaryErrors,
            'error_rate_pct' => round($errorRate * 100, 2),
            'circuit_tripped' => $this->circuitTripped,
            'status' => $this->circuitTripped ? 'CIRCUIT_TRIPPED_ROLLED_BACK' : 'DEPLOYMENT_HEALTHY',
        ];
    }
}
