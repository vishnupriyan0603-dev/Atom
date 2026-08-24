<?php

use PHPUnit\Framework\TestCase;
use Atom\Sync\StateReplicationEngine;

/**
 * Phase 28 — StateReplicationEngine unit tests (5 tests).
 */
class StateReplicationEngineTest extends TestCase
{
    private StateReplicationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new StateReplicationEngine();
    }

    public function testRecordDeltaIncrementsClockMonotonically(): void
    {
        $d1 = $this->engine->recordDelta('prompt', 'p1', ['title' => 'System Prompt']);
        $d2 = $this->engine->recordDelta('prompt', 'p2', ['title' => 'User Prompt']);

        $this->assertGreaterThan(100, $d1['clock']);
        $this->assertSame($d1['clock'] + 1, $d2['clock']);
    }

    public function testGetDeltasSinceFiltersByVectorClock(): void
    {
        $d1 = $this->engine->recordDelta('note', 'n1', ['content' => 'Note 1']);
        $d2 = $this->engine->recordDelta('note', 'n2', ['content' => 'Note 2']);

        $res = $this->engine->getDeltasSince($d1['clock']);
        $this->assertSame(1, $res['count']);
        $this->assertSame('n2', $res['deltas'][0]['entity_id']);
    }

    public function testResolveConflictsRemoteWinsWhenNewer(): void
    {
        $local = ['clock' => 105, 'payload' => ['val' => 'old']];
        $remote = ['clock' => 110, 'payload' => ['val' => 'new']];

        $res = $this->engine->resolveConflicts($local, $remote);
        $this->assertSame('remote', $res['winner']);
        $this->assertSame('new', $res['state']['val']);
    }

    public function testResolveConflictsLocalWinsWhenNewer(): void
    {
        $local = ['clock' => 120, 'payload' => ['val' => 'local_latest']];
        $remote = ['clock' => 115, 'payload' => ['val' => 'remote_stale']];

        $res = $this->engine->resolveConflicts($local, $remote);
        $this->assertSame('local', $res['winner']);
        $this->assertSame('local_latest', $res['state']['val']);
    }

    public function testGetDeltasWithZeroReturnsAll(): void
    {
        $this->engine->recordDelta('item', 'i1', ['name' => 'A']);
        $this->engine->recordDelta('item', 'i2', ['name' => 'B']);

        $res = $this->engine->getDeltasSince(0);
        $this->assertGreaterThanOrEqual(2, $res['count']);
    }
}
