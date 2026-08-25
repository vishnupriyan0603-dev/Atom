<?php

namespace App\Controllers\Api;

use Atom\Search\VectorEmbeddingPipeline;
use Atom\Search\HnswVectorIndex;

/**
 * VectorSearch API Controller — Phase 44
 */
class VectorSearch extends BaseApiController
{
    private static ?VectorEmbeddingPipeline $pipeline = null;

    private function getPipeline(): VectorEmbeddingPipeline
    {
        if (self::$pipeline === null) {
            self::$pipeline = new VectorEmbeddingPipeline(64);
            // Pre-seed with key core modules
            self::$pipeline->ingest('core_brain', 'Atom Brain Core multi-agent reasoning, thought graph, GoT planning, and cognitive dispatch.', ['category' => 'core']);
            self::$pipeline->ingest('core_voice', 'Tamil Ben 10 reference voice engine with 245Hz pitch, formant equalizer, and prosodic Tamil phonemes.', ['category' => 'voice']);
            self::$pipeline->ingest('core_security', 'Zero Knowledge Vault with AES-256-GCM encryption, secret redactor, and PBKDF2 key derivation.', ['category' => 'security']);
            self::$pipeline->ingest('core_refactor', 'Dependency graph DAG analyzer, Martin coupling metrics, circular cycle detection and DIP decoupling.', ['category' => 'refactoring']);
            self::$pipeline->ingest('core_vision', 'Neural code OCR extraction, UI layout synthesizer for Bootstrap 5, Tailwind and Flutter, and SQL schema generator.', ['category' => 'vision']);
        }

        return self::$pipeline;
    }

    /**
     * GET /api/vector/index/stats
     */
    public function stats()
    {
        $pipeline = $this->getPipeline();
        return $this->respondSuccess($pipeline->getStats(), 'HNSW vector index statistics');
    }

    /**
     * POST /api/vector/index/insert
     */
    public function insert()
    {
        $json = $this->request->getJSON(true) ?? [];
        $id = $json['id'] ?? ('doc_' . uniqid());
        $content = $json['content'] ?? $json['text'] ?? '';
        $metadata = $json['metadata'] ?? [];

        if (empty(trim($content))) {
            return $this->respondError('Document content is required for embedding', 400);
        }

        $pipeline = $this->getPipeline();
        $result = $pipeline->ingest($id, $content, $metadata);

        return $this->respondSuccess($result, 'Vector embedded and inserted into HNSW index');
    }

    /**
     * POST /api/vector/search
     */
    public function search()
    {
        $json = $this->request->getJSON(true) ?? [];
        $query = $json['query'] ?? $json['text'] ?? '';
        $topK = (int)($json['top_k'] ?? 5);

        if (empty(trim($query))) {
            return $this->respondError('Query text is required', 400);
        }

        $pipeline = $this->getPipeline();
        $result = $pipeline->query($query, $topK);

        return $this->respondSuccess($result, 'HNSW vector search completed');
    }

    /**
     * POST /api/vector/embed
     */
    public function embed()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = $json['text'] ?? $json['content'] ?? '';

        if (empty(trim($text))) {
            return $this->respondError('Text content is required for embedding generation', 400);
        }

        $pipeline = $this->getPipeline();
        $vector = $pipeline->generateEmbedding($text);

        return $this->respondSuccess([
            'dimension' => count($vector),
            'vector' => $vector,
        ], 'Vector embedding generated');
    }

    /**
     * DELETE /api/vector/index/clear
     */
    public function clear()
    {
        $pipeline = $this->getPipeline();
        $pipeline->getIndex()->clear();

        return $this->respondSuccess([
            'cleared' => true,
            'total_vectors' => 0,
        ], 'HNSW vector index cleared');
    }
}
