<?php

namespace Tests\Unit;

use Atom\Knowledge\NeuralDocumentChunker;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 — NeuralDocumentChunker unit tests (5 tests).
 */
class NeuralDocumentChunkerTest extends TestCase
{
    private NeuralDocumentChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new NeuralDocumentChunker(400, 50);
    }

    public function testChunkDocumentParsesMarkdownSections(): void
    {
        $markdown = "# Introduction\n\nATOM is an autonomous AI assistant.\n\n# Architecture\n\nIt features 41 phases.";
        $result = $this->chunker->chunkDocument($markdown, ['doc_title' => 'ATOM Guide']);

        $this->assertIsArray($result);
        $this->assertEquals('ATOM Guide', $result['document_title']);
        $this->assertGreaterThanOrEqual(2, $result['total_chunks']);
        $this->assertNotEmpty($result['chunks'][0]['header']);
    }

    public function testChunkDocumentHandlesEmptyContent(): void
    {
        $result = $this->chunker->chunkDocument('   ');
        $this->assertEmpty($result);
    }

    public function testEstimateTokensCalculatesReasonableBounds(): void
    {
        $text = "The quick brown fox jumps over the lazy dog and optimizes latency.";
        $tokens = $this->chunker->estimateTokens($text);
        $this->assertGreaterThan(5, $tokens);
        $this->assertLessThan(40, $tokens);
    }

    public function testCosineSimilarityMatchesIdenticalVectors(): void
    {
        $vecA = [0.5, 0.5, 0.5, 0.5];
        $vecB = [0.5, 0.5, 0.5, 0.5];

        $similarity = NeuralDocumentChunker::cosineSimilarity($vecA, $vecB);
        $this->assertEquals(1.0, $similarity);
    }

    public function testCosineSimilarityRejectsOrthogonalVectors(): void
    {
        $vecA = [1.0, 0.0, 0.0];
        $vecB = [0.0, 1.0, 0.0];

        $similarity = NeuralDocumentChunker::cosineSimilarity($vecA, $vecB);
        $this->assertEquals(0.0, $similarity);
    }
}
