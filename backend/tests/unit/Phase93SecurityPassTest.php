<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DataPipelineEtlOrchestratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 93 — Phase93SecurityPassTest security & safety tests (5 tests).
 */
class Phase93SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInEtlPipelinePayload(): void
    {
        $engine = new DataPipelineEtlOrchestratorEngine($this->redactor);
        $records = [
            ['id' => 1, 'email' => 'sk-1122334455667788990011223344_user@corp.io', 'active' => true],
        ];

        $res = $engine->executePipeline($records);
        $this->assertTrue($res['success']);
        $emittedJson = json_encode($res['records']);

        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $emittedJson);
    }

    public function testHighThroughputEtlBatchProcessing(): void
    {
        $engine = new DataPipelineEtlOrchestratorEngine($this->redactor);
        $batch = [];
        for ($i = 0; $i < 500; $i++) {
            $batch[] = ['id' => $i, 'email' => "user_{$i}@DOMAIN.COM", 'active' => true, 'amount' => $i * 1.5];
        }

        $startTime = microtime(true);
        $res = $engine->executePipeline($batch, 'financial_transaction_enricher');
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertSame(500, $res['ingested_count']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testNonStandardTypesHandledSafelyWithoutCrash(): void
    {
        $engine = new DataPipelineEtlOrchestratorEngine($this->redactor);
        $records = [
            ['id' => 1, 'email' => 12345, 'active' => true, 'amount' => 'NaN'],
        ];

        $res = $engine->executePipeline($records);
        $this->assertTrue($res['success']);
        $this->assertSame(1, $res['emitted_count']);
    }

    public function testCurrencyNormalizedToUppercase(): void
    {
        $engine = new DataPipelineEtlOrchestratorEngine($this->redactor);
        $records = [
            ['amount' => 50.0, 'currency' => 'inr'],
        ];

        $res = $engine->executePipeline($records, 'financial_transaction_enricher');
        $this->assertSame('INR', $res['records'][0]['currency_normalized']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/DataPipelineEtlOrchestratorEngine.php',
            'src/Database/ConsistentHashShardRouterEngine.php',
            'src/Database/ConnectionPoolGovernorEngine.php',
            'src/Database/SqlQueryExplainerEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
