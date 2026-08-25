<?php

namespace Atom\Search;

use Atom\Security\SecretRedactor;

/**
 * VectorEmbeddingPipeline — Phase 44
 * Edge-native neural vector embedding generator and HNSW vector space ingestion pipeline.
 */
class VectorEmbeddingPipeline
{
    private SecretRedactor $redactor;
    private HnswVectorIndex $index;
    private int $dimension;

    public function __construct(int $dimension = 64, ?HnswVectorIndex $index = null, ?SecretRedactor $redactor = null)
    {
        $this->dimension = $dimension;
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->index = $index ?? new HnswVectorIndex($dimension, 16, 64, 32, 'cosine', $this->redactor);
    }

    /**
     * Generate dense vector embedding from text, code snippet, or document.
     */
    public function generateEmbedding(string $text): array
    {
        $cleanText = $this->redactor->redact($text);
        $tokens = preg_split('/[\s,;:(){}\[\]<>+\-*\/="\'`.]+/', strtolower($cleanText), -1, PREG_SPLIT_NO_EMPTY);
        $vector = array_fill(0, $this->dimension, 0.0);

        if (empty($tokens)) {
            return $vector;
        }

        foreach ($tokens as $idx => $token) {
            $h = crc32($token);
            $bucket = abs($h) % $this->dimension;
            $subBucket = abs((int)($h >> 8)) % $this->dimension;

            // Frequency and position weighting
            $weight = 1.0 + (1.0 / (1.0 + $idx * 0.1));
            $vector[$bucket] += $weight;
            $vector[$subBucket] += ($weight * 0.5);
        }

        // L2 normalize
        $sumSq = 0.0;
        foreach ($vector as $v) {
            $sumSq += $v * $v;
        }
        $magnitude = sqrt($sumSq);

        if ($magnitude > 1e-12) {
            for ($i = 0; $i < $this->dimension; $i++) {
                $vector[$i] = round($vector[$i] / $magnitude, 6);
            }
        }

        return $vector;
    }

    /**
     * Ingest document or code chunk into the HNSW index.
     */
    public function ingest(string $id, string $content, array $metadata = []): array
    {
        $vector = $this->generateEmbedding($content);
        $cleanMetadata = [];
        foreach ($metadata as $k => $v) {
            $cleanMetadata[$k] = is_string($v) ? $this->redactor->redact($v) : $v;
        }

        $this->index->insert($id, $vector, $cleanMetadata);

        return [
            'success' => true,
            'id' => $id,
            'dimension' => $this->dimension,
            'indexed_terms_count' => count(explode(' ', $content)),
            'total_indexed' => $this->index->count(),
        ];
    }

    /**
     * Query the HNSW index with natural language text or code snippet.
     */
    public function query(string $queryText, int $topK = 5): array
    {
        $queryVector = $this->generateEmbedding($queryText);
        $results = $this->index->search($queryVector, $topK);

        return [
            'success' => true,
            'query' => $queryText,
            'results_count' => count($results),
            'results' => $results,
        ];
    }

    public function getIndex(): HnswVectorIndex
    {
        return $this->index;
    }

    public function getStats(): array
    {
        return array_merge($this->index->getStats(), [
            'pipeline_dimension' => $this->dimension,
        ]);
    }
}
