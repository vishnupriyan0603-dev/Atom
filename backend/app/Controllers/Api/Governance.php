<?php

namespace App\Controllers\Api;

use Atom\Governance\PolicyEngine;
use Atom\Governance\PolicySimulator;
use Atom\Governance\KillSwitchManager;

class Governance extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * GET /api/v1/governance/policies - List governance policies.
     */
    public function getPolicies()
    {
        $db = $this->getDb();
        $policies = [];
        if ($db !== null) {
            try {
                $policies = $db->table($db->prefixTable('atom_governance_policies'), true)
                               ->orderBy('id', 'DESC')
                               ->get()
                               ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($policies);
    }

    /**
     * POST /api/v1/governance/policies/simulate - Simulate policy evaluation.
     */
    public function simulatePolicy()
    {
        $json = $this->request->getJSON(true) ?? [];
        $actorId  = (int)($json['actor_id'] ?? 1);
        $action   = $json['action'] ?? 'tool.execute';
        $resource = $json['resource'] ?? 'workspace';

        $simulator = new PolicySimulator();
        $res = $simulator->simulate($actorId, $action, $resource, $json);

        return $this->respondSuccess($res, 'Policy simulation completed');
    }

    /**
     * GET /api/v1/governance/decisions - List governance decision audit logs.
     */
    public function getDecisions()
    {
        $db = $this->getDb();
        $decisions = [];
        if ($db !== null) {
            try {
                $decisions = $db->table($db->prefixTable('atom_governance_decisions'), true)
                                ->orderBy('id', 'DESC')
                                ->get(30)
                                ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($decisions);
    }

    /**
     * POST /api/v1/governance/kill-switch - Toggle emergency kill switch.
     */
    public function toggleKillSwitch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $targetType = $json['target_type'] ?? 'resource';
        $targetId   = $json['target_id'] ?? 'workspace';
        $enable     = !empty($json['enable']);

        if ($enable) {
            KillSwitchManager::enableKillSwitch($targetType, $targetId, $json['reason'] ?? '');
        } else {
            KillSwitchManager::disableKillSwitch($targetType, $targetId);
        }

        return $this->respondSuccess([
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'active'      => KillSwitchManager::isKilled($targetType, $targetId),
        ], 'Kill switch toggled');
    }
}
