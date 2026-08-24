<?php

namespace App\Controllers\Api;

use Atom\Search\CodeVectorEmbedder;
use Atom\Search\CosineSimilarityIndex;
use Atom\Search\CodeChunkSegmenter;
use Atom\Search\HybridSearchRanker;

/**
 * Autonomous Semantic Code Search & Vector Embedding API Controller — Phase 39
 *
 * Endpoints:
 * - POST /api/v1/search/query   — Search indexed code repository semantically
 * - POST /api/v1/search/index   — Embed & index code chunks
 * - POST /api/v1/search/embed   — Generate vector embedding for input text
 * - POST /api/v1/search/hybrid  — Run hybrid vector + lexical search
 * - GET  /api/v1/search/stats   — Index size and embedding dimension stats
 */
class SemanticSearch extends BaseApiController
{
    private static ?CodeVectorEmbedder $embedderInstance = null;
    private static ?CosineSimilarityIndex $indexInstance = null;
    private static ?CodeChunkSegmenter $segmenterInstance = null;
    private static ?HybridSearchRanker $rankerInstance = null;

    private function getEmbedder(): CodeVectorEmbedder
    {
        if (self::$embedderInstance === null) {
            self::$embedderInstance = new CodeVectorEmbedder(64);
        }
        return self::$embedderInstance;
    }

    private function getIndex(): CosineSimilarityIndex
    {
        if (self::$indexInstance === null) {
            self::$indexInstance = new CosineSimilarityIndex();
            // Pre-seed sample codebase index
            $embedder = $this->getEmbedder();
            $samples = [
                ['id' => 'auth_rbac', 'code' => 'class RolePermissionMatrix { public function hasPermission(string $role, string $perm): bool {} }', 'file' => 'src/Auth/RolePermissionMatrix.php'],
                ['id' => 'vault_crypto', 'code' => 'class ZeroKnowledgeVaultEngine { public function encrypt(string $plain, string $pass): array {} }', 'file' => 'src/Security/ZeroKnowledgeVaultEngine.php'],
                ['id' => 'webrtc_hub', 'code' => 'class WebRTCMeshSignalingHub { public function postOffer(string $from, string $to, string $sdp): array {} }', 'file' => 'src/Network/WebRTCMeshSignalingHub.php'],
                ['id' => 'forecaster_hw', 'code' => 'class HoltWintersForecaster { public function forecast(array $series, int $horizon): array {} }', 'file' => 'src/Analytics/HoltWintersForecaster.php'],
            ];
            foreach ($samples as $s) {
                $vec = $embedder->embed($s['code']);
                self::$indexInstance->addDocument($s['id'], $vec, ['file' => $s['file'], 'code' => $s['code']]);
            }
        }
        return self::$indexInstance;
    }

    private function getSegmenter(): CodeChunkSegmenter
    {
        if (self::$segmenterInstance === null) {
            self::$segmenterInstance = new CodeChunkSegmenter();
        }
        return self::$segmenterInstance;
    }

    private function getRanker(): HybridSearchRanker
    {
        if (self::$rankerInstance === null) {
            self::$rankerInstance = new HybridSearchRanker();
        }
        return self::$rankerInstance;
    }

    /**
     * POST /api/v1/search/query
     */
    public function query()
    {
        $json = $this->request->getJSON(true) ?? [];
        $queryText = $json['query'] ?? 'how to encrypt secret vault data with password';
        $topK = (int)($json['top_k'] ?? 5);

        $queryVec = $this->getEmbedder()->embed($queryText);
        $results = $this->getIndex()->search($queryVec, $topK);

        return $this->respondSuccess([
            'query'   => $queryText,
            'top_k'   => $topK,
            'results' => $results,
        ], 'Semantic code search completed');
    }

    /**
     * POST /api/v1/search/index
     */
    public function index()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? 'function authenticateUser($user, $token) { return true; }';
        $filePath = $json['file'] ?? 'src/Auth/UserAuth.php';

        $chunks = $this->getSegmenter()->segment($code, $filePath);
        $indexedCount = 0;

        foreach ($chunks as $chunk) {
            $chunkId = $filePath . '#' . $chunk['symbol'] . ':' . $chunk['start_line'];
            $vec = $this->getEmbedder()->embed($chunk['content']);
            $this->getIndex()->addDocument($chunkId, $vec, $chunk);
            $indexedCount++;
        }

        return $this->respondSuccess([
            'indexed_chunks' => $indexedCount,
            'total_in_index' => $this->getIndex()->count(),
        ], 'Code successfully chunked, embedded, and indexed');
    }

    /**
     * POST /api/v1/search/embed
     */
    public function embed()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = $json['text'] ?? 'class DatabaseConnectionPool {}';

        $vector = $this->getEmbedder()->embed($text);
        return $this->respondSuccess([
            'dimension' => count($vector),
            'vector'    => $vector,
        ], 'Vector embedding computed');
    }

    /**
     * POST /api/v1/search/hybrid
     */
    public function hybrid()
    {
        $json = $this->request->getJSON(true) ?? [];
        $query = $json['query'] ?? 'forecast future metrics time series';

        $queryVec = $this->getEmbedder()->embed($query);
        $vecResults = $this->getIndex()->search($queryVec, 5);

        // Simple lexical match fallback
        $lexResults = [];
        $tokens = explode(' ', strtolower($query));
        foreach ($vecResults as $res) {
            $codeStr = strtolower($res['metadata']['code'] ?? '');
            $matchCount = 0;
            foreach ($tokens as $t) {
                if (strlen($t) > 2 && strpos($codeStr, $t) !== false) {
                    $matchCount++;
                }
            }
            $lexResults[] = [
                'id'       => $res['id'],
                'score'    => $matchCount,
                'metadata' => $res['metadata'],
            ];
        }

        $fused = $this->getRanker()->fuse($vecResults, $lexResults);
        return $this->respondSuccess(['fused_results' => $fused], 'Hybrid RRF search completed');
    }

    /**
     * GET /api/v1/search/stats
     */
    public function stats()
    {
        return $this->respondSuccess([
            'indexed_documents' => $this->getIndex()->count(),
            'vector_dimension'  => 64,
            'similarity_metric' => 'COSINE_SIMILARITY',
            'ranking_engine'    => 'RECIPROCAL_RANK_FUSION_RRF',
        ], 'Semantic search engine statistics');
    }
}
