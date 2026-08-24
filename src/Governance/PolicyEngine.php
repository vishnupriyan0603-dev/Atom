<?php

namespace Atom\Governance;

use Atom\Telemetry\TelemetryManager;
use CodeIgniter\Database\BaseConnection;

class PolicyEngine
{
    private TrustEngine $trustEngine;

    public function __construct(?TrustEngine $trustEngine = null)
    {
        $this->trustEngine = $trustEngine ?? new TrustEngine();
    }

    private function getDb(): ?BaseConnection
    {
        try {
            return \Config\Database::connect();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Authoritative policy decision evaluation.
     * Deny rules always take precedence over allow/optimization rules.
     */
    public function evaluate(int $actorId, string $action, string $resource, array $context = []): GovernanceDecision
    {
        $span = TelemetryManager::getInstance()->startSpan('governance.evaluate');

        // Check Kill Switch first
        if (KillSwitchManager::isKilled('resource', $resource) || KillSwitchManager::isKilled('action', $action)) {
            TelemetryManager::getInstance()->endSpan($span, 'ok');
            return new GovernanceDecision('deny', 1, ['KILL_SWITCH_ACTIVE']);
        }

        $decision = 'allow';
        $reasonCodes = ['POLICY_MATCH_ALLOW'];

        // Strict risk / permission rules
        if (strpos($action, 'delete') !== false || strpos($action, 'drop') !== false || strpos($action, 'admin') !== false) {
            $trust = $this->trustEngine->getTrustLevel($actorId);
            if (!$this->trustEngine->meetsTrustThreshold($trust, 'privileged')) {
                $decision = 'require_approval';
                $reasonCodes = ['HIGH_RISK_ACTION', 'REQUIRES_PRIVILEGED_TRUST'];
            }
        }

        // Audit log decision
        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_governance_decisions'), true)->insert([
                    'actor_id'          => $actorId,
                    'action'            => $action,
                    'resource'          => $resource,
                    'decision'          => $decision,
                    'reason_codes_json' => json_encode($reasonCodes),
                    'policy_id'         => 1,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
        }

        TelemetryManager::getInstance()->endSpan($span, 'ok');

        return new GovernanceDecision($decision, 1, $reasonCodes);
    }
}
