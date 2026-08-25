<?php

namespace Atom\Refactoring;

use Atom\Security\SecretRedactor;

/**
 * DependencyGraphEngine — Phase 43
 * Advanced AST codebase dependency network analyzer, Martin metrics computation, and circular cycle resolution.
 */
class DependencyGraphEngine
{
    private SecretRedactor $redactor;
    private DependencyGraphAnalyzer $analyzer;

    public function __construct(?SecretRedactor $redactor = null, ?DependencyGraphAnalyzer $analyzer = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->analyzer = $analyzer ?? new DependencyGraphAnalyzer();
    }

    /**
     * Build and analyze full dependency graph from an array of file/class maps or code sources.
     *
     * @param array $sourceMap Associative array where key is Class/File and value is array of dependencies, or array of source codes
     * @return array
     */
    public function analyzeGraph(array $sourceMap): array
    {
        $graph = [];
        $nodeDetails = [];

        foreach ($sourceMap as $key => $value) {
            if (is_array($value)) {
                $graph[$key] = array_values(array_unique($value));
                $nodeDetails[$key] = [
                    'id' => $key,
                    'label' => basename(str_replace('\\', '/', $key)),
                    'namespace' => str_contains($key, '\\') ? dirname(str_replace('\\', '/', $key)) : 'Global',
                    'type' => 'class',
                ];
            } elseif (is_string($value)) {
                // Parse code string for imports
                $parsed = $this->parseDependenciesFromCode($value, (string)$key);
                $graph[$parsed['class_name']] = $parsed['dependencies'];
                $nodeDetails[$parsed['class_name']] = [
                    'id' => $parsed['class_name'],
                    'label' => basename(str_replace('\\', '/', $parsed['class_name'])),
                    'namespace' => $parsed['namespace'],
                    'type' => $parsed['is_interface'] ? 'interface' : ($parsed['is_abstract'] ? 'abstract' : 'class'),
                ];
            }
        }

        if (empty($graph)) {
            $graph = $this->getDefaultArchitectureGraph();
            foreach (array_keys($graph) as $node) {
                $nodeDetails[$node] = [
                    'id' => $node,
                    'label' => basename(str_replace('\\', '/', $node)),
                    'namespace' => 'Atom',
                    'type' => 'class',
                ];
            }
        }

        // Run coupling and cycle analysis
        $analysis = $this->analyzer->analyze($graph);

        // Compute advanced Martin metrics: Abstractness (A) and Distance from Main Sequence (D)
        $enrichedNodes = [];
        $totalClasses = count($analysis['nodes']);
        $abstractCount = count(array_filter($nodeDetails, fn($n) => in_array($n['type'] ?? '', ['interface', 'abstract'])));
        $abstractness = $totalClasses > 0 ? round($abstractCount / $totalClasses, 3) : 0.0;

        foreach ($analysis['nodes'] as $nodeName => $metrics) {
            $instability = $metrics['instability_index'];
            // Normalized distance D = |A + I - 1| / sqrt(2) or |A + I - 1|
            $distance = round(abs($abstractness + $instability - 1.0), 3);

            $enrichedNodes[$nodeName] = array_merge($metrics, [
                'meta' => $nodeDetails[$nodeName] ?? ['id' => $nodeName, 'label' => $nodeName, 'type' => 'class'],
                'abstractness' => $abstractness,
                'distance_from_main_sequence' => $distance,
                'risk_level' => $distance > 0.7 ? 'HIGH_RISK' : ($distance > 0.4 ? 'MEDIUM_RISK' : 'OPTIMAL'),
            ]);
        }

        // Format edges for D3 / Graph rendering
        $edges = [];
        foreach ($graph as $source => $targets) {
            foreach ($targets as $target) {
                $edges[] = [
                    'source' => $source,
                    'target' => $target,
                    'weight' => 1,
                ];
            }
        }

        // Compute Topological Sort (order of compilation / execution)
        $topologicalOrder = $this->computeTopologicalOrder($graph);

        // Generate Mermaid representation
        $mermaid = $this->generateMermaidGraph($graph, $analysis['circular_cycles']);

        return [
            'success' => true,
            'total_nodes' => count($enrichedNodes),
            'total_edges' => count($edges),
            'abstractness_index' => $abstractness,
            'nodes' => $enrichedNodes,
            'edges' => $edges,
            'topological_order' => $topologicalOrder,
            'circular_cycles' => array_values($analysis['circular_cycles']),
            'has_cycles' => $analysis['has_cycles'],
            'mermaid_diagram' => $mermaid,
        ];
    }

