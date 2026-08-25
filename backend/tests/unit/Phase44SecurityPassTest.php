<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Search\VectorEmbeddingPipeline;
use Atom\Search\HnswVectorIndex;
use Atom\Security\SecretRedactor;

/**
 * Phase 44 — Phase44SecurityPassTest security & safety tests (5 tests).
 */
class Phase44SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInEmbeddingSourceText(): void
    {
        $pipeline = new VectorEmbeddingPipeline(32, null, $this->redactor);
        $secretText = "api_key = 'sk-123456789012345678901234567890'";
        $sanitizedText = $this->redactor->redact($secretText);

        $vec1 = $pipeline->generateEmbedding($secretText);
        $vec2 = $pipeline->generateEmbedding($sanitizedText);

        // Vector of raw input matches sanitized input because secrets were redacted before token hashing
        $this->assertSame($vec1, $vec2);
    }

    public function testSecretRedactionInMetadataStorage(): void
    {
        $pipeline = new VectorEmbeddingPipeline(32, null, $this->redactor);
        $pipeline->ingest('secure_doc', 'Public telemetry payload', [
            'token' => "api_key = 'sk-9988776655443322110011223344'",
        ]);

        $res = $pipeline->query('Public telemetry', 1);

        $this->assertNotEmpty($res['results']);
        $meta = $res['results'][0]['metadata'];
        $this->assertStringNotContainsString('sk-9988776655443322110011223344', $meta['token']);
    }

    public function testZeroNormProtectionAgainstDivisionByZero(): void
    {
        $index = new HnswVectorIndex(8, 4, 16, 8, 'cosine', $this->redactor);
        $allZeros = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

        // Inserting and searching zero vector must not throw E_WARNING or division by zero
        $index->insert('zero_node', $allZeros);
        $results = $index->search($allZeros, 1);

        $this->assertCount(1, $results);
    }

    public function testVeryLargeVectorQuerySafety(): void
    {
        $index = new HnswVectorIndex(64, 16, 64, 32, 'cosine', $this->redactor);
        $largeVector = array_fill(0, 100, 0.5); // Oversized dimension

        $index->insert('oversized_doc', $largeVector);
        $results = $index->search($largeVector, 1);

        $this->assertCount(1, $results);
    }

    public function testNoDangerousEvalOrShellExecutionInSearchSubsystem(): void
    {
        $files = [
            'src/Search/HnswVectorIndex.php',
            'src/Search/VectorEmbeddingPipeline.php',
            'src/Search/CosineSimilarityIndex.php',
            'src/Search/CodeVectorEmbedder.php',
            'src/Search/CodeChunkSegmenter.php',
            'src/Search/HybridSearchRanker.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
