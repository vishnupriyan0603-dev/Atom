<?php

namespace Atom\Network;

/**
 * P2P Edge Swarm Node — Phase 37
 *
 * Decentralized edge node clustering with Raft-style consensus state transitions
 * (LEADER, FOLLOWER, CANDIDATE), terms, and heartbeat coordination.
 */
class P2PEdgeSwarmNode
{
    public const ROLE_FOLLOWER  = 'FOLLOWER';
    public const ROLE_CANDIDATE = 'CANDIDATE';
    public const ROLE_LEADER    = 'LEADER';

    private string $nodeId;
    private string $role = self::ROLE_FOLLOWER;
    private int $currentTerm = 0;
    private ?string $votedFor = null;
    private array $clusterNodes = [];
    private float $lastHeartbeat;

    public function __construct(string $nodeId, array $clusterNodes = [])
    {
        $this->nodeId = $nodeId;
        $this->clusterNodes = $clusterNodes;
        $this->lastHeartbeat = microtime(true);
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCurrentTerm(): int
    {
        return $this->currentTerm;
    }

    /**
     * Triggers election timeout and transitions node to CANDIDATE.
     */
    public function startElection(): array
    {
        $this->currentTerm++;
        $this->role = self::ROLE_CANDIDATE;
        $this->votedFor = $this->nodeId;

        return [
            'candidate_id' => $this->nodeId,
            'term'         => $this->currentTerm,
            'role'         => $this->role,
        ];
    }

    /**
     * Promotes node to LEADER upon receiving majority quorum votes.
     */
    public function promoteToLeader(): void
    {
        $this->role = self::ROLE_LEADER;
        $this->lastHeartbeat = microtime(true);
    }

    /**
     * Processes incoming heartbeat from cluster leader.
     */
    public function receiveHeartbeat(string $leaderId, int $term): bool
    {
        if ($term >= $this->currentTerm) {
            $this->currentTerm = $term;
            $this->role = self::ROLE_FOLLOWER;
            $this->lastHeartbeat = microtime(true);
            return true;
        }
        return false;
    }

    /**
     * Evaluates vote request from candidate peer.
     */
    public function requestVote(string $candidateId, int $term): bool
    {
        if ($term > $this->currentTerm) {
            $this->currentTerm = $term;
            $this->role = self::ROLE_FOLLOWER;
            $this->votedFor = $candidateId;
            return true;
        }

        if ($term === $this->currentTerm && ($this->votedFor === null || $this->votedFor === $candidateId)) {
            $this->votedFor = $candidateId;
            return true;
        }

        return false;
    }
}
