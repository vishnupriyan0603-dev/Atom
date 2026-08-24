<?php

use PHPUnit\Framework\TestCase;
use Atom\Sync\SyncEngine;

/**
 * Phase 28 — SyncEngine unit tests (5 tests).
 */
class SyncEngineTest extends TestCase
{
    private SyncEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new SyncEngine();
    }

    public function testGetSyncTopologyReturnsValidStructure(): void
    {
        $top = $this->engine->getSyncTopology();
        $this->assertSame('active', $top['sync_status']);
        $this->assertArrayHasKey('current_vector_clock', $top);
        $this->assertArrayHasKey('peers', $top);
        $this->assertGreaterThanOrEqual(1, $top['active_peers_count']);
    }

    public function testRegisterPeerReturnsValidPeer(): void
    {
        $peer = $this->engine->registerPeer('test_device_1', 'mobile_flutter', 'Pixel 8 Pro', '192.168.1.50');
        $this->assertSame('test_device_1', $peer['device_id']);
        $this->assertSame('mobile_flutter', $peer['client_type']);
        $this->assertSame('online', $peer['status']);
    }

    public function testPushDeltaIncrementsClockAndBroadcasts(): void
    {
        $initialClock = $this->engine->getReplicationEngine()->getCurrentClock();
        $delta = $this->engine->pushDelta('memory', 'mem_001', ['text' => 'New sync item'], 'device_a');

        $this->assertGreaterThan($initialClock, $delta['clock']);
        $this->assertSame('mem_001', $delta['entity_id']);
        $this->assertSame('memory', $delta['entity_type']);
    }

    public function testPullDeltasRetrievesRecordedUpdates(): void
    {
        $this->engine->pushDelta('chat', 'msg_1', ['text' => 'Hello']);
        $this->engine->pushDelta('chat', 'msg_2', ['text' => 'World']);

        $res = $this->engine->pullDeltas(0);
        $this->assertGreaterThanOrEqual(2, $res['count']);
        $this->assertNotEmpty($res['deltas']);
    }

    public function testBroadcastEventReturnsSuccess(): void
    {
        $res = $this->engine->broadcastEvent('system:alert', ['severity' => 'info']);
        $this->assertTrue($res['broadcast_success']);
        $this->assertArrayHasKey('frame', $res);
        $this->assertSame('system:alert', $res['frame']['event']);
    }
}
