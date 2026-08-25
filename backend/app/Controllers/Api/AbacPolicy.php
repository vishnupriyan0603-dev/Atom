<?php

namespace App\Controllers\Api;

use Atom\Auth\AbacPolicyEngine;
use Atom\Auth\AbacPolicyStore;

/**
 * AbacPolicy API Controller — Phase 48
 */
class AbacPolicy extends BaseApiController
{
    private static ?AbacPolicyStore $store = null;

    private function getStore(): AbacPolicyStore
    {
        if (self::$store === null) {
            self::$store = new AbacPolicyStore();
        }
        return self::$store;
    }

    /**
     * POST /api/abac/evaluate
     */
    public function evaluate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $engine = new AbacPolicyEngine($json['combining_algorithm'] ?? 'DenyOverrides');
        $policies = $this->getStore()->listPolicies();

        $result = $engine->evaluate($json, $policies);

        return $this->respondSuccess($result, 'ABAC Access Decision Evaluated');
    }

    /**
     * GET /api/abac/policies
     */
    public function listPolicies()
    {
        $store = $this->getStore();
        return $this->respondSuccess([
            'total_policies' => $store->count(),
            'policies' => $store->listPolicies(),
        ], 'ABAC Policies listed');
    }

    /**
     * POST /api/abac/policies
     */
    public function savePolicy()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (empty($json['id']) || empty($json['target']) || empty($json['rules'])) {
            return $this->respondError('Policy ID, target, and rules are required', 400);
        }

        $store = $this->getStore();
        $store->addPolicy($json);

        return $this->respondSuccess(['policy' => $json], 'Policy saved successfully');
    }

    /**
     * DELETE /api/abac/policies/{id}
     */
    public function deletePolicy($id = null)
    {
        if (!$id) {
            return $this->respondError('Policy ID required', 400);
        }

        $store = $this->getStore();
        $deleted = $store->removePolicy((string)$id);

        if ($deleted) {
            return $this->respondSuccess(['deleted' => true, 'id' => $id], 'Policy removed');
        }
        return $this->respondError('Policy not found', 404);
    }

    /**
     * POST /api/abac/simulate
     */
    public function simulate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $scenarios = $json['scenarios'] ?? [];

        if (empty($scenarios)) {
            // Default 3 audit test scenarios
            $scenarios = [
                [
                    'name' => 'Admin on Corporate Network accessing Vault with MFA',
                    'request' => [
                        'subject' => ['role' => 'admin', 'clearance_level' => 4, 'mfa_verified' => true],
                        'resource' => ['type' => 'vault_secret', 'classification' => 'TopSecret'],
                        'action' => 'read',
                        'environment' => ['ip_address' => '10.1.2.3', 'device_trust_score' => 95],
                    ],
                ],
                [
                    'name' => 'Guest User attempting to terminate Prod Cluster without MFA',
                    'request' => [
                        'subject' => ['role' => 'guest', 'clearance_level' => 1, 'mfa_verified' => false],
                        'resource' => ['type' => 'cluster_infrastructure'],
                        'action' => 'terminate',
                        'environment' => ['ip_address' => '192.168.1.1', 'device_trust_score' => 40],
                    ],
                ],
                [
                    'name' => 'Authenticated Developer reading Public Docs',
                    'request' => [
                        'subject' => ['role' => 'developer', 'is_authenticated' => true],
                        'resource' => ['type' => 'public_document'],
                        'action' => 'read',
                        'environment' => ['ip_address' => '172.16.0.5'],
                    ],
                ],
            ];
        }

        $engine = new AbacPolicyEngine('DenyOverrides');
        $policies = $this->getStore()->listPolicies();
        $results = [];

        foreach ($scenarios as $s) {
            $eval = $engine->evaluate($s['request'], $policies);
            $results[] = [
                'scenario_name' => $s['name'] ?? 'Simulation Scenario',
                'decision' => $eval['decision'],
                'matched_policy' => $eval['matched_policy'],
                'permits' => $eval['permits_count'] ?? 0,
                'denies' => $eval['denies_count'] ?? 0,
            ];
        }

        return $this->respondSuccess([
            'total_scenarios' => count($results),
            'scenarios' => $results,
        ], 'ABAC Multi-Scenario Simulation Completed');
    }
}
