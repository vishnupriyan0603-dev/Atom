<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\ConnectionPoolGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 79 — Phase79SecurityPassTest security & safety tests (5 tests).
 */
class Phase79SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInTenantAndContext(): void
    {
        $engine = new ConnectionPoolGovernorEngine($this->redactor);
        $res = $engine->leaseConnection('tenant_sk-1122334455667788990011223344_corp', 'context_token');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['tenant_id']);
    }

    public function testHighThroughputConnectionCycling(): void
    {
        $engine = new ConnectionPoolGovernorEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $lease = $engine->leaseConnection("tenant_{$i}");
            if ($lease['success']) {
                $engine->releaseConnection($lease['handle_id']);
            }
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testPoolUtilizationBounded(): void
    {
        $engine = new ConnectionPoolGovernorEngine($this->redactor);
        $status = $engine->getPoolStatus();

        $this->assertGreaterThanOrEqual(0.0, $status['utilization_pct']);
        $this->assertLessThanOrEqual(100.0, $status['utilization_pct']);
    }

    public function testReclaimEmptyOrValidHandlesSafety(): void
    {
        $engine = new ConnectionPoolGovernorEngine($this->redactor);
        $res = $engine->reclaimLeakedConnections();

        $this->assertTrue($res['success']);
        $this->assertIsInt($res['reclaimed_count']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/ConnectionPoolGovernorEngine.php',
            'src/Database/SqlQueryExplainerEngine.php',
            'src/Database/DistributedCacheInvalidatorEngine.php',
            'src/Database/SchemaDriftDetectorEngine.php',
            'src/Database/QueryLoadSimulatorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
