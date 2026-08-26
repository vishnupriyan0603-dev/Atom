<?php

namespace Atom\Ai;

use Atom\Security\SecretRedactor;

/**
 * VectorSimilaritySearchEngine — Phase 96
 * High-dimensional vector similarity index, Top-K nearest neighbor search (Cosine, Euclidean, Dot Product), and metadata filter.
 */
class VectorSimilaritySearchEngine
{
    private SecretRedactor $redactor;
    private int $dimension = 8; // Default sample dimension
    private array $vectors = []; // [ vector_id => [ 'vector' => [], 'metadata' => [] ] ]

    public function __construct(?SecretRedactor $redactor = null, int $dimension = 8)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->dimension = max(2, min(1536, $dimension));
        $this->seedSampleVectors();
    }

    /**
     * Ingest or update a vector in the index.
     */
    public function upsertVector(string $vectorId, array $vector, array $metadata = []): bool
    {
        $cleanId = trim(strtolower($this->redactor->redact($vectorId)));

        if (count($vector) !== $this->dimension) {
            return false;
        }

        // Normalize vector to float values
        $floatVector = array_map(fn($v) => (float)$v, $vector);

        $this->vectors[$cleanId] = [
            'vector_id' => $cleanId,
            'vector' => $floatVector,
            'metadata' => $metadata,
            'norm' => $this->calculateL2Norm($floatVector),
            'updated_at' => microtime(true),
        ];

        return true;
    }

    /**
     * Search for Top-K most similar vectors to a query embedding.
     *
     * @param array $queryVector Target query embedding
     * @param int $topK Number of nearest neighbors to return
     * @param string $metric 'cosine', 'euclidean', 'dot_product'
     * @param array $filterMetadata Optional metadata filter
     * @return array Top-K ranked matches
     */
    public function search(array $queryVector, int $topK = 3, string $metric = 'cosine', array $filterMetadata = []): array
    {
        if (count($queryVector) !== $this->dimension || empty($this->vectors)) {
            return [
                'success' => false,
                'error' => 'Query vector dimension mismatch or empty index',
                'matches' => [],
            ];
        }

        $cleanMetric = strtolower(trim($metric));
        $floatQuery = array_map(fn($v) => (float)$v, $queryVector);
        $queryNorm = $this->calculateL2Norm($floatQuery);

        $scores = [];

        foreach ($this->vectors as $id => $item) {
            // Apply optional metadata filters
            if (!empty($filterMetadata)) {
                $match = true;
                foreach ($filterMetadata as $k => $v) {
                    if (!isset($item['metadata'][$k]) || $item['metadata'][$k] !== $v) {
                        $match = false;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
            }

            $score = 0.0;
            if ($cleanMetric === 'euclidean') {
                $score = $this->calculateEuclideanDistance($floatQuery, $item['vector']);
            } elseif ($cleanMetric === 'dot_product') {
                $score = $this->calculateDotProduct($floatQuery, $item['vector']);
            } else {
                // Default Cosine Similarity
                $score = $this->calculateCosineSimilarity($floatQuery, $item['vector'], $queryNorm, $item['norm']);
            }

            $scores[] = [
                'vector_id' => $id,
                'score' => round($score, 4),
                'metadata' => $item['metadata'],
            ];
        }

        // Sort results: Descending for cosine/dot_product, Ascending for euclidean
        if ($cleanMetric === 'euclidean') {
            usort($scores, fn($a, $b) => $a['score'] <=> $b['score']);
        } else {
            usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        $topMatches = array_slice($scores, 0, max(1, $topK));

        return [
            'success' => true,
            'metric' => $cleanMetric,
            'top_k' => $topK,
            'total_indexed' => count($this->vectors),
            'matches_found' => count($topMatches),
            'matches' => $topMatches,
        ];
    }

    private function calculateCosineSimilarity(array $u, array $v, float $normU, float $normV): float
    {
        if ($normU <= 1e-9 || $normV <= 1e-9) {
            return 0.0;
        }

        $dot = $this->calculateDotProduct($u, $v);
        return $dot / ($normU * $normV);
    }

    private function calculateDotProduct(array $u, array $v): float
    {
        $sum = 0.0;
        $len = count($u);
        for ($i = 0; $i < $len; $i++) {
            $sum += $u[$i] * $v[$i];
        }
        return $sum;
    }

    private function calculateEuclideanDistance(array $u, array $v): float
    {
        $sum = 0.0;
        $len = count($u);
        for ($i = 0; $i < $len; $i++) {
            $diff = $u[$i] - $v[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    private function calculateL2Norm(array $u): float
    {
        $sum = 0.0;
        foreach ($u as $x) {
            $sum += $x * $x;
        }
        return sqrt($sum);
    }

    public function getIndexStats(): array
    {
        return [
            'dimension' => $this->dimension,
            'total_vectors' => count($this->vectors),
            'memory_approx_kb' => round((count($this->vectors) * $this->dimension * 8) / 1024, 2),
        ];
    }

    private function seedSampleVectors(): void
    {
        $this->upsertVector('doc_neural_arch', [0.12, 0.45, 0.88, 0.23, 0.91, 0.15, 0.67, 0.34], ['category' => 'architecture', 'author' => 'alice']);
        $this->upsertVector('doc_quantum_crypt', [0.89, 0.12, 0.34, 0.95, 0.22, 0.87, 0.11, 0.76], ['category' => 'security', 'author' => 'bob']);
        $this->upsertVector('doc_binaural_dsp', [0.15, 0.48, 0.85, 0.21, 0.89, 0.18, 0.65, 0.31], ['category' => 'voice', 'author' => 'carol']);
    }
}
