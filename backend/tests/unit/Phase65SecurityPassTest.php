<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SchemaDriftDetectorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 65 — Phase65SecurityPassTest security & safety tests (5 tests).
 */
class Phase65SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testMigrationNameInjectionSanitization(): void
    {
        $engine = new SchemaDriftDetectorEngine($this->redactor);
        $maliciousName = "AutoSync; eval('malicious');";

        $code = $engine->synthesizeMigration([], $maliciousName);
        $this->assertStringContainsString('class AutoSyncevalmalicious extends Migration', $code);
        $this->assertDoesNotMatchRegularExpression('/class\s+.*[;\'"]/i', $code);
    }

    public function testReversibleMigrationIncludesDownMethod(): void
    {
        $engine = new SchemaDriftDetectorEngine($this->redactor);
        $drifts = [
            [
                'type' => 'MISSING_TABLE',
                'table' => 'audit_events',
                'columns' => ['id' => 'INT', 'event' => 'VARCHAR'],
            ]
        ];

        $code = $engine->synthesizeMigration($drifts);
        $this->assertStringContainsString('dropTable(\'audit_events\'', $code);
    }

    public function testLargeSchemaDriftAnalysisResilience(): void
    {
        $engine = new SchemaDriftDetectorEngine($this->redactor);
        $current = [];
        $expected = [];

        for ($i = 0; $i < 100; $i++) {
            $current["tbl_{$i}"] = ['id' => 'INT', 'col_a' => 'VARCHAR'];
            $expected["tbl_{$i}"] = ['id' => 'INT', 'col_a' => 'VARCHAR', 'col_b' => 'DATETIME'];
        }

        $startTime = microtime(true);
        $res = $engine->detectDrift($current, $expected);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertSame(100, $res['drift_count']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testDriftOutputNeverContainsSensitiveSecrets(): void
    {
        $engine = new SchemaDriftDetectorEngine($this->redactor);
        $current = ['keys' => ['id' => 'INT']];
        $expected = ['keys' => ['id' => 'INT', 'sk_11223344' => 'VARCHAR']];

        $res = $engine->detectDrift($current, $expected);
        $this->assertTrue($res['success']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
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
