<?php

namespace Tests\Unit;

use Atom\Swarm\SwarmOrchestrationHub;
use Atom\Voice\AudioDspFilterEngine;
use Atom\Knowledge\NeuralDocumentChunker;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 — Phase41SecurityPassTest security & safety tests (5 tests).
 */
class Phase41SecurityPassTest extends TestCase
{
    public function testSwarmConsensusRejectsEmptyClaims(): void
    {
        $hub = new SwarmOrchestrationHub();
        $this->expectException(\InvalidArgumentException::class);
        $hub->evaluateConsensus([]);
    }

    public function testSwarmArtifactRedactsPotentialSecrets(): void
    {
        $hub = new SwarmOrchestrationHub();
        $contributions = [
            ['role' => 'coder', 'output' => 'API Token: sk_test_secret_1234567890abcdef']
        ];
        $artifact = $hub->synthesizeArtifact('Security Redaction Pass', $contributions);
        $this->assertIsArray($artifact);
        $this->assertNotEmpty($artifact['integrity_hash']);
    }

    public function testAudioDspNoiseGatePreventsBufferOverflow(): void
    {
        $engine = new AudioDspFilterEngine();
        $engine->setNoiseGate(true, -10.0);
        $largeSamples = array_fill(0, 1000, 0.00001);
        $filtered = $engine->applyNoiseGate($largeSamples);

        $this->assertCount(1000, $filtered);
        $this->assertEquals(0.0, $filtered[0]);
    }

    public function testDocumentChunkerHandlesMassiveInputGracefully(): void
    {
        $chunker = new NeuralDocumentChunker(500, 50);
        $massive = str_repeat("# Section\nThis is safe content without memory leaks.\n", 50);
        $result = $chunker->chunkDocument($massive);

        $this->assertIsArray($result);
        $this->assertGreaterThan(5, $result['total_chunks']);
        $this->assertLessThan(200, $result['total_chunks']);
    }

    public function testCosineSimilaritySafelyHandlesDimensionMismatch(): void
    {
        $vecA = [1.0, 2.0, 3.0, 4.0];
        $vecB = [1.0, 2.0];

        $similarity = NeuralDocumentChunker::cosineSimilarity($vecA, $vecB);
        $this->assertIsFloat($similarity);
        $this->assertGreaterThanOrEqual(0.0, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }
}
