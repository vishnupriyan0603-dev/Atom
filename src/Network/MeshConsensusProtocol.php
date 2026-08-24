<?php

namespace Atom\Network;

/**
 * Mesh Consensus Protocol — Phase 37
 *
 * Distributed state replication over WebRTC mesh via anti-entropy gossip
 * exchange and monotonic version vector convergence.
 */
class MeshConsensusProtocol
{
    private array $localState = [];
    private array $versionVectors = [];

    /**
     * Updates or sets a state key locally.
     */
    public function setState(string $nodeId, string $key, $value): array
    {
        $v = ($this->versionVectors[$key] ?? 0) + 1;
        $this->versionVectors[$key] = $v;
        $this->localState[$key] = [
            'value'     => $value,
            'version'   => $v,
            'origin'    => $nodeId,
            'timestamp' => microtime(true),
        ];

        return $this->localState[$key];
    }

    /**
     * Generates a gossip digest of local version vector keys for exchange.
     */
    public function generateDigest(): array
    {
        return $this->versionVectors;
    }

    /**
     * Computes delta differences given a remote peer's version digest.
     */
    public function computeDeltas(array $remoteDigest): array
    {
        $deltas = [];
        foreach ($this->localState as $key => $item) {
            $remoteVer = $remoteDigest[$key] ?? 0;
            if ($item['version'] > $remoteVer) {
                $deltas[$key] = $item;
            }
        }
        return $deltas;
    }

    /**
     * Merges incoming gossip state deltas.
     */
    public function mergeDeltas(array $incomingDeltas): int
    {
        $applied = 0;
        foreach ($incomingDeltas as $key => $remoteItem) {
            $localVer = $this->versionVectors[$key] ?? 0;
            if ($remoteItem['version'] > $localVer) {
                $this->localState[$key] = $remoteItem;
                $this->versionVectors[$key] = $remoteItem['version'];
                $applied++;
            }
        }
        return $applied;
    }

    /**
     * Gets all local converged state.
     */
    public function getState(): array
    {
        return $this->localState;
    }
}
