<?php

namespace App\Controllers\Api;

use Atom\Ai\VectorSimilaritySearchEngine;

/**
 * VectorSearch API Controller — Phase 96
 */
class VectorSearch extends BaseApiController
{
    private static ?VectorSimilaritySearchEngine $engine = null;

    private function getEngine(): VectorSimilaritySearchEngine
    {
        if (self::$engine === null) {
            self::$engine = new VectorSimilaritySearchEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/ai/vector/search
     */
    public function search()
    {
        $json = $this->request->getJSON(true) ?? [];
        $queryVector = $json['query_vector'] ?? [0.14, 0.46, 0.86, 0.22, 0.90, 0.16, 0.66, 0.32];
        $topK = (int)($json['top_k'] ?? 3);
        $metric = $json['metric'] ?? 'cosine';
        $filter = $json['filter'] ?? [];

        $engine = $this->getEngine();
        $res = $engine->search($queryVector, $topK, $metric, $filter);

        return $this->respondSuccess($res, 'Vector similarity search completed');
    }

    /**
     * POST /api/ai/vector/upsert
     */
    public function upsert()
    {
        $json = $this->request->getJSON(true) ?? [];
        $vectorId = $json['vector_id'] ?? ('vec_' . bin2hex(random_bytes(4)));
        $vector = $json['vector'] ?? [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8];
        $metadata = $json['metadata'] ?? ['category' => 'custom'];

        $engine = $this->getEngine();
        $ok = $engine->upsertVector($vectorId, $vector, $metadata);

        return $this->respondSuccess(['upserted' => $ok, 'vector_id' => $vectorId], 'Vector upserted to index');
    }

    /**
     * GET /api/ai/vector/stats
     */
    public function stats()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getIndexStats(), 'Vector index stats');
    }
}
