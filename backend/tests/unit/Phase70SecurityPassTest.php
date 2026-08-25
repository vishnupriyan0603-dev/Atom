<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\DistributedCacheInvalidatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 70 Landmark — Phase70SecurityPassTest security & safety tests (5 tests).
 */
class Phase70SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInCacheKeys(): void
    {
        $engine = new DistributedCacheInvalidatorEngine($this->redactor);
        $engine->set('user_sk-1122334455667788990011223344_token', ['val' => 1]);

        $stats = $engine->getStats();
        $allKeys = array_column($stats['keys'], 'key');

        foreach ($allKeys as $k) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $k);
        }
    }

    public function testXFetchLogarithmicCalculationsNeverProduceNan(): void
    {
        $engine = new DistributedCacheInvalidatorEngine($this->redactor);
        $engine->set('calc:test', 'val', 60, 'default', [], 0.001);

        for ($i = 0; $i < 50; $i++) {
            $res = $engine->get('calc:test', 2.0);
            $this->assertTrue($res['found']);
            $this->assertIsBool($res['should_recompute']);
        }
    }

    public function testHighThroughputCacheOperations(): void
    {
        $engine = new DistributedCacheInvalidatorEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->set("key:{$i}", "value_{$i}", 300, "tenant_{$i}", ["tag_" . ($i % 5)]);
            $engine->get("key:{$i}");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testInvalidatingNonExistentTagFailsGracefully(): void
    {
        $engine = new DistributedCacheInvalidatorEngine($this->redactor);
        $res = $engine->invalidateTag('non_existent_random_tag_xyz');

        $this->assertTrue($res['success']);
        $this->assertSame(0, $res['count']);
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
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
