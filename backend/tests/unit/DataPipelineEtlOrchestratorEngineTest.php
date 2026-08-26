<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DataPipelineEtlOrchestratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 93 — DataPipelineEtlOrchestratorEngine unit tests (6 tests).
 */
class DataPipelineEtlOrchestratorEngineTest extends TestCase
{
    private DataPipelineEtlOrchestratorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DataPipelineEtlOrchestratorEngine(new SecretRedactor());
    }

    public function testExecuteUserActivitySanitizerFiltersInactive(): void
    {
        $records = [
            ['id' => 1, 'email' => '  ALICE@CORP.IO  ', 'active' => true],
            ['id' => 2, 'email' => 'BOB@CORP.IO', 'active' => false],
            ['id' => 3, 'email' => 'Carol@Corp.Io', 'active' => true],
        ];

        $res = $this->engine->executePipeline($records, 'user_activity_sanitizer');

        $this->assertTrue($res['success']);
        $this->assertSame(3, $res['ingested_count']);
        $this->assertSame(2, $res['emitted_count']);
        $this->assertSame(1, $res['filtered_count']);
        $this->assertSame('alice@corp.io', $res['records'][0]['email']);
        $this->assertArrayHasKey('_etl_timestamp', $res['records'][0]);
    }

    public function testFinancialTransactionEnricherFiltersBelowThreshold(): void
    {
        $records = [
            ['tx_id' => 'tx_1', 'amount' => 150.456, 'currency' => 'usd'],
            ['tx_id' => 'tx_2', 'amount' => 4.50, 'currency' => 'usd'], // Below $10 threshold
            ['tx_id' => 'tx_3', 'amount' => 25.0, 'currency' => 'eur'],
        ];

        $res = $this->engine->executePipeline($records, 'financial_transaction_enricher');

        $this->assertTrue($res['success']);
        $this->assertSame(2, $res['emitted_count']);
        $this->assertSame(1, $res['filtered_count']);
        $this->assertSame(150.46, $res['records'][0]['amount']);
        $this->assertSame('USD', $res['records'][0]['currency_normalized']);
    }

    public function testQuarantinesMalformedNonArrayRecords(): void
    {
        $records = [
            ['id' => 1, 'active' => true],
            'corrupted_string_entry_instead_of_array',
            ['id' => 2, 'active' => true],
        ];

        $res = $this->engine->executePipeline($records, 'user_activity_sanitizer');

        $this->assertTrue($res['success']);
        $this->assertSame(3, $res['ingested_count']);
        $this->assertSame(2, $res['emitted_count']);
        $this->assertSame(1, $res['quarantined_count']);
    }

    public function testEmptyRecordsListFailsGracefully(): void
    {
        $res = $this->engine->executePipeline([]);
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['ingested_count']);
    }

    public function testGetAvailablePipelinesReturnsTemplates(): void
    {
        $pipelines = $this->engine->getAvailablePipelines();

        $this->assertArrayHasKey('user_activity_sanitizer', $pipelines);
        $this->assertArrayHasKey('financial_transaction_enricher', $pipelines);
    }

    public function testEtlChecksumPreservesDataIntegrity(): void
    {
        $records = [['id' => 1, 'email' => 'test@test.com', 'active' => true]];
        $res = $this->engine->executePipeline($records);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['records'][0]['_etl_checksum']);
        $this->assertSame(16, strlen($res['records'][0]['_etl_checksum']));
    }
}
