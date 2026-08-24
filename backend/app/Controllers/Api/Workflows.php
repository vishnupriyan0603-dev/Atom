<?php

namespace App\Controllers\Api;

use Atom\Workflow\WorkflowExecutor;
use Atom\Workflow\WorkflowValidator;

class Workflows extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * GET /api/v1/workflows - List workflows.
     */
    public function getWorkflows()
    {
        $db = $this->getDb();
        $workflows = [];
        if ($db !== null) {
            try {
                $workflows = $db->table($db->prefixTable('atom_workflows'), true)
                                ->orderBy('id', 'DESC')
                                ->get()
                                ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($workflows);
    }

    /**
     * POST /api/v1/workflows - Create a new workflow.
     */
    public function createWorkflow()
    {
        $json = $this->request->getJSON(true) ?? [];
        $name = $json['name'] ?? $this->request->getPost('name');

        if (empty($name)) {
            return $this->respondError('Workflow name is required', 400);
        }

        $db = $this->getDb();
        $data = [
            'owner_user_id'   => 1,
            'name'            => $name,
            'description'     => $json['description'] ?? null,
            'status'          => 'published',
            'current_version' => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
            'published_at'    => date('Y-m-d H:i:s'),
        ];

        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_workflows'), true)->insert($data);
                $data['id'] = (int)$db->insertID();
            } catch (\Throwable $e) {
                $data['id'] = time();
            }
        } else {
            $data['id'] = time();
        }

        return $this->respondSuccess($data, 'Workflow created successfully');
    }

    /**
     * POST /api/v1/workflows/{id}/execute - Execute target workflow.
     */
    public function executeWorkflow($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Workflow ID required', 400);
        }

        $json = $this->request->getJSON(true) ?? [];
        $inputs = $json['input'] ?? $json;

        $executor = new WorkflowExecutor();
        $execution = $executor->executeWorkflow((int)$id, $inputs);

        return $this->respondSuccess($execution->toArray(), 'Workflow execution started');
    }

    /**
     * GET /api/v1/workflows/executions - List workflow executions.
     */
    public function getExecutions()
    {
        $db = $this->getDb();
        $executions = [];
        if ($db !== null) {
            try {
                $executions = $db->table($db->prefixTable('atom_workflow_executions'), true)
                                 ->orderBy('id', 'DESC')
                                 ->get(30)
                                 ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($executions);
    }

    /**
     * GET /api/v1/workflows/executions/{id} - Execution detail.
     */
    public function getExecution($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Execution ID required', 400);
        }

        $db = $this->getDb();
        $execution = null;
        if ($db !== null) {
            try {
                $execution = $db->table($db->prefixTable('atom_workflow_executions'), true)
                                ->where('id', (int)$id)
                                ->get()
                                ->getRowArray();
            } catch (\Throwable $e) {}
        }

        if (!$execution) {
            return $this->respondError('Workflow execution not found', 404);
        }

        return $this->respondSuccess($execution);
    }

    /**
     * GET /api/v1/workflows/executions/{id}/stream - SSE live execution stream.
     */
    public function streamExecutionEvents($id = null)
    {
        response()->setHeader('Content-Type', 'text/event-stream');
        response()->setHeader('Cache-Control', 'no-cache');
        response()->setHeader('Connection', 'keep-alive');

        $db = $this->getDb();
        $events = [];
        if ($db !== null && !empty($id)) {
            try {
                $events = $db->table($db->prefixTable('atom_workflow_events'), true)
                             ->where('execution_id', (int)$id)
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
}
