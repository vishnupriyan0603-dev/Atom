<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SqlQueryExplainerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 72 — Phase72SecurityPassTest security & safety tests (5 tests).
 */
class Phase72SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSqlQuery(): void
    {
        $engine = new SqlQueryExplainerEngine($this->redactor);
        $sql = "SELECT * FROM keys WHERE token = 'sk-1122334455667788990011223344';";

        $exp = $engine->explainQuery($sql);
        $this->assertTrue($exp['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $exp['sql']);
    }

    public function testHighThroughputQueryExplanation(): void
    {
        $engine = new SqlQueryExplainerEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->explainQuery("SELECT id FROM table_{$i} WHERE col_a = {$i} AND col_b >= 100;");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testEfficiencyScoreBounded(): void
    {
        $engine = new SqlQueryExplainerEngine($this->redactor);
        $badQuery = "SELECT * FROM huge_table ORDER BY random_field;";
        $exp = $engine->explainQuery($badQuery);

        $this->assertGreaterThanOrEqual(10, $exp['efficiency_score']);
        $this->assertLessThanOrEqual(100, $exp['efficiency_score']);
    }

    public function testIndexDdlSyntaxSafety(): void
    {
        $engine = new SqlQueryExplainerEngine($this->redactor);
        $suggestions = $engine->synthesizeIndexSuggestions('users', ['email', 'is_verified']);

        $this->assertStringStartsWith('CREATE INDEX', $suggestions[0]['sql_ddl']);
        $this->assertStringEndsWith(');', $suggestions[0]['sql_ddl']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/SqlQueryExplainerEngine.php',
            'src/Database/DistributedCacheInvalidatorEngine.php',
            'src/Database/SchemaDriftDetectorEngine.php',
            'src/Database/QueryLoadSimulatorEngine.php',
            'src/Database/SqlQueryIndexOptimizerEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
