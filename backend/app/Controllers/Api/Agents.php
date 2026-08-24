<?php

namespace App\Controllers\Api;

use Atom\Agent\AgentOrchestrator;

class Agents extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * POST /api/v1/agents/tasks - Create and run a new agent task.
     */
    public function createTask()
    {
        $json = $this->request->getJSON(true) ?? [];
        $objective = $json['objective'] ?? $this->request->getPost('objective');

        if (empty($objective)) {
            return $this->respondError('Objective parameter is required', 400);
        }

        $userId = (int)($json['user_id'] ?? 1);
        $orchestrator = new AgentOrchestrator();
        $task = $orchestrator->createTask($objective, $userId, $json);
        $completedTask = $orchestrator->runTask($task);

        return $this->respondSuccess($completedTask->toArray(), 'Agent task initialized');
    }

    /**
     * GET /api/v1/agents/tasks - List agent tasks.
     */
    public function getTasks()
    {
        $db = $this->getDb();
        $tasks = [];
        if ($db !== null) {
            try {
                $tasks = $db->table($db->prefixTable('atom_agent_tasks'), true)
                            ->orderBy('id', 'DESC')
                            ->get(20)
                            ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($tasks);
    }

    /**
     * GET /api/v1/agents/tasks/{id} - Task detail inspection.
     */
    public function getTask($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Task ID required', 400);
        }

        $db = $this->getDb();
        $task = null;
        if ($db !== null) {
            try {
                $task = $db->table($db->prefixTable('atom_agent_tasks'), true)
                           ->where('id', (int)$id)
                           ->get()
                           ->getRowArray();
            } catch (\Throwable $e) {}
        }

        if (!$task) {
            return $this->respondError('Agent task not found', 404);
        }

        return $this->respondSuccess($task);
    }

    /**
     * POST /api/v1/agents/tasks/{id}/cancel - Cancel an active task.
     */
    public function cancelTask($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Task ID required', 400);
        }

        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_agent_tasks'), true)
                   ->where('id', (int)$id)
                   ->update([
                       'status'       => 'cancelled',
                       'cancelled_at' => date('Y-m-d H:i:s'),
                   ]);
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess(['id' => (int)$id, 'status' => 'cancelled'], 'Task cancelled');
    }

    /**
     * GET /api/v1/agents/tasks/{id}/steps - List plan steps for task.
     */
    public function getTaskSteps($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Task ID required', 400);
        }

        $db = $this->getDb();
        $steps = [];
        if ($db !== null) {
            try {
                $steps = $db->table($db->prefixTable('atom_agent_steps'), true)
                            ->where('task_id', (int)$id)
                            ->orderBy('sequence', 'ASC')
                            ->get()
                            ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($steps);
    }

    /**
     * GET /api/v1/agents/tasks/{id}/stream - SSE live execution event stream.
     */
    public function streamTaskEvents($id = null)
    {
        response()->setHeader('Content-Type', 'text/event-stream');
        response()->setHeader('Cache-Control', 'no-cache');
        response()->setHeader('Connection', 'keep-alive');

        $db = $this->getDb();
        $events = [];
        if ($db !== null && !empty($id)) {
            try {
                $events = $db->table($db->prefixTable('atom_agent_events'), true)
                             ->where('task_id', (int)$id)
                             ->orderBy('id', 'ASC')
                             ->get()
                             ->getResultArray();
            } catch (\Throwable $e) {}
        }

        foreach ($events as $evt) {
            echo "event: " . $evt['event_type'] . "\n";
            echo "data: " . $evt['payload'] . "\n\n";
        }

        exit;
    }
}
