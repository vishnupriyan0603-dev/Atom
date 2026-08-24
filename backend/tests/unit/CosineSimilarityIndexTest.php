<?php

use PHPUnit\Framework\TestCase;
use Atom\Search\CosineSimilarityIndex;

/**
 * Phase 39 — CosineSimilarityIndex unit tests (5 tests).
 */
class CosineSimilarityIndexTest extends TestCase
{
    private CosineSimilarityIndex $index;

    protected function setUp(): void
    {
        $this->index = new CosineSimilarityIndex();
    }

    public function testAddDocumentIncrementsCount(): void
    {
        $this->assertSame(0, $this->index->count());

        $this->index->addDocument('doc_1', [1.0, 0.0, 0.0], ['name' => 'First']);
        $this->assertSame(1, $this->index->count());
    }

    public function testIdenticalVectorsHaveMaxSimilarity(): void
    {
        $sim = $this->index->cosineSimilarity([1.0, 0.0], [1.0, 0.0]);
        $this->assertEqualsWithDelta(1.0, $sim, 0.001);
    }

    public function testSearchReturnsRankedResults(): void
    {
        $this->index->addDocument('doc_a', [1.0, 0.0], ['tag' => 'A']);
        $this->index->addDocument('doc_b', [0.0, 1.0], ['tag' => 'B']);

        $query = [1.0, 0.0];
        $results = $this->index->search($query, 2);

        $this->assertCount(2, $results);
        $this->assertSame('doc_a', $results[0]['id']);
        $this->assertGreaterThan($results[1]['score'], $results[0]['score']);
    }

    public function testMinScoreFiltering(): void
    {
        $this->index->addDocument('doc_exact', [1.0, 0.0]);
        $this->index->addDocument('doc_opposite', [-1.0, 0.0]);

        $query = [1.0, 0.0];
        $results = $this->index->search($query, 5, 0.8); // Require >= 0.8

        $this->assertCount(1, $results);
        $this->assertSame('doc_exact', $results[0]['id']);
    }

    public function testEmptyIndexSearchReturnsEmptyArray(): void
    {
        $results = $this->index->search([1.0, 0.0], 5);
        $this->assertEmpty($results);
    }
}
