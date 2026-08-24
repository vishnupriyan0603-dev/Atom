<?php

namespace Atom\Planning;

/**
 * Plan Visualizer — Phase 30
 *
 * Renders hierarchical task DAGs and Graph-of-Thought search trees
 * into Mermaid diagram syntax, nested JSON structures, and ASCII terminal trees.
 */
class PlanVisualizer
{
    /**
     * Converts a plan or thought tree into Mermaid flowchart syntax.
     */
    public function toMermaid(array $tree): string
    {
        $nodes = $tree['nodes'] ?? [];
        if (empty($nodes)) {
            return "graph TD\n    empty([Empty Plan])";
        }

        $lines = ["graph TD"];

        // Style classes
        $lines[] = "    classDef selected fill:#059669,stroke:#10b981,stroke-width:2px,color:#ffffff;";
        $lines[] = "    classDef evaluated fill:#2563eb,stroke:#3b82f6,stroke-width:1px,color:#ffffff;";
        $lines[] = "    classDef exploring fill:#0891b2,stroke:#06b6d4,stroke-width:1px,color:#ffffff;";
        $lines[] = "    classDef pruned fill:#374151,stroke:#4b5563,stroke-width:1px,color:#9ca3af,stroke-dasharray: 5 5;";
        $lines[] = "    classDef failed fill:#dc2626,stroke:#ef4444,stroke-width:2px,color:#ffffff;";
        $lines[] = "    classDef default fill:#1e293b,stroke:#334155,stroke-width:1px,color:#e2e8f0;";

        // Define nodes
        foreach ($nodes as $id => $node) {
            $title = $node['title'] ?? $node['thought'] ?? $id;
            // Escape quotes, braces, parentheses, and remove non-redaction brackets
            $cleanTitle = str_replace(['"', '{', '}', '(', ')'], '', $title);
            $cleanTitle = preg_replace('/\[(?!REDACTED)([^\]]*)\]/i', '$1', $cleanTitle);
            $cleanTitle = mb_substr($cleanTitle, 0, 120);
            $status = $node['status'] ?? 'pending';
            $conf = isset($node['confidence']) ? ' (' . round($node['confidence'] * 100) . '%)' : '';

            $lines[] = "    {$id}[\"{$cleanTitle}{$conf}\"]:::{$status}";
        }

        // Define edges
        foreach ($nodes as $id => $node) {
            $children = $node['children'] ?? [];
            foreach ($children as $childId) {
                if (isset($nodes[$childId])) {
                    $lines[] = "    {$id} --> {$childId}";
                }
            }
            if (isset($node['dependencies']) && is_array($node['dependencies'])) {
                foreach ($node['dependencies'] as $depId) {
                    if (isset($nodes[$depId]) && !in_array($id, $nodes[$depId]['children'] ?? [], true)) {
                        $lines[] = "    {$depId} -.-> {$id}";
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Converts a flat node dictionary into a nested hierarchical JSON tree.
     */
    public function toJsonHierarchy(array $tree): array
    {
        $nodes = $tree['nodes'] ?? [];
        $rootId = $tree['root_id'] ?? array_key_first($nodes);

        if ($rootId === null || !isset($nodes[$rootId])) {
            return [];
        }

        $buildNode = function ($nodeId) use (&$buildNode, $nodes) {
            $node = $nodes[$nodeId];
            $item = [
                'id'         => $node['id'],
                'title'      => $node['title'] ?? $node['thought'] ?? $node['id'],
                'type'       => $node['type'] ?? 'node',
                'status'     => $node['status'] ?? 'pending',
                'confidence' => $node['confidence'] ?? 1.0,
                'children'   => [],
            ];

            foreach ($node['children'] ?? [] as $childId) {
                if (isset($nodes[$childId])) {
                    $item['children'][] = $buildNode($childId);
                }
            }

            return $item;
        };

        return $buildNode($rootId);
    }

    /**
     * Renders a text-based ASCII tree for CLI output.
     */
    public function toAsciiTree(array $tree): string
    {
        $nodes = $tree['nodes'] ?? [];
        $rootId = $tree['root_id'] ?? array_key_first($nodes);

        if ($rootId === null || !isset($nodes[$rootId])) {
            return "(Empty Plan Tree)";
        }

        $output = [];

        $render = function ($nodeId, $prefix = '') use (&$render, &$output, $nodes) {
            $node = $nodes[$nodeId];
            $title = $node['title'] ?? $node['thought'] ?? $node['id'];
            $status = $node['status'] ?? 'pending';
            $conf = isset($node['confidence']) ? sprintf(' [%0.0f%%]', $node['confidence'] * 100) : '';

            $statusIcon = match ($status) {
                'selected'  => '●',
                'evaluated' => '◆',
                'exploring' => '○',
                'pruned'    => '⨯',
                'failed'    => '✗',
                default     => '▪',
            };

            $output[] = "{$prefix}{$statusIcon} {$nodeId}: {$title}{$conf} ({$status})";

            $children = $node['children'] ?? [];
            $count = count($children);
            foreach ($children as $idx => $childId) {
                if (isset($nodes[$childId])) {
                    $isLast = ($idx === $count - 1);
                    $subPrefix = $prefix . ($isLast ? '    ' : '│   ');
                    $render($childId, $subPrefix);
                }
            }
        };

        $render($rootId);
        return implode("\n", $output);
    }
}
