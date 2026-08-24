<?php

namespace App\Controllers\Api;

use Atom\Infrastructure\IncidentEventClassifier;
use Atom\Infrastructure\RunbookRemediationExecutor;
use Atom\Infrastructure\CircuitBreakerOrchestrator;
use Atom\Infrastructure\PostMortemGenerator;

/**
 * Self-Healing Infrastructure & Incident Response API Controller — Phase 40
 *
 * Endpoints:
 * - POST /api/v1/incident/classify        — Classify infrastructure alert & severity
 * - POST /api/v1/incident/remediate       — Execute self-healing runbook playbook
 * - POST /api/v1/incident/circuit/record  — Record circuit breaker result
 * - POST /api/v1/incident/postmortem      — Generate RCA incident post-mortem
 * - GET  /api/v1/incident/status          — Overview of active circuit breakers & runbooks
 */
class IncidentResponse extends BaseApiController
{
    private static ?IncidentEventClassifier $classifierInstance = null;
    private static ?RunbookRemediationExecutor $runbookInstance = null;
    private static ?CircuitBreakerOrchestrator $cbInstance = null;
    private static ?PostMortemGenerator $rcaInstance = null;

    private function getClassifier(): IncidentEventClassifier
    {
        if (self::$classifierInstance === null) {
            self::$classifierInstance = new IncidentEventClassifier();
        }
        return self::$classifierInstance;
    }

    private function getRunbookExecutor(): RunbookRemediationExecutor
    {
        if (self::$runbookInstance === null) {
            self::$runbookInstance = new RunbookRemediationExecutor();
        }
        return self::$runbookInstance;
    }

    private function getCircuitBreaker(): CircuitBreakerOrchestrator
    {
        if (self::$cbInstance === null) {
            self::$cbInstance = new CircuitBreakerOrchestrator('api_gateway', 3, 5.0);
        }
        return self::$cbInstance;
    }

    private function getPostMortemGenerator(): PostMortemGenerator
    {
        if (self::$rcaInstance === null) {
            self::$rcaInstance = new PostMortemGenerator();
        }
        return self::$rcaInstance;
    }

    /**
     * POST /api/v1/incident/classify
     */
    public function classify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $event = [
            'message'    => $json['message'] ?? 'database connection refused after timeout',
            'error_rate' => (float)($json['error_rate'] ?? 25.0),
            'latency_ms' => (float)($json['latency_ms'] ?? 4200.0),
            'subsystem'  => $json['subsystem'] ?? 'database_pool',
        ];

        $classification = $this->getClassifier()->classify($event);
        return $this->respondSuccess($classification, 'Incident event classified');
    }

    /**
     * POST /api/v1/incident/remediate
     */
    public function remediate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $runbook = $json['runbook'] ?? 'drain_connection_pool';
        $subsystem = $json['subsystem'] ?? 'database_pool';

        $result = $this->getRunbookExecutor()->executeRunbook($runbook, $subsystem);
        return $this->respondSuccess($result, 'Self-healing runbook executed successfully');
    }

    /**
     * POST /api/v1/incident/circuit/record
     */
    public function recordCircuit()
    {
        $json = $this->request->getJSON(true) ?? [];
        $success = (bool)($json['success'] ?? true);
        $cb = $this->getCircuitBreaker();

        if ($success) {
            $cb->recordSuccess();
        } else {
            $cb->recordFailure();
        }

        return $this->respondSuccess([
            'state'         => $cb->getState(),
            'failure_count' => $cb->getFailureCount(),
            'allow_traffic' => $cb->allowExecution(),
        ], 'Circuit breaker state updated');
    }

    /**
     * POST /api/v1/incident/postmortem
     */
    public function postMortem()
    {
        $json = $this->request->getJSON(true) ?? [];
        $incident = [
            'incident_id'      => $json['incident_id'] ?? 'inc_prod_401',
            'severity'         => $json['severity'] ?? 'SEV2_MAJOR',
            'subsystem'        => $json['subsystem'] ?? 'database_pool',
            'root_cause'       => $json['root_cause'] ?? 'Unindexed query causing connection pool exhaustion',
            'downtime_minutes' => (float)($json['downtime_minutes'] ?? 3.5),
        ];

        $rca = $this->getPostMortemGenerator()->generate($incident);
        return $this->respondSuccess($rca, 'Incident post-mortem report generated');
    }

    /**
     * GET /api/v1/incident/status
     */
    public function status()
    {
        $cb = $this->getCircuitBreaker();
        return $this->respondSuccess([
            'circuit_breaker' => [
                'state'         => $cb->getState(),
                'failure_count' => $cb->getFailureCount(),
            ],
            'recent_runbooks' => $this->getRunbookExecutor()->getHistory(),
            'system_health'   => 'SELF_HEALING_ONLINE',
        ], 'Incident response status overview');
    }
}
