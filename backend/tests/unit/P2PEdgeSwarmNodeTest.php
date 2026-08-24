<?php

use PHPUnit\Framework\TestCase;
use Atom\Network\P2PEdgeSwarmNode;

/**
 * Phase 37 — P2PEdgeSwarmNode unit tests (5 tests).
 */
class P2PEdgeSwarmNodeTest extends TestCase
{
    private P2PEdgeSwarmNode $node;

    protected function setUp(): void
    {
        $this->node = new P2PEdgeSwarmNode('node_edge_alpha', ['node_edge_beta', 'node_edge_gamma']);
    }

    public function testInitialNodeStateIsFollower(): void
    {
        $this->assertSame('node_edge_alpha', $this->node->getNodeId());
        $this->assertSame('FOLLOWER', $this->node->getRole());
        $this->assertSame(0, $this->node->getCurrentTerm());
    }

    public function testStartElectionTransitionsToCandidate(): void
    {
        $res = $this->node->startElection();

        $this->assertSame('CANDIDATE', $this->node->getRole());
        $this->assertSame(1, $this->node->getCurrentTerm());
        $this->assertSame('node_edge_alpha', $res['candidate_id']);
    }

    public function testPromoteToLeader(): void
    {
        $this->node->startElection();
        $this->node->promoteToLeader();

        $this->assertSame('LEADER', $this->node->getRole());
    }

    public function testReceiveHeartbeatFromNewerLeaderStepsDown(): void
    {
        $this->node->startElection(); // CANDIDATE, term 1
        $accepted = $this->node->receiveHeartbeat('node_edge_beta', 2); // Leader in term 2

        $this->assertTrue($accepted);
        $this->assertSame('FOLLOWER', $this->node->getRole());
        $this->assertSame(2, $this->node->getCurrentTerm());
    }

    public function testRequestVoteGrantedForHigherTerm(): void
    {
        $granted = $this->node->requestVote('node_edge_beta', 3);

        $this->assertTrue($granted);
        $this->assertSame(3, $this->node->getCurrentTerm());
    }
}
