<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Search\VectorEmbeddingPipeline;
use Atom\Security\SecretRedactor;

/**
 * Phase 44 — VectorEmbeddingPipeline unit tests (6 tests).
 */
class VectorEmbeddingPipelineTest extends TestCase
{
    private VectorEmbeddingPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new VectorEmbeddingPipeline(32, null, new SecretRedactor());
    }

    public function testGenerateEmbeddingVectorDimensionAndNormalization(): void
    {
        $vector = $this->pipeline->generateEmbedding('Autonomous neural vector search pipeline');

        $this->assertCount(32, $vector);

        // Verify L2 unit norm
        $norm = 0.0;
        foreach ($vector as $v) {
            $norm += $v * $v;
        }
        $this->assertEqualsWithDelta(1.0, sqrt($norm), 0.01);
    }

    public function testDeterministicEmbeddingsForIdenticalText(): void
    {
        $v1 = $this->pipeline->generateEmbedding('Machine Learning Model Gateway');
        $v2 = $this->pipeline->generateEmbedding('Machine Learning Model Gateway');

        $this->assertSame($v1, $v2);
    }

    public function testIngestAndQuerySemanticRetrieval(): void
    {
        $this->pipeline->ingest('doc_voice', 'Tamil Ben 10 voice studio synthesizer with prosodic phonemes');
        $this->pipeline->ingest('doc_vault', 'Zero knowledge encrypted security vault with AES-256-GCM');

        $queryResult = $this->pipeline->query('voice synthesizer phonemes', 1);

        $this->assertTrue($queryResult['success']);
        $this->assertNotEmpty($queryResult['results']);
        $this->assertSame('doc_voice', $queryResult['results'][0]['id']);
    }

    public function testEmptyTextGeneratesZeroVector(): void
    {
        $vector = $this->pipeline->generateEmbedding('    ');

        $this->assertCount(32, $vector);
        $this->assertSame(0.0, array_sum($vector));
    }

    public function testPipelineStatsRetrieval(): void
    {
        $this->pipeline->ingest('k1', 'Knowledge item 1');
        $stats = $this->pipeline->getStats();

        $this->assertSame(32, $stats['pipeline_dimension']);
        $this->assertSame(1, $stats['total_vectors']);
    }

    public function testMetadataPreservationAcrossIngestion(): void
    {
        $this->pipeline->ingest('meta_doc', 'Some code sample', ['author' => 'ATOM', 'priority' => 'high']);
        $results = $this->pipeline->query('Some code sample', 1);

        $this->assertNotEmpty($results['results']);
        $meta = $results['results'][0]['metadata'];
        $this->assertSame('ATOM', $meta['author']);
        $this->assertSame('high', $meta['priority']);
    }
}
