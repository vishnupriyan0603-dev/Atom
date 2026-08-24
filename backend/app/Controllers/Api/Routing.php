<?php

namespace App\Controllers\Api;

use Atom\Routing\RoutingEngine;
use Atom\Routing\RoutingCandidate;

class Routing extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * GET /api/v1/routing/policies - List routing policies.
     */
    public function getPolicies()
    {
        $db = $this->getDb();
        $policies = [];
        if ($db !== null) {
            try {
                $policies = $db->table($db->prefixTable('atom_routing_policies'), true)
                               ->orderBy('id', 'DESC')
                               ->get()
                               ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($policies);
    }

    /**
     * GET /api/v1/routing/candidates - List candidate pool.
     */
    public function getCandidates()
    {
        $db = $this->getDb();
        $candidates = [];
        if ($db !== null) {
            try {
                $candidates = $db->table($db->prefixTable('atom_routing_candidates'), true)
                                 ->get()
                                 ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($candidates);
    }

    /**
     * POST /api/v1/routing/select - Select optimal routing target.
     */
    public function selectCandidate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $engine = new RoutingEngine();
        $decision = $engine->selectCandidate($json);

        return $this->respondSuccess($decision, 'Candidate selected successfully');
    }

    /**
     * GET /api/v1/routing/decisions - Get decision audit logs.
     */
    public function getDecisions()
    {
        $db = $this->getDb();
        $decisions = [];
        if ($db !== null) {
            try {
                $decisions = $db->table($db->prefixTable('atom_routing_decisions'), true)
                                ->orderBy('id', 'DESC')
                                ->get(30)
                                ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($decisions);
    }
}
