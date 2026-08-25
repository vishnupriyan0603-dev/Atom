<?php

namespace Atom\Search;

use Atom\Security\SecretRedactor;

/**
 * HnswVectorIndex — Phase 44
 * Edge-native Hierarchical Navigable Small World (HNSW) multi-layer vector index.
 * Enables O(log N) approximate nearest neighbor (ANN) vector search.
 */
class HnswVectorIndex
{
    private SecretRedactor $redactor;
    private int $dimension;
    private int $maxM;          // Maximum outgoing connections per node in layer > 0
    private int $maxM0;         // Maximum outgoing connections per node in layer 0
    private int $efConstruction;// Size of dynamic candidate list during construction
    private int $efSearch;      // Size of dynamic candidate list during search
    private float $mL;          // Normalization factor for level generation (1 / ln(M))
    private string $distanceMetric; // 'cosine' | 'euclidean' | 'dot'

    private array $nodes = [];      // [ id => [ 'vector' => [...], 'metadata' => [...], 'layer' => int ] ]
    private array $graphs = [];     // [ layer => [ nodeId => [ neighborId1, ... ] ] ]
    private ?string $entryPoint = null;
    private int $maxLayer = 0;

    public function __construct(
        int $dimension = 64,
        int $maxM = 16,
        int $efConstruction = 64,
        int $efSearch = 32,
        string $distanceMetric = 'cosine',
        ?SecretRedactor $redactor = null
    ) {
        $this->dimension = $dimension;
        $this->maxM = $maxM;
        $this->maxM0 = $maxM * 2;
        $this->efConstruction = $efConstruction;
        $this->efSearch = $efSearch;
        $this->mL = 1.0 / log((float)max(2, $maxM));
        $this->distanceMetric = strtolower($distanceMetric);
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->graphs[0] = [];
    }

    /**
     * Insert a vector into the HNSW index.
     */
    public function insert(string $id, array $vector, array $metadata = []): void
    {
        $normalizedVector = $this->normalizeVector($vector);
        $nodeLayer = $this->getRandomLevel();

        $this->nodes[$id] = [
            'id' => $id,
            'vector' => $normalizedVector,
            'metadata' => $metadata,
            'layer' => $nodeLayer,
        ];

        // If index is empty, set as entry point
        if ($this->entryPoint === null) {
            $this->entryPoint = $id;
            $this->maxLayer = $nodeLayer;
            for ($l = 0; $l <= $nodeLayer; $l++) {
                $this->graphs[$l][$id] = [];
            }
            return;
        }

        $currObj = $this->entryPoint;
        $maxL = $this->maxLayer;

        // 1. Search for closest entry point in higher layers down to nodeLayer + 1
        for ($l = $maxL; $l > $nodeLayer; $l--) {
            $currObj = $this->searchLayerClosest($currObj, $normalizedVector, $l);
        }

        // 2. Insert and connect node across layers from min(maxL, nodeLayer) down to 0
        $ep = [$currObj];
        for ($l = min($maxL, $nodeLayer); $l >= 0; $l--) {
            if (!isset($this->graphs[$l])) {
                $this->graphs[$l] = [];
            }
            $this->graphs[$l][$id] = [];

            // Find nearest neighbors in layer
            $candidates = $this->searchLayer($ep, $normalizedVector, $this->efConstruction, $l);
            $maxConnections = ($l === 0) ? $this->maxM0 : $this->maxM;
            $selectedNeighbors = $this->selectNeighbors($candidates, $maxConnections);

            // Connect bidirectionally
            foreach ($selectedNeighbors as $neighborId) {
                $this->graphs[$l][$id][] = $neighborId;
                $this->graphs[$l][$neighborId][] = $id;

                // Shrink neighbor's connections if exceeding maximum M
                if (count($this->graphs[$l][$neighborId]) > $maxConnections) {
                    $this->shrinkNeighbors($neighborId, $maxConnections, $l);
                }
            }

            $ep = $candidates;
        }

        // Update entry point if new node has higher layer
        if ($nodeLayer > $this->maxLayer) {
            $this->maxLayer = $nodeLayer;
            $this->entryPoint = $id;
        }
    }

