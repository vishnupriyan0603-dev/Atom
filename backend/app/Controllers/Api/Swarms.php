<?php

namespace App\Controllers\Api;

use Atom\Swarm\AgentCoordinator;
use Atom\Swarm\AgentSelector;

class Swarms extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * GET /api/v1/swarms - List active and historical swarm executions.
     */
    public function getSwarms()
    {
        $db = $this->getDb();
        $swarms = [];
        if ($db !== null) {
            try {
                $swarms = $db->table($db->prefixTable('atom_swarm_executions'), true)
                             ->orderBy('id', 'DESC')
                             ->get(30)
                             ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($swarms);
    }

    /**
     * POST /api/v1/swarms - Launch multi-agent swarm execution.
     */
    public function createSwarm()
    {
        $json = $this->request->getJSON(true) ?? [];
        $objective = $json['objective'] ?? $this->request->getPost('objective');

        if (empty($objective)) {
            return $this->respondError('Objective is required', 400);
        }

        $coordinator = new AgentCoordinator();
        $swarm = $coordinator->runSwarm($objective);

        return $this->respondSuccess($swarm->toArray(), 'Multi-agent swarm execution started');
    }

    /**
     * GET /api/v1/swarms/{id} - Get swarm execution detail.
     */
    public function getSwarm($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Swarm ID required', 400);
        }

        $db = $this->getDb();
        $swarm = null;
        if ($db !== null) {
            try {
                $swarm = $db->table($db->prefixTable('atom_swarm_executions'), true)
                            ->where('id', (int)$id)
                            ->get()
                            ->getRowArray();
            } catch (\Throwable $e) {}
        }

        if (!$swarm) {
            return $this->respondError('Swarm execution not found', 404);
        }

        return $this->respondSuccess($swarm);
    }

    /**
     * GET /api/v1/swarms/{id}/stream - SSE live event stream.
     */
    public function streamSwarmEvents($id = null)
    {
        response()->setHeader('Content-Type', 'text/event-stream');
        response()->setHeader('Cache-Control', 'no-cache');
        response()->setHeader('Connection', 'keep-alive');

        $db = $this->getDb();
        $events = [];
        if ($db !== null && !empty($id)) {
            try {
                $events = $db->table($db->prefixTable('atom_swarm_events'), true)
                             ->where('swarm_id', (int)$id)
                             ->orderBy('id', 'ASC')
                             ->get()
                             ->getResultArray();
            } catch (\Throwable $e) {}
        }

        foreach ($events as $evt) {
            echo "event: " . $evt['event_type'] . "\n";
            echo "data: " . $evt['payload_json'] . "\n\n";
        }

        exit;
    }

    /**
     * GET /api/v1/agents/definitions - List agent definitions.
     */
    public function getDefinitions()
    {
        $selector = new AgentSelector();
        $defs = [
            $selector->selectAgentForRole('researcher')->toArray(),
            $selector->selectAgentForRole('analyst')->toArray(),
            $selector->selectAgentForRole('verifier')->toArray(),
            $selector->selectAgentForRole('synthesizer')->toArray(),
        ];

        return $this->respondSuccess($defs);
    }
}
