<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DynamicSchemaMigrationEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 98 — DynamicSchemaMigrationEngine unit tests (6 tests).
 */
class DynamicSchemaMigrationEngineTest extends TestCase
{
    private DynamicSchemaMigrationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DynamicSchemaMigrationEngine(new SecretRedactor());
    }

    public function testPlanAddColumnUsesInplaceAlgorithm(): void
    {
        $plan = $this->engine->planMigration('orders', 'add_column', [
            'column_name' => 'tracking_code',
            'column_type' => 'VARCHAR(64)',
            'nullable' => true,
        ]);

        $this->assertTrue($plan['success']);
        $this->assertSame('ONLINE_INSTANT_ADD', $plan['strategy']);
        $this->assertStringContainsString('ALGORITHM=INPLACE', $plan['forward_ddl']);
        $this->assertStringContainsString('LOCK=NONE', $plan['forward_ddl']);
        $this->assertNotEmpty($plan['reverse_ddl']);
    }

    public function testPlanAddIndexUsesConcurrentInplaceStrategy(): void
    {
        $plan = $this->engine->planMigration('payments', 'add_index', [
            'index_name' => 'idx_payments_status',
            'columns' => ['status', 'created_at'],
        ]);

        $this->assertTrue($plan['success']);
        $this->assertSame('CONCURRENT_INDEX_BUILD', $plan['strategy']);
        $this->assertStringContainsString('CREATE INDEX', $plan['forward_ddl']);
        $this->assertStringContainsString('idx_payments_status', $plan['forward_ddl']);
    }

    public function testModifyColumnUsesShadowTableExpansion(): void
    {
        $plan = $this->engine->planMigration('large_events', 'modify_column', [
            'column_name' => 'event_id',
            'new_type' => 'BIGINT UNSIGNED',
        ]);

        $this->assertTrue($plan['success']);
        $this->assertSame('SHADOW_TABLE_EXPANSION_SWAP', $plan['strategy']);
        $this->assertSame('WARNING', $plan['risk_level']);
        $this->assertStringContainsString('_shadow_large_events', $plan['forward_ddl']);
    }

    public function testEmptyTableNameFailsGracefully(): void
    {
        $plan = $this->engine->planMigration('', 'add_column');
        $this->assertFalse($plan['success']);
        $this->assertEmpty($plan['forward_ddl']);
    }

    public function testExecuteMigrationRecordsInAuditLedger(): void
    {
        $plan = $this->engine->planMigration('notifications', 'add_column', [
            'column_name' => 'read_at',
            'column_type' => 'DATETIME',
        ]);

        $res = $this->engine->executeMigration($plan);

        $this->assertTrue($res['success']);
        $this->assertSame('MIGRATION_APPLIED_SUCCESSFULLY', $res['status']);

        $history = $this->engine->getMigrationHistory();
        $this->assertGreaterThanOrEqual(1, count($history));
        $this->assertSame('notifications', $history[0]['table']);
    }

    public function testChecksumIntegrityPreserved(): void
    {
        $plan = $this->engine->planMigration('accounts', 'add_column', ['column_name' => 'tier']);

        $this->assertNotEmpty($plan['checksum']);
        $this->assertSame(64, strlen($plan['checksum']));
    }
}
