<?php

namespace Atom\Knowledge;

class AdvancedHybridRag
{
    private KnowledgeSearch $searchEngine;
    private AdvancedRagPipeline $pipeline;

    public function __construct(KnowledgeSearch $searchEngine, ?AdvancedRagPipeline $pipeline = null)
    {
        $this->searchEngine = $searchEngine;
        $this->pipeline = $pipeline ?? new AdvancedRagPipeline();
    }

    /**
     * Executes Advanced Hybrid Retrieval (Cosine Similarity + BM25 keyword matching + reranking + citations).
     */
    public function searchAdvanced(
        string $query,
        int $topK = 5,
        float $similarityThreshold = 0.20,
        ?string $category = null
    ): array {
        $queryVec = $this->pipeline->generateEmbedding($query);
        $rawResults = $this->searchEngine->search($query, 20);

        $scoredResults = [];
        foreach ($rawResults as $row) {
            $chunkText = $row['chunk_text'] ?? '';
            $chunkVec = $this->pipeline->generateEmbedding($chunkText);

            $cosineSim = $this->searchEngine->cosineSimilarity($queryVec, $chunkVec);
            $bm25Score = (float)($row['relevance_score'] ?? 0.5);

            // Dynamic Hybrid Score: 60% Cosine Similarity + 40% Keyword/BM25
            $hybridScore = round(($cosineSim * 0.6) + ($bm25Score * 0.4), 3);

            if ($hybridScore >= $similarityThreshold) {
                $title = $row['title'] ?? 'Document';
                $pageNum = $row['page_number'] ?? 1;

                $scoredResults[] = [
                    'title'           => $title,
                    'filename'        => $row['filename'] ?? 'doc.pdf',
                    'page_number'     => $pageNum,
                    'chunk_text'      => $chunkText,
                    'cosine_score'    => round($cosineSim, 3),
                    'bm25_score'      => round($bm25Score, 3),
                    'relevance_score' => $hybridScore,
                    'citation'        => "[{$title} | Page {$pageNum} | Score: {$hybridScore}]",
                ];
            }
        }

        // Rerank results by hybrid score descending
        usort($scoredResults, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return array_slice($scoredResults, 0, $topK);
    }
}
