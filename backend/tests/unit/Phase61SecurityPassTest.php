<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\QueryLoadSimulatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 61 — Phase61SecurityPassTest security & safety tests (5 tests).
 */
class Phase61SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSqlString(): void
    {
        $engine = new QueryLoadSimulatorEngine($this->redactor);
        $sql = 'SELECT * FROM users WHERE token = "sk-1122334455667788990011223344"';

        $res = $engine->simulateLoad($sql, 50);
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['sql']);
    }

    public function testSqlInjectionStringSafety(): void
    {
        $engine = new QueryLoadSimulatorEngine($this->redactor);
        $maliciousSql = "SELECT * FROM users WHERE id = 1; DROP TABLE users; --";

        $res = $engine->simulateLoad($maliciousSql, 50);
        $this->assertTrue($res['success']);
    }

    public function testHighConcurrencyIterationMemorySafety(): void
    {
        $engine = new QueryLoadSimulatorEngine($this->redactor);
        $sql = 'SELECT * FROM metrics WHERE id = 1';

        $startTime = microtime(true);
        $res = $engine->simulateLoad($sql, 1000);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testPercentileLatenciesMonotonicOrder(): void
    {
        $engine = new QueryLoadSimulatorEngine($this->redactor);
        $res = $engine->simulateLoad('SELECT * FROM accounts', 100);

        $this->assertLessThanOrEqual($res['p90_latency_ms'], $res['p50_latency_ms']);
        $this->assertLessThanOrEqual($res['p99_latency_ms'], $res['p90_latency_ms']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
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