    /**
     * Parse class name, namespace, and dependencies from raw PHP/C#/JS code.
     */
    public function parseDependenciesFromCode(string $code, string $fallbackName = 'UnknownClass'): array
    {
        $className = $fallbackName;
        $namespace = 'Global';
        $isInterface = (bool)preg_match('/\binterface\s+([A-Za-z0-9_]+)/', $code);
        $isAbstract = (bool)preg_match('/\babstract\s+class\s+([A-Za-z0-9_]+)/', $code);

        if (preg_match('/\bnamespace\s+([^;]+);/', $code, $m)) {
            $namespace = trim($m[1]);
        }

        if (preg_match('/\b(?:class|interface|trait)\s+([A-Za-z0-9_]+)/', $code, $m)) {
            $className = $namespace !== 'Global' ? "{$namespace}\\{$m[1]}" : $m[1];
        }

        $dependencies = [];

        // Match use / import statements
        if (preg_match_all('/\buse\s+([^;]+);/', $code, $matches)) {
            foreach ($matches[1] as $import) {
                $trimmed = trim($import);
                if (!str_contains($trimmed, 'function ') && !str_contains($trimmed, 'const ')) {
                    $dependencies[] = $trimmed;
                }
            }
        }

        // Match instantiation: new ClassName()
        if (preg_match_all('/\bnew\s+([A-Za-z0-9_\\\\]+)\s*\(/', $code, $matches)) {
            foreach ($matches[1] as $inst) {
                $trimmed = trim($inst);
                if (!in_array(strtolower($trimmed), ['self', 'static', 'stdclass', 'exception', 'invalidargumentexception', 'runtimeexception'])) {
                    $dependencies[] = $trimmed;
                }
            }
        }

        return [
            'class_name' => $className,
            'namespace' => $namespace,
            'is_interface' => $isInterface,
            'is_abstract' => $isAbstract,
            'dependencies' => array_values(array_unique($dependencies)),
        ];
    }

    /**
     * Compute Topological Sort (Kahn's algorithm) to detect execution tiers.
     */
    public function computeTopologicalOrder(array $graph): array
    {
        $inDegree = [];
        $allNodes = array_unique(array_merge(array_keys($graph), ...array_values($graph)));
        foreach ($allNodes as $n) {
            $inDegree[$n] = 0;
        }

        foreach ($graph as $u => $neighbors) {
            foreach ($neighbors as $v) {
                $inDegree[$v] = ($inDegree[$v] ?? 0) + 1;
            }
        }

        $queue = [];
        foreach ($inDegree as $node => $deg) {
            if ($deg === 0) {
                $queue[] = $node;
            }
        }

        $order = [];
        while (!empty($queue)) {
            $u = array_shift($queue);
            $order[] = $u;

            foreach (($graph[$u] ?? []) as $v) {
                $inDegree[$v]--;
                if ($inDegree[$v] === 0) {
                    $queue[] = $v;
                }
            }
        }

        return $order;
    }

    private function generateMermaidGraph(array $graph, array $cycles): string
    {
        $mermaid = "graph TD\n";

        foreach ($graph as $source => $targets) {
            $srcClean = preg_replace('/[^a-zA-Z0-9_]/', '_', $source);
            $srcLabel = basename(str_replace('\\', '/', $source));

            foreach ($targets as $target) {
                $tgtClean = preg_replace('/[^a-zA-Z0-9_]/', '_', $target);
                $tgtLabel = basename(str_replace('\\', '/', $target));

                $isCycleEdge = false;
                foreach ($cycles as $cycle) {
                    for ($i = 0; $i < count($cycle) - 1; $i++) {
                        if ($cycle[$i] === $source && $cycle[$i + 1] === $target) {
                            $isCycleEdge = true;
                            break 2;
                        }
                    }
                }

                if ($isCycleEdge) {
                    $mermaid .= "    {$srcClean}[\"{$srcLabel}\"] -.->|CIRCULAR| {$tgtClean}[\"{$tgtLabel}\"]\n";
                } else {
                    $mermaid .= "    {$srcClean}[\"{$srcLabel}\"] --> {$tgtClean}[\"{$tgtLabel}\"]\n";
                }
            }
        }

        return $mermaid;
    }

    private function getDefaultArchitectureGraph(): array
    {
        return [
            'Atom\\Brain\\BrainCore' => ['Atom\\Memory\\MemoryManager', 'Atom\\Routing\\RoutingEngine', 'Atom\\Security\\SecretRedactor'],
            'Atom\\Routing\\RoutingEngine' => ['Atom\\Telemetry\\TelemetryManager', 'Atom\\ModelGateway\\GatewayDriver'],
            'Atom\\Swarm\\SwarmOrchestrationHub' => ['Atom\\Brain\\BrainCore', 'Atom\\Voice\\AudioDspFilterEngine'],
            'Atom\\Workflow\\WorkflowExecutor' => ['Atom\\Telemetry\\TelemetryManager', 'Atom\\Security\\SecretRedactor'],
            'Atom\\Voice\\TamilReferenceVoiceEngine' => ['Atom\\Voice\\TamilPhonemeEngine'],
        ];
    }
}
