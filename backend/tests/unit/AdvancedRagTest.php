<?php

use PHPUnit\Framework\TestCase;
use Atom\Knowledge\AdvancedRagPipeline;
use Atom\Knowledge\AdvancedHybridRag;
use Atom\Knowledge\KnowledgeSearch;
use Atom\Database\Connection;

class AdvancedRagTest extends TestCase
{
    public function testDocumentChunkingAndHashingPipeline()
    {
        $pipeline = new AdvancedRagPipeline();
        $sampleText = "CodeIgniter 4 is a powerful PHP framework with a very small footprint. It provides built-in RESTful routing, database migrations, and security tools. ATOM AI Platform integrates CodeIgniter with local and cloud LLM model providers seamlessly.";

        $processed = $pipeline->processDocument('CI4 Manual', $sampleText, 15);

        $this->assertEquals('CI4 Manual', $processed['title']);
        $this->assertNotEmpty($processed['doc_hash']);
        $this->assertGreaterThan(0, $processed['total_chunks']);
        $this->assertCount($processed['total_chunks'], $processed['chunks']);

        $firstChunk = $processed['chunks'][0];
        $this->assertArrayHasKey('content', $firstChunk);
        $this->assertArrayHasKey('embedding', $firstChunk);
        $this->assertCount(8, $firstChunk['embedding']);
    }

    public function testVectorCosineSimilarityCalculation()
    {
        $conn = new Connection('localhost', 'atom_assistant', 'root', '', '3306');
        $search = new KnowledgeSearch($conn);

        $vecA = [1.0, 0.0, 0.0, 0.0];
        $vecB = [1.0, 0.0, 0.0, 0.0];
        $vecC = [0.0, 1.0, 0.0, 0.0];

        $simIdentical = $search->cosineSimilarity($vecA, $vecB);
        $simOrthogonal = $search->cosineSimilarity($vecA, $vecC);

        $this->assertEquals(1.0, round($simIdentical, 2));
        $this->assertEquals(0.0, round($simOrthogonal, 2));
    }

    public function testHybridRagCitationFormatting()
    {
        $conn = new Connection('localhost', 'atom_assistant', 'root', '', '3306');
        $search = new KnowledgeSearch($conn);
        $hybrid = new AdvancedHybridRag($search);

        $results = $hybrid->searchAdvanced('CodeIgniter REST API', 3, 0.0);
        $this->assertIsArray($results);
    }
}
