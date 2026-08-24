<?php

namespace Atom\Workflow;

class WorkflowValidator
{
    private array $supportedNodeTypes = [
        'START',
        'END',
        'AGENT',
        'LLM',
        'TOOL',
        'SKILL',
        'RAG',
        'MEMORY_GET',
        'MEMORY_SET',
        'CONDITION',
        'APPROVAL',
        'DELAY',
        'LOOP',
        'PARALLEL',
        'NOTIFICATION',
        'WEBHOOK',
        'TRANSFORM',
    ];

    public function validateGraph(array $definition): array
    {
        $nodes = $definition['nodes'] ?? [];
        if (empty($nodes) || !is_array($nodes)) {
            return ['valid' => false, 'error' => 'WORKFLOW_INVALID: Definition must contain a non-empty list of nodes'];
        }

        if (count($nodes) > 100) {
            return ['valid' => false, 'error' => 'WORKFLOW_INVALID: Exceeds maximum node count limit (100 nodes)'];
        }

        $hasStart = false;
        $hasEnd   = false;
        $keys     = [];

        foreach ($nodes as $node) {
            $type = strtoupper($node['type'] ?? '');
            $key  = $node['key'] ?? $node['id'] ?? '';

            if (!in_array($type, $this->supportedNodeTypes, true)) {
                return ['valid' => false, 'error' => "WORKFLOW_INVALID: Unsupported node type '{$type}'"];
            }

            if ($type === 'START') {
                $hasStart = true;
            }
            if ($type === 'END') {
                $hasEnd = true;
            }

            $keys[$key] = true;
        }

        if (!$hasStart) {
            return ['valid' => false, 'error' => 'WORKFLOW_INVALID: Graph missing mandatory START node'];
        }

        if (!$hasEnd) {
            return ['valid' => false, 'error' => 'WORKFLOW_INVALID: Graph missing mandatory END node'];
        }

        return ['valid' => true];
    }
}
