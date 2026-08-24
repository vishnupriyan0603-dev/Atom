<?php

use PHPUnit\Framework\TestCase;
use Atom\Vault\DifferentialSyncEngine;
use Atom\Vault\MerkleAuditTree;

/**
 * Phase 33 — DifferentialSyncEngine unit tests (5 tests).
 */
class DifferentialSyncEngineTest extends TestCase
{
    private DifferentialSyncEngine $sync;
    private MerkleAuditTree $merkle;

    protected function setUp(): void
    {
        $this->merkle = new MerkleAuditTree();
        $this->sync = new DifferentialSyncEngine($this->merkle);
    }

    public function testStoreAndRetrieveEncryptedRecord(): void
    {
        $payload = ['ciphertext' => 'enc_xyz', 'iv' => 'iv_123', 'tag' => 'tag_456', 'salt' => 'salt_789'];
        $entry = $this->sync->set('api_key', $payload, 'local_device');

        $this->assertSame('api_key', $entry['key']);
        $this->assertSame(1, $entry['clock']);

        $retrieved = $this->sync->get('api_key');
        $this->assertNotNull($retrieved);
        $this->assertSame($payload, $retrieved['record']);
    }

    public function testGenerateDeltasSinceClock(): void
    {
        $this->sync->set('k1', ['data' => '1']); // clock = 1
        $this->sync->set('k2', ['data' => '2']); // clock = 1
        $this->sync->set('k1', ['data' => '3']); // clock = 2

        $allDeltas = $this->sync->generateDeltas(0);
        $this->assertCount(2, $allDeltas);

        $recentDeltas = $this->sync->generateDeltas(1);
        $this->assertCount(1, $recentDeltas);
        $this->assertSame('k1', $recentDeltas[0]['key']);
    }

    public function testMergeDeltasNewRecords(): void
    {
        $remoteDeltas = [
            [
                'key'       => 'remote_key_1',
                'record'    => ['ciphertext' => 'rem_enc'],
                'clock'     => 1,
                'peer_id'   => 'peer_b',
                'timestamp' => microtime(true),
            ]
        ];

        $summary = $this->sync->mergeDeltas($remoteDeltas);

        $this->assertSame(1, $summary['applied']);
        $this->assertSame(0, $summary['conflicts_resolved']);
        $this->assertNotNull($this->sync->get('remote_key_1'));
    }

    public function testMergeDeltasConflictResolutionLastWriteWins(): void
    {
        // Store initial local record
        $this->sync->set('shared_config', ['val' => 'local_old']);
        $local = $this->sync->get('shared_config');

        // Remote peer has a newer timestamp
        $remoteNewer = [
            [
                'key'       => 'shared_config',
                'record'    => ['val' => 'remote_new'],
                'clock'     => 2,
                'peer_id'   => 'peer_c',
                'timestamp' => $local['timestamp'] + 10.0,
            ]
        ];

        $summary = $this->sync->mergeDeltas($remoteNewer);

        $this->assertSame(1, $summary['applied']);
        $this->assertSame(1, $summary['conflicts_resolved']);

        $updated = $this->sync->get('shared_config');
        $this->assertSame('remote_new', $updated['record']['val']);
    }

    public function testMerkleTreeUpdatesOnSyncMerge(): void
    {
        $this->assertNull($this->merkle->getRootHash());

        $this->sync->set('k1', ['data' => '1']);
        $root1 = $this->merkle->getRootHash();
        $this->assertNotNull($root1);

        $this->sync->set('k2', ['data' => '2']);
        $root2 = $this->merkle->getRootHash();
        $this->assertNotSame($root1, $root2);
    }
}
