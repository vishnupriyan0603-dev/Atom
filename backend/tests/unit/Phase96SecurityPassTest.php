<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Ai\VectorSimilaritySearchEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 96 — Phase96SecurityPassTest security & safety tests (5 tests).
 */
class Phase96SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInVectorId(): void
    {
        $engine = new VectorSimilaritySearchEngine($this->redactor, 4);
        $engine->upsertVector('vec_sk-1122334455667788990011223344_user', [0.1, 0.2, 0.3, 0.4]);

        $res = $engine->search([0.1, 0.2, 0.3, 0.4], 1);
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['matches'][0]['vector_id']);
    }

    public function testHighThroughputVectorSearch(): void
    {
        $engine = new VectorSimilaritySearchEngine($this->redactor, 8);
        for ($i = 0; $i < 100; $i++) {
            $engine->upsertVector("vec_{$i}", [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8]);
        }

        $query = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8];
        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->search($query, 5);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testZeroVectorNormDoesNotThrowDivisionByZero(): void
    {
        $engine = new VectorSimilaritySearchEngine($this->redactor, 4);
        $engine->upsertVector('zero_vec', [0.0, 0.0, 0.0, 0.0]);

        $res = $engine->search([0.0, 0.0, 0.0, 0.0], 1, 'cosine');
        $this->assertTrue($res['success']);
        $this->assertSame(0.0, $res['matches'][0]['score']);
    }

    public function testNonStandardTypesHandledSafely(): void
    {
        $engine = new VectorSimilaritySearchEngine($this->redactor, 4);
        $this->assertFalse($engine->upsertVector('bad_vec', ['not_enough_elements']));
    }

    public function testNoDangerousEvalOrShellExecutionInAiSubsystem(): void
    {
        $files = [
            'src/Ai/VectorSimilaritySearchEngine.php',
            'src/Ai/ApiCostGovernorEngine.php',
            'src/Ai/FederatedLearningEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
