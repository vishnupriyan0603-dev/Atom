<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\ConsistentHashShardRouterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 87 — Phase87SecurityPassTest security & safety tests (5 tests).
 */
class Phase87SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInShardIdAndKey(): void
    {
        $engine = new ConsistentHashShardRouterEngine($this->redactor);
        $engine->addShard('shard_sk-1122334455667788990011223344_corp', '10.0.0.1');

        $res = $engine->locateShard('tenant_sk-1122334455667788990011223344_user');
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['routing_key']);
    }

    public function testHighThroughputShardResolution(): void
    {
        $engine = new ConsistentHashShardRouterEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->locateShard("user_entity_{$i}");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testConsistentHashUniformDistribution(): void
    {
        $engine = new ConsistentHashShardRouterEngine($this->redactor, 64);
        $distribution = [];

        for ($i = 0; $i < 300; $i++) {
            $res = $engine->locateShard("tenant_id_{$i}");
            $shardId = $res['shard']['shard_id'];
            $distribution[$shardId] = ($distribution[$shardId] ?? 0) + 1;
        }

        // Each of the 3 shards should receive at least 10% of traffic
        $this->assertCount(3, $distribution);
        foreach ($distribution as $shard => $count) {
            $this->assertGreaterThan(30, $count);
        }
    }

    public function testShardWeightClampingSafety(): void
    {
        $engine = new ConsistentHashShardRouterEngine($this->redactor);
        $engine->addShard('heavy_shard', '10.0.0.5', 3306, 999);

        $status = $engine->getRingStatus();
        $map = array_column($status['shards'], null, 'shard_id');
        $this->assertSame(10, $map['heavy_shard']['weight']); // clamped to max 10
    }

    public function testNoDangerousEvalOrShellExecutionInDatabaseSubsystem(): void
    {
        $files = [
            'src/Database/ConsistentHashShardRouterEngine.php',
            'src/Database/ConnectionPoolGovernorEngine.php',
            'src/Database/SqlQueryExplainerEngine.php',
            'src/Database/SchemaDriftDetectorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
