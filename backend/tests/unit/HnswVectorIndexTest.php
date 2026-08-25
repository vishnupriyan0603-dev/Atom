<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Search\HnswVectorIndex;
use Atom\Security\SecretRedactor;

/**
 * Phase 44 — HnswVectorIndex unit tests (6 tests).
 */
class HnswVectorIndexTest extends TestCase
{
    private HnswVectorIndex $index;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = new HnswVectorIndex(8, 4, 16, 8, 'cosine', new SecretRedactor());
    }

    public function testInsertAndSearchExactMatch(): void
    {
        $v1 = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $v2 = [0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $v3 = [0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0];

        $this->index->insert('doc_a', $v1, ['tag' => 'alpha']);
        $this->index->insert('doc_b', $v2, ['tag' => 'beta']);
        $this->index->insert('doc_c', $v3, ['tag' => 'gamma']);

        $this->assertSame(3, $this->index->count());

        $results = $this->index->search($v1, 2);

        $this->assertNotEmpty($results);
        $this->assertSame('doc_a', $results[0]['id']);
        $this->assertGreaterThan(0.99, $results[0]['similarity']);
    }

    public function testMultiLayerHierarchicalGraphConstruction(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $v = array_pad([$i * 0.1, ($i % 2) * 0.5], 8, 0.0);
            $this->index->insert("item_{$i}", $v, ['index' => $i]);
        }

        $stats = $this->index->getStats();

        $this->assertSame(20, $stats['total_vectors']);
        $this->assertGreaterThanOrEqual(0, $stats['max_layer']);
        $this->assertNotEmpty($stats['layer_node_distribution']);
    }

    public function testSearchOnEmptyIndexReturnsEmptyArray(): void
    {
        $results = $this->index->search([1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0], 5);
        $this->assertEmpty($results);
    }

    public function testCosineSimilarityCalculationBounds(): void
    {
        $v1 = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $v2 = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $vOpposite = [-1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

        $simIdentical = $this->index->calculateSimilarity($v1, $v2);
        $simOpposite = $this->index->calculateSimilarity($v1, $vOpposite);

        $this->assertEqualsWithDelta(1.0, $simIdentical, 0.001);
        $this->assertEqualsWithDelta(0.0, $simOpposite, 0.001);
    }

    public function testClearResetsGraphState(): void
    {
        $this->index->insert('test_1', [0.5, 0.5, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]);
        $this->assertSame(1, $this->index->count());

        $this->index->clear();
        $this->assertSame(0, $this->index->count());
    }

    public function testTopKLimitEnforcement(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->index->insert("node_{$i}", [mt_rand(1, 100) / 100, 0.2, 0.1, 0.0, 0.0, 0.0, 0.0, 0.0]);
        }

        $results = $this->index->search([0.5, 0.2, 0.1, 0.0, 0.0, 0.0, 0.0, 0.0], 3);
        $this->assertCount(3, $results);
    }
}
