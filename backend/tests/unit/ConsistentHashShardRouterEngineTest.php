<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\ConsistentHashShardRouterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 87 — ConsistentHashShardRouterEngine unit tests (6 tests).
 */
class ConsistentHashShardRouterEngineTest extends TestCase
{
    private ConsistentHashShardRouterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ConsistentHashShardRouterEngine(new SecretRedactor(), 32);
    }

    public function testLocateShardDeterministicForIdenticalKey(): void
    {
        $res1 = $this->engine->locateShard('tenant_enterprise_100');
        $res2 = $this->engine->locateShard('tenant_enterprise_100');

        $this->assertTrue($res1['success']);
        $this->assertTrue($res2['success']);
        $this->assertSame($res1['shard']['shard_id'], $res2['shard']['shard_id']);
        $this->assertSame($res1['key_hash'], $res2['key_hash']);
    }

    public function testAddShardIncreasesVirtualNodes(): void
    {
        $initial = $this->engine->getRingStatus();
        $this->engine->addShard('shard_delta', '10.0.10.4', 3306, 1);
        $updated = $this->engine->getRingStatus();

        $this->assertSame($initial['total_shards'] + 1, $updated['total_shards']);
        $this->assertGreaterThan($initial['total_vnodes_on_ring'], $updated['total_vnodes_on_ring']);
    }

    public function testRemoveShardPurgesVirtualNodes(): void
    {
        $this->assertTrue($this->engine->removeShard('shard_alpha'));
        $status = $this->engine->getRingStatus();

        $shardIds = array_column($status['shards'], 'shard_id');
        $this->assertNotContains('shard_alpha', $shardIds);

        // Routing should still succeed across remaining shards
        $res = $this->engine->locateShard('some_user_key');
        $this->assertTrue($res['success']);
        $this->assertNotSame('shard_alpha', $res['shard']['shard_id']);
    }

    public function testLocateShardEmptyRingFailsGracefully(): void
    {
        $engine = new ConsistentHashShardRouterEngine(new SecretRedactor());
        $engine->removeShard('shard_alpha');
        $engine->removeShard('shard_beta');
        $engine->removeShard('shard_gamma');

        $res = $engine->locateShard('any_key');
        $this->assertFalse($res['success']);
        $this->assertSame('NO_SHARDS_IN_RING', $res['error']);
    }

    public function testDsnContainsShardDatabaseName(): void
    {
        $res = $this->engine->locateShard('tenant_alpha_test');

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('mysql:host=', $res['shard']['dsn']);
        $this->assertStringContainsString('dbname=atom_' . $res['shard']['shard_id'], $res['shard']['dsn']);
    }

    public function testVirtualNodesCountBounded(): void
    {
        $engine = new ConsistentHashShardRouterEngine(new SecretRedactor(), 500); // capped at 256
        $status = $engine->getRingStatus();

        $this->assertLessThanOrEqual(256, $status['vnodes_per_shard_base']);
    }
}
