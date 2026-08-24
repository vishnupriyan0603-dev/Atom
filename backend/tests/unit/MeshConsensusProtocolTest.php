<?php

use PHPUnit\Framework\TestCase;
use Atom\Network\MeshConsensusProtocol;

/**
 * Phase 37 — MeshConsensusProtocol unit tests (5 tests).
 */
class MeshConsensusProtocolTest extends TestCase
{
    private MeshConsensusProtocol $consensus;

    protected function setUp(): void
    {
        $this->consensus = new MeshConsensusProtocol();
    }

    public function testSetLocalStateIncrementsVersion(): void
    {
        $state = $this->consensus->setState('node_1', 'cluster_mode', 'ACTIVE');

        $this->assertSame('ACTIVE', $state['value']);
        $this->assertSame(1, $state['version']);
        $this->assertSame('node_1', $state['origin']);
    }

    public function testGenerateDigestReturnsVersionVectors(): void
    {
        $this->consensus->setState('node_1', 'k1', 'v1');
        $this->consensus->setState('node_1', 'k2', 'v2');

        $digest = $this->consensus->generateDigest();
        $this->assertSame(1, $digest['k1']);
        $this->assertSame(1, $digest['k2']);
    }

    public function testComputeDeltasForOutdatedRemoteDigest(): void
    {
        $this->consensus->setState('node_1', 'leader_id', 'node_1'); // version 1
        $this->consensus->setState('node_1', 'epoch', 100);          // version 1

        // Remote peer has leader_id v1, but lacks epoch (v0)
        $remoteDigest = ['leader_id' => 1];
        $deltas = $this->consensus->computeDeltas($remoteDigest);

        $this->assertArrayHasKey('epoch', $deltas);
        $this->assertArrayNotHasKey('leader_id', $deltas);
    }

    public function testMergeIncomingNewerDeltas(): void
    {
        $incoming = [
            'remote_key' => [
                'value'   => 'REMOTE_DATA',
                'version' => 5,
                'origin'  => 'node_remote',
            ],
        ];

        $applied = $this->consensus->mergeDeltas($incoming);
        $this->assertSame(1, $applied);

        $state = $this->consensus->getState();
        $this->assertSame('REMOTE_DATA', $state['remote_key']['value']);
    }

    public function testIgnoreOutdatedIncomingDeltas(): void
    {
        $this->consensus->setState('node_1', 'metric_x', 'VAL_LOCAL'); // version 1
        $this->consensus->setState('node_1', 'metric_x', 'VAL_UPDATED'); // version 2

        $staleIncoming = [
            'metric_x' => [
                'value'   => 'VAL_STALE',
                'version' => 1,
            ],
        ];

        $applied = $this->consensus->mergeDeltas($staleIncoming);
        $this->assertSame(0, $applied);

        $state = $this->consensus->getState();
        $this->assertSame('VAL_UPDATED', $state['metric_x']['value']);
    }
}
