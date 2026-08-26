<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Ai\VectorSimilaritySearchEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 96 — VectorSimilaritySearchEngine unit tests (6 tests).
 */
class VectorSimilaritySearchEngineTest extends TestCase
{
    private VectorSimilaritySearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new VectorSimilaritySearchEngine(new SecretRedactor(), 4);
    }

    public function testCosineSimilarityIdenticalVectorReturnsOne(): void
    {
        $this->engine->upsertVector('vec_a', [1.0, 0.0, 0.0, 0.0]);
        $res = $this->engine->search([1.0, 0.0, 0.0, 0.0], 1, 'cosine');

        $this->assertTrue($res['success']);
        $this->assertSame('vec_a', $res['matches'][0]['vector_id']);
        $this->assertEqualsWithDelta(1.0, $res['matches'][0]['score'], 0.001);
    }

    public function testEuclideanDistanceIdenticalVectorReturnsZero(): void
    {
        $this->engine->upsertVector('vec_b', [0.5, 0.5, 0.5, 0.5]);
        $res = $this->engine->search([0.5, 0.5, 0.5, 0.5], 1, 'euclidean');

        $this->assertTrue($res['success']);
        $this->assertSame('vec_b', $res['matches'][0]['vector_id']);
        $this->assertEqualsWithDelta(0.0, $res['matches'][0]['score'], 0.001);
    }

    public function testDimensionMismatchFailsGracefully(): void
    {
        // Engine is configured for 4-dimensions, query with 3-dimensions should fail
        $res = $this->engine->search([1.0, 2.0, 3.0]);
        $this->assertFalse($res['success']);
        $this->assertEmpty($res['matches']);
    }

    public function testMetadataFilterExcludesNonMatchingRecords(): void
    {
        $this->engine->upsertVector('v_arch', [0.1, 0.2, 0.3, 0.4], ['domain' => 'arch']);
        $this->engine->upsertVector('v_sec', [0.1, 0.2, 0.3, 0.4], ['domain' => 'security']);

        $res = $this->engine->search([0.1, 0.2, 0.3, 0.4], 5, 'cosine', ['domain' => 'security']);

        $this->assertTrue($res['success']);
        $this->assertSame(1, $res['matches_found']);
        $this->assertSame('v_sec', $res['matches'][0]['vector_id']);
    }

    public function testTopKLimitRespected(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->engine->upsertVector("v_{$i}", [0.1 * $i, 0.2, 0.3, 0.4]);
        }

        $res = $this->engine->search([0.5, 0.2, 0.3, 0.4], 3);
        $this->assertSame(3, $res['matches_found']);
    }

    public function testGetIndexStatsReturnsMetrics(): void
    {
        $stats = $this->engine->getIndexStats();
        $this->assertSame(4, $stats['dimension']);
        $this->assertArrayHasKey('total_vectors', $stats);
        $this->assertArrayHasKey('memory_approx_kb', $stats);
    }
}
