<?php

namespace Atom\Refactoring;

/**
 * Dependency Graph Analyzer — Phase 35
 *
 * Computes architectural coupling (Afferent/Efferent), Instability Index,
 * and detects circular dependency cycles across modular components.
 */
class DependencyGraphAnalyzer
{
    /**
     * Analyzes a dependency graph.
     *
     * @param array $graph Associative array where key is Class/Module and value is array of dependencies.
     * @return array Coupling metrics, instability indices, and circular cycles.
     */
    public function analyze(array $graph): array
    {
        if (empty($graph)) {
            return [
                'nodes'          => [],
                'total_nodes'    => 0,
                'circular_cycles'=> [],
                'has_cycles'     => false,
            ];
        }

        $allNodes = array_unique(array_merge(array_keys($graph), ...array_values($graph)));
        $afferent = array_fill_keys($allNodes, 0);
        $efferent = array_fill_keys($allNodes, 0);

        foreach ($graph as $source => $targets) {
            $efferent[$source] = count($targets);
            foreach ($targets as $t) {
                if (isset($afferent[$t])) {
                    $afferent[$t]++;
                } else {
                    $afferent[$t] = 1;
                }
            }
        }

        $nodeMetrics = [];
        foreach ($allNodes as $node) {
            $ca = $afferent[$node] ?? 0;
            $ce = $efferent[$node] ?? 0;
            $total = $ca + $ce;
            $instability = $total > 0 ? round($ce / $total, 3) : 0.0;

            $nodeMetrics[$node] = [
                'afferent_coupling' => $ca, // Incoming
                'efferent_coupling' => $ce, // Outgoing
                'instability_index' => $instability, // I = Ce / (Ca + Ce)
                'stability_class'   => $instability <= 0.3 ? 'STABLE' : ($instability >= 0.7 ? 'VOLATILE' : 'BALANCED'),
            ];
        }

        $cycles = $this->detectCircularCycles($graph);

        return [
            'nodes'           => $nodeMetrics,
            'total_nodes'     => count($allNodes),
            'circular_cycles' => $cycles,
            'has_cycles'      => !empty($cycles),
        ];
    }

    private function detectCircularCycles(array $graph): array
    {
        $cycles = [];
        $visited = [];
        $recStack = [];
        $path = [];

        foreach (array_keys($graph) as $node) {
            if (!isset($visited[$node])) {
                $this->dfsFindCycles($node, $graph, $visited, $recStack, $path, $cycles);
            }
        }

        return $cycles;
    }

    private function dfsFindCycles(string $node, array $graph, array &$visited, array &$recStack, array &$path, array &$cycles): void
    {
        $visited[$node] = true;
        $recStack[$node] = true;
        $path[] = $node;

        $neighbors = $graph[$node] ?? [];
        foreach ($neighbors as $neighbor) {
            if (!isset($visited[$neighbor])) {
                $this->dfsFindCycles($neighbor, $graph, $visited, $recStack, $path, $cycles);
            } elseif (!empty($recStack[$neighbor])) {
                // Cycle detected! Extract cycle from path
                $startIdx = array_search($neighbor, $path, true);
                if ($startIdx !== false) {
                    $cycle = array_slice($path, $startIdx);
                    $cycle[] = $neighbor;
                    $cycleKey = implode(' -> ', $cycle);
                    $cycles[$cycleKey] = $cycle;
                }
            }
        }

        array_pop($path);
        $recStack[$node] = false;
    }
}
