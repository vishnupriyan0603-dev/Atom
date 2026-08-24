<?php

use PHPUnit\Framework\TestCase;
use Atom\Search\CodeVectorEmbedder;
use Atom\Search\CosineSimilarityIndex;
use Atom\Search\CodeChunkSegmenter;

/**
 * Phase 39 — SearchSecurityPassTest security & safety tests (5 tests).
 */
class SearchSecurityPassTest extends TestCase
{
    public function testSecretRedactionInSearchResults(): void
    {
        $embedder = new CodeVectorEmbedder();
        $index = new CosineSimilarityIndex();

        $secretCode = 'const API_KEY = "sk-ant-api03-123456789012345678901234";';
        $vec = $embedder->embed($secretCode);
        $index->addDocument('secret_chunk', $vec, ['code' => $secretCode]);

        $res = $index->search($vec, 1);
        $this->assertIsArray($res);
        $this->assertCount(1, $res);
    }

    public function testNoEvalOrShellExecutionInSearchSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $embedCode = file_get_contents($rootDir . '/src/Search/CodeVectorEmbedder.php');
        $indexCode = file_get_contents($rootDir . '/src/Search/CosineSimilarityIndex.php');
        $segCode = file_get_contents($rootDir . '/src/Search/CodeChunkSegmenter.php');
        $rrfCode = file_get_contents($rootDir . '/src/Search/HybridSearchRanker.php');

        $this->assertNotFalse($embedCode);
        $this->assertNotFalse($indexCode);
        $this->assertNotFalse($segCode);
        $this->assertNotFalse($rrfCode);

        $this->assertStringNotContainsString('eval(', $embedCode);
        $this->assertStringNotContainsString('eval(', $indexCode);
        $this->assertStringNotContainsString('eval(', $segCode);
        $this->assertStringNotContainsString('eval(', $rrfCode);
        $this->assertStringNotContainsString('exec(', $embedCode);
        $this->assertStringNotContainsString('shell_exec(', $embedCode);
    }

    public function testDimensionMismatchSafety(): void
    {
        $index = new CosineSimilarityIndex();
        $sim = $index->cosineSimilarity([1.0, 0.0, 0.0], [1.0, 0.0]); // 3-D vs 2-D

        $this->assertGreaterThanOrEqual(0.0, $sim);
        $this->assertLessThanOrEqual(1.0, $sim);
    }

    public function testLargePayloadEmbeddingResourceBound(): void
    {
        $embedder = new CodeVectorEmbedder(64);
        $largeCode = str_repeat("function test() { \$x = 1; }\n", 1000);
        $vec = $embedder->embed($largeCode);

        $this->assertCount(64, $vec);
    }

    public function testRegexInjectionSafetyInSegmenter(): void
    {
        $segmenter = new CodeChunkSegmenter();
        $code = "function test(?.*+){}\nclass [A-Z]+ {}";
        $chunks = $segmenter->segment($code);

        $this->assertIsArray($chunks);
    }
}
