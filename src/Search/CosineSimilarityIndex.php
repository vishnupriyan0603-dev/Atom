<?php

namespace Atom\Search;

/**
 * Cosine Similarity Index — Phase 39
 *
 * In-memory vector database and similarity search engine for repository embeddings.
 */
class CosineSimilarityIndex
{
    private array $documents = [];

    /**
     * Adds an embedded document / code chunk to the index.
     */
    public function addDocument(string $id, array $vector, array $metadata = []): void
    {
        $this->documents[$id] = [
            'id'       => $id,
            'vector'   => $vector,
            'metadata' => $metadata,
        ];
    }

    /**
     * Searches index for top-k closest vectors to query vector.
     */
    public function search(array $queryVector, int $topK = 5, float $minScore = 0.0): array
    {
        $scores = [];

        foreach ($this->documents as $id => $doc) {
            $sim = $this->cosineSimilarity($queryVector, $doc['vector']);
            if ($sim >= $minScore) {
                $scores[] = [
                    'id'       => $id,
                    'score'    => round($sim, 4),
                    'metadata' => $doc['metadata'],
                ];
            }
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scores, 0, $topK);
    }

    /**
     * Computes Cosine Similarity between two L2-normalized vectors.
     */
    public function cosineSimilarity(array $v1, array $v2): float
    {
        $dim = min(count($v1), count($v2));
        if ($dim === 0) {
            return 0.0;
        }

        $dot = 0.0;
        for ($i = 0; $i < $dim; $i++) {
            $dot += ($v1[$i] * $v2[$i]);
        }

        // Clip bounds to [0.0, 1.0] for non-negative similarity ranking
        return max(0.0, min(1.0, (1.0 + $dot) / 2.0));
    }

    public function count(): int
    {
        return count($this->documents);
    }
}
