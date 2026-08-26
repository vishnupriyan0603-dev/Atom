<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\EventSourcingCqrsLedgerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 102 — EventSourcingCqrsLedgerEngine unit tests (6 tests).
 */
class EventSourcingCqrsLedgerEngineTest extends TestCase
{
    private EventSourcingCqrsLedgerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new EventSourcingCqrsLedgerEngine(new SecretRedactor());
    }

    public function testDispatchCommandAppendsChainedEvent(): void
    {
        $res = $this->engine->dispatchCommand('aggregate-test-1', 'CreateWorkspace', [
            'name' => 'Test Unit Workspace',
            'status' => 'active',
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(1, $res['version']);
        $this->assertNotEmpty($res['checksum']);
        $this->assertEquals('WorkspaceCreatedEvent', $res['event']['event_type']);
        $this->assertEquals(64, strlen($res['checksum']));
    }

    public function testOptimisticConcurrencyVersionConflict(): void
    {
        $this->engine->dispatchCommand('aggregate-occ-1', 'CreateWorkspace', ['name' => 'Initial']);

        // Dispatch with matching expected version -> succeeds
        $res2 = $this->engine->dispatchCommand('aggregate-occ-1', 'UpdateAgentPersona', ['depth' => 2], 1);
        $this->assertTrue($res2['success']);
        $this->assertEquals(2, $res2['version']);

        // Dispatch with stale expected version (expected 1, but is 2) -> conflicts
        $resConflict = $this->engine->dispatchCommand('aggregate-occ-1', 'UpdateAgentPersona', ['depth' => 3], 1);
        $this->assertFalse($resConflict['success']);
        $this->assertTrue($resConflict['conflict']);
        $this->assertStringContainsString('Concurrency Conflict', $resConflict['error']);
    }

    public function testTimeTravelHistoricalStateReconstruction(): void
    {
        $aggId = 'aggregate-timetravel-1';
        $this->engine->dispatchCommand($aggId, 'CreateWorkspace', ['name' => 'Alpha', 'status' => 'v1']);
        $this->engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['name' => 'Beta', 'status' => 'v2']);
        $this->engine->dispatchCommand($aggId, 'DeployPipeline', ['name' => 'Gamma', 'status' => 'v3']);

        // Reconstruct at version 1
        $tt1 = $this->engine->timeTravelToVersion($aggId, 1);
        $this->assertTrue($tt1['success']);
        $this->assertEquals(1, $tt1['reconstructed_state']['version']);
        $this->assertEquals('v1', $tt1['reconstructed_state']['status']);
        $this->assertTrue($tt1['is_historical']);

        // Reconstruct at version 2
        $tt2 = $this->engine->timeTravelToVersion($aggId, 2);
        $this->assertTrue($tt2['success']);
        $this->assertEquals(2, $tt2['reconstructed_state']['version']);
        $this->assertEquals('v2', $tt2['reconstructed_state']['status']);

        // Reconstruct at head (version 3)
        $tt3 = $this->engine->timeTravelToVersion($aggId, 3);
        $this->assertTrue($tt3['success']);
        $this->assertEquals(3, $tt3['reconstructed_state']['version']);
        $this->assertEquals('v3', $tt3['reconstructed_state']['status']);
        $this->assertFalse($tt3['is_historical']);
    }

    public function testMaterializedProjectionUpdates(): void
    {
        $aggId = 'aggregate-proj-1';
        $this->engine->dispatchCommand($aggId, 'CreateWorkspace', [
            'name' => 'Materialized Workspace',
            'status' => 'online',
        ]);

        $projections = $this->engine->getProjections();
        $this->assertTrue($projections['success']);
        $this->assertArrayHasKey($aggId, $projections['projections']);
        $this->assertEquals(1, $projections['projections'][$aggId]['current_version']);
        $this->assertEquals('online', $projections['projections'][$aggId]['status']);
    }

    public function testGetEventStreamWithRangeFilter(): void
    {
        $aggId = 'aggregate-stream-1';
        for ($i = 1; $i <= 5; $i++) {
            $this->engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['step' => $i]);
        }

        $stream = $this->engine->getEventStream($aggId, 2, 4);
        $this->assertTrue($stream['success']);
        $this->assertEquals(3, $stream['total_events']);
        $this->assertEquals(2, $stream['events'][0]['version']);
        $this->assertEquals(4, $stream['events'][2]['version']);
    }

    public function testCryptographicLedgerIntegrityVerification(): void
    {
        $aggId = 'aggregate-verify-1';
        $this->engine->dispatchCommand($aggId, 'CreateWorkspace', ['name' => 'Secure Ledger']);
        $this->engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['persona' => 'Heroic']);

        $verify = $this->engine->verifyLedgerIntegrity($aggId);
        $this->assertTrue($verify['success']);
        $this->assertTrue($verify['is_valid']);
        $this->assertEquals('SECURE_AND_VERIFIED', $verify['chain_status']);
    }
}
