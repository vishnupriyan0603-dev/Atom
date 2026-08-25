<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SqlQueryIndexOptimizerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 52 — Phase52SecurityPassTest security & safety tests (5 tests).
 */
class Phase52SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSqlQueryLiterals(): void
    {
        $engine = new SqlQueryIndexOptimizerEngine($this->redactor);
        $sql = "SELECT * FROM api_keys WHERE secret_key = 'sk-1122334455667788990011223344' AND tenant = 't1';";

        $result = $engine->analyze($sql);
        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $result['sql_ddl_migration']);
    }

    public function testIndexMigrationNonDestructiveIntegrity(): void
    {
        $engine = new SqlQueryIndexOptimizerEngine($this->redactor);
        $sql = "SELECT * FROM sensitive_vault WHERE id = 1;";

        $result = $engine->analyze($sql);
        $this->assertStringNotContainsString('DROP TABLE', $result['sql_ddl_migration']);
        $this->assertStringNotContainsString('TRUNCATE', $result['sql_ddl_migration']);
        $this->assertStringStartsWith('CREATE INDEX', $result['sql_ddl_migration']);
    }

    public function testIdentifierSanitization(): void
    {
        $engine = new SqlQueryIndexOptimizerEngine($this->redactor);
        // Attempt injection through column/table naming
        $sql = "SELECT * FROM `orders; DROP TABLE users;` WHERE user_id = 1;";

        $result = $engine->analyze($sql);
        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $result['recommended_index']['name']);
    }

    public function testCostBoundsVerification(): void
    {
        $engine = new SqlQueryIndexOptimizerEngine($this->redactor);
        $sql = "SELECT * FROM logs WHERE a = 1 AND b = 2 AND c = 3 AND d = 4;";

        $result = $engine->analyze($sql);
        $this->assertLessThanOrEqual(100.0, $result['cost_reduction_pct']);
        $this->assertGreaterThanOrEqual(0.0, $result['cost_reduction_pct']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/SqlQueryIndexOptimizerEngine.php',
            'src/Database/Connection.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
