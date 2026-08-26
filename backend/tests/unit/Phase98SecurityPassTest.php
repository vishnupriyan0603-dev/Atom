<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DynamicSchemaMigrationEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 98 — Phase98SecurityPassTest security & safety tests (5 tests).
 */
class Phase98SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSqlIdentifierSanitizationPreventsInjection(): void
    {
        $engine = new DynamicSchemaMigrationEngine($this->redactor);
        $plan = $engine->planMigration("users`; DROP TABLE users; --", 'add_column', [
            'column_name' => "col'; DROP DATABASE db; --",
        ]);

        $this->assertTrue($plan['success']);
        // Special characters (semicolons, quotes, dashes) stripped from table and column identifiers
        $this->assertStringNotContainsString('DROP DATABASE', $plan['forward_ddl']);
        $this->assertStringNotContainsString('DROP TABLE', $plan['forward_ddl']);
    }

    public function testHighThroughputMigrationPlanning(): void
    {
        $engine = new DynamicSchemaMigrationEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->planMigration("table_{$i}", 'add_column', ['column_name' => "col_{$i}"]);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testMalformedPlanExecutionFailsCleanly(): void
    {
        $engine = new DynamicSchemaMigrationEngine($this->redactor);
        $res = $engine->executeMigration(['invalid_key' => 123]);

        $this->assertFalse($res['success']);
        $this->assertSame('Invalid migration plan envelope', $res['error']);
    }

    public function testRollbackDdlAlwaysGenerated(): void
    {
        $engine = new DynamicSchemaMigrationEngine($this->redactor);
        $plan = $engine->planMigration('invoices', 'add_index', ['index_name' => 'idx_invoices_due', 'columns' => ['due_date']]);

        $this->assertTrue($plan['success']);
        $this->assertNotEmpty($plan['reverse_ddl']);
        $this->assertStringContainsString('DROP INDEX', $plan['reverse_ddl']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/DynamicSchemaMigrationEngine.php',
            'src/Database/DataPipelineEtlOrchestratorEngine.php',
            'src/Database/ConsistentHashShardRouterEngine.php',
            'src/Database/ConnectionPoolGovernorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
