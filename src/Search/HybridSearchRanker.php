<?php

namespace Atom\Search;

/**
 * Hybrid Search Ranker — Phase 39
 *
 * Combines dense semantic vector similarity search with sparse lexical
 * keyword matching using Reciprocal Rank Fusion (RRF).
 */
class HybridSearchRanker
{
    private int $rrfK;
    private float $vectorWeight;
    private float $lexicalWeight;

    public function __construct(int $rrfK = 60, float $vectorWeight = 0.6, float $lexicalWeight = 0.4)
    {
        $this->rrfK = max(1, $rrfK);
        $this->vectorWeight = $vectorWeight;
        $this->lexicalWeight = $lexicalWeight;
    }

    /**
     * Fuses vector search rankings and lexical search rankings.
     *
     * @param array $vectorResults Ordered list of items with 'id' and 'score'.
     * @param array $lexicalResults Ordered list of items with 'id' and 'score'.
     * @return array Fused and sorted items with composite RRF score.
     */
    public function fuse(array $vectorResults, array $lexicalResults): array
    {
        $scores = [];
        $metadata = [];

        // 1. Process Vector Ranks
        foreach ($vectorResults as $rank => $item) {
            $id = $item['id'];
            $rrf = $this->vectorWeight / ($this->rrfK + ($rank + 1));
            $scores[$id] = ($scores[$id] ?? 0.0) + $rrf;
            $metadata[$id] = $item['metadata'] ?? [];
        }

        // 2. Process Lexical Ranks
        foreach ($lexicalResults as $rank => $item) {
            $id = $item['id'];
            $rrf = $this->lexicalWeight / ($this->rrfK + ($rank + 1));
            $scores[$id] = ($scores[$id] ?? 0.0) + $rrf;
            if (!isset($metadata[$id])) {
                $metadata[$id] = $item['metadata'] ?? [];
            }
        }

        $fused = [];
        foreach ($scores as $id => $score) {
            $fused[] = [
                'id'        => $id,
                'rrf_score' => round($score, 6),
                'metadata'  => $metadata[$id],
            ];
        }

        usort($fused, fn($a, $b) => $b['rrf_score'] <=> $a['rrf_score']);
        return $fused;
    }
}