    /**
     * Search top-K approximate nearest neighbors.
     */
    public function search(array $queryVector, int $topK = 5): array
    {
        if ($this->entryPoint === null || empty($this->nodes)) {
            return [];
        }

        $query = $this->normalizeVector($queryVector);
        $currObj = $this->entryPoint;

        // Traverse higher layers greedily
        for ($l = $this->maxLayer; $l > 0; $l--) {
            $currObj = $this->searchLayerClosest($currObj, $query, $l);
        }

        // Search layer 0 with beam width efSearch
        $candidates = $this->searchLayer([$currObj], $query, max($this->efSearch, $topK), 0);

        // Sort candidates by similarity descending
        usort($candidates, function ($a, $b) use ($query) {
            $simA = $this->calculateSimilarity($query, $this->nodes[$a]['vector']);
            $simB = $this->calculateSimilarity($query, $this->nodes[$b]['vector']);
            return $simB <=> $simA;
        });

        $results = [];
        $topCandidates = array_slice($candidates, 0, $topK);
        foreach ($topCandidates as $id) {
            $sim = $this->calculateSimilarity($query, $this->nodes[$id]['vector']);
            $results[] = [
                'id' => $id,
                'similarity' => round($sim, 4),
                'distance' => round(1.0 - $sim, 4),
                'metadata' => $this->nodes[$id]['metadata'] ?? [],
            ];
        }

        return $results;
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function getStats(): array
    {
        $layerCounts = [];
        for ($l = 0; $l <= $this->maxLayer; $l++) {
            $layerCounts[$l] = isset($this->graphs[$l]) ? count($this->graphs[$l]) : 0;
        }

        return [
            'total_vectors' => count($this->nodes),
            'dimension' => $this->dimension,
            'max_layer' => $this->maxLayer,
            'layer_node_distribution' => $layerCounts,
            'distance_metric' => $this->distanceMetric,
            'max_m' => $this->maxM,
            'ef_search' => $this->efSearch,
        ];
    }

    public function clear(): void
    {
        $this->nodes = [];
        $this->graphs = [0 => []];
        $this->entryPoint = null;
        $this->maxLayer = 0;
    }

    private function searchLayerClosest(string $entryPoint, array $query, int $layer): string
    {
        $curr = $entryPoint;
        $bestSim = $this->calculateSimilarity($query, $this->nodes[$curr]['vector']);
        $changed = true;

        while ($changed) {
            $changed = false;
            $neighbors = $this->graphs[$layer][$curr] ?? [];

            foreach ($neighbors as $neighbor) {
                if (!isset($this->nodes[$neighbor])) continue;
                $sim = $this->calculateSimilarity($query, $this->nodes[$neighbor]['vector']);
                if ($sim > $bestSim) {
                    $bestSim = $sim;
                    $curr = $neighbor;
                    $changed = true;
                }
            }
        }

        return $curr;
    }

    private function searchLayer(array $entryPoints, array $query, int $ef, int $layer): array
    {
        $visited = array_fill_keys($entryPoints, true);
        $candidates = $entryPoints;
        $w = $entryPoints;

        while (!empty($candidates)) {
            // Pick closest candidate in candidates pool
            usort($candidates, fn($a, $b) => $this->calculateSimilarity($query, $this->nodes[$b]['vector']) <=> $this->calculateSimilarity($query, $this->nodes[$a]['vector']));
            $c = array_shift($candidates);

            // Worst candidate in current result pool W
            usort($w, fn($a, $b) => $this->calculateSimilarity($query, $this->nodes[$a]['vector']) <=> $this->calculateSimilarity($query, $this->nodes[$b]['vector']));
            $worstSim = $this->calculateSimilarity($query, $this->nodes[$w[0]]['vector']);
            $cSim = $this->calculateSimilarity($query, $this->nodes[$c]['vector']);

            if ($cSim < $worstSim && count($w) >= $ef) {
                break;
            }

            foreach (($this->graphs[$layer][$c] ?? []) as $e) {
                if (!isset($visited[$e])) {
                    $visited[$e] = true;
                    $eSim = $this->calculateSimilarity($query, $this->nodes[$e]['vector']);

                    if ($eSim > $worstSim || count($w) < $ef) {
                        $candidates[] = $e;
                        $w[] = $e;
                        if (count($w) > $ef) {
                            usort($w, fn($a, $b) => $this->calculateSimilarity($query, $this->nodes[$a]['vector']) <=> $this->calculateSimilarity($query, $this->nodes[$b]['vector']));
                            array_shift($w); // Remove furthest
                        }
                    }
                }
            }
        }

        return $w;
    }

    private function selectNeighbors(array $candidates, int $maxM): array
    {
        return array_slice(array_unique($candidates), 0, $maxM);
    }

    private function shrinkNeighbors(string $nodeId, int $maxM, int $layer): void
    {
        if (isset($this->graphs[$layer][$nodeId])) {
            $this->graphs[$layer][$nodeId] = array_slice($this->graphs[$layer][$nodeId], 0, $maxM);
        }
    }

    public function calculateSimilarity(array $v1, array $v2): float
    {
        $dim = min(count($v1), count($v2));
        if ($dim === 0) return 0.0;

        $dot = 0.0;
        for ($i = 0; $i < $dim; $i++) {
            $dot += ($v1[$i] * $v2[$i]);
        }

        return max(0.0, min(1.0, (1.0 + $dot) / 2.0));
    }

    private function normalizeVector(array $v): array
    {
        $norm = 0.0;
        foreach ($v as $val) {
            $norm += $val * $val;
        }
        $norm = sqrt($norm);
        if ($norm <= 1e-12) {
            return array_pad($v, $this->dimension, 0.0);
        }

        $res = [];
        foreach ($v as $val) {
            $res[] = $val / $norm;
        }
        return array_pad($res, $this->dimension, 0.0);
    }

    private function getRandomLevel(): int
    {
        $r = mt_rand(1, 1000000) / 1000000.0;
        $level = (int)floor(-log($r) * $this->mL);
        return min($level, 6); // Cap max layer depth to 6
    }
}
