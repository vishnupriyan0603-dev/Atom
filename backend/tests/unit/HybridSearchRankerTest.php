<?php

use PHPUnit\Framework\TestCase;
use Atom\Search\HybridSearchRanker;

/**
 * Phase 39 — HybridSearchRanker unit tests (5 tests).
 */
class HybridSearchRankerTest extends TestCase
{
    private HybridSearchRanker $ranker;

    protected function setUp(): void
    {
        $this->ranker = new HybridSearchRanker(60, 0.6, 0.4);
    }

    public function testFuseCombinesVectorAndLexicalLists(): void
    {
        $vec = [
            ['id' => 'doc_1', 'score' => 0.95, 'metadata' => ['title' => 'Doc 1']],
            ['id' => 'doc_2', 'score' => 0.80, 'metadata' => ['title' => 'Doc 2']],
        ];
        $lex = [
            ['id' => 'doc_2', 'score' => 10, 'metadata' => ['title' => 'Doc 2']],
            ['id' => 'doc_3', 'score' => 5, 'metadata' => ['title' => 'Doc 3']],
        ];

        $fused = $this->ranker->fuse($vec, $lex);

        $this->assertCount(3, $fused);
        $this->assertArrayHasKey('rrf_score', $fused[0]);
    }

    public function testItemPresentInBothListsReceivesHighestRRFScore(): void
    {
        $vec = [
            ['id' => 'doc_shared', 'score' => 0.9],
            ['id' => 'doc_vec_only', 'score' => 0.8],
        ];
        $lex = [
            ['id' => 'doc_shared', 'score' => 5],
            ['id' => 'doc_lex_only', 'score' => 3],
        ];

        $fused = $this->ranker->fuse($vec, $lex);

        $this->assertSame('doc_shared', $fused[0]['id']);
    }

    public function testEmptyListsReturnEmptyResults(): void
    {
        $fused = $this->ranker->fuse([], []);
        $this->assertEmpty($fused);
    }

    public function testPreservesMetadataAcrossFusion(): void
    {
        $vec = [['id' => 'doc_x', 'score' => 0.9, 'metadata' => ['file' => 'test.php']]];
        $fused = $this->ranker->fuse($vec, []);

        $this->assertSame('test.php', $fused[0]['metadata']['file']);
    }

    public function testCustomRrfKWeighting(): void
    {
        $rankerCustom = new HybridSearchRanker(10, 0.5, 0.5);
        $res = $rankerCustom->fuse([['id' => 'd1', 'score' => 0.9]], []);

        $this->assertGreaterThan(0, $res[0]['rrf_score']);
    }
}
