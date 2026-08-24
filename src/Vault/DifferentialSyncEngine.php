<?php

namespace Atom\Vault;

/**
 * Differential Sync Engine — Phase 33
 *
 * Peer-to-peer differential encrypted delta synchronization,
 * vector clocks, and Last-Write-Wins (LWW) conflict resolution.
 */
class DifferentialSyncEngine
{
    private array $vaultStore = [];
    private array $peerClocks = [];
    private MerkleAuditTree $merkle;

    public function __construct(?MerkleAuditTree $merkle = null)
    {
        $this->merkle = $merkle ?? new MerkleAuditTree();
    }

    /**
     * Stores an encrypted record locally and updates vector clock and Merkle tree.
     */
    public function set(string $key, array $encryptedRecord, string $peerId = 'local'): array
    {
        $currentClock = ($this->vaultStore[$key]['clock'] ?? 0) + 1;
        $timestamp = microtime(true);

        $entry = [
            'key'        => $key,
            'record'     => $encryptedRecord,
            'clock'      => $currentClock,
            'peer_id'    => $peerId,
            'timestamp'  => $timestamp,
        ];

        $this->vaultStore[$key] = $entry;
        $this->peerClocks[$peerId] = max($this->peerClocks[$peerId] ?? 0, $currentClock);

        // Add to Merkle audit tree
        $this->merkle->addLeaf($entry);

        return $entry;
    }

    /**
     * Gets a record from the vault store.
     */
    public function get(string $key): ?array
    {
        return $this->vaultStore[$key] ?? null;
    }

    /**
     * Generates differential deltas for a peer based on their known vector clock.
     */
    public function generateDeltas(int $sinceClock = 0): array
    {
        $deltas = [];
        foreach ($this->vaultStore as $key => $entry) {
            if ($entry['clock'] > $sinceClock) {
                $deltas[] = $entry;
            }
        }
        return $deltas;
    }

    /**
     * Merges incoming remote deltas into local vault using Last-Write-Wins (LWW).
     *
     * @param array $remoteDeltas List of entries from remote peer.
     * @return array Merge summary statistics.
     */
    public function mergeDeltas(array $remoteDeltas): array
    {
        $applied = 0;
        $conflictsResolved = 0;

        foreach ($remoteDeltas as $remoteEntry) {
            $key = $remoteEntry['key'] ?? null;
            if (!$key) continue;

            $local = $this->vaultStore[$key] ?? null;

            if ($local === null) {
                // New record from peer
                $this->vaultStore[$key] = $remoteEntry;
                $this->merkle->addLeaf($remoteEntry);
                $applied++;
            } else {
                // Conflict resolution via Last-Write-Wins (LWW)
                $remoteTs = $remoteEntry['timestamp'] ?? 0;
                $localTs = $local['timestamp'] ?? 0;

                if ($remoteTs > $localTs || ($remoteTs == $localTs && ($remoteEntry['clock'] ?? 0) > ($local['clock'] ?? 0))) {
                    $this->vaultStore[$key] = $remoteEntry;
                    $this->merkle->addLeaf($remoteEntry);
                    $applied++;
                    $conflictsResolved++;
                }
            }

            $peer = $remoteEntry['peer_id'] ?? 'remote';
            $this->peerClocks[$peer] = max($this->peerClocks[$peer] ?? 0, $remoteEntry['clock'] ?? 1);
        }

        return [
            'applied'            => $applied,
            'conflicts_resolved' => $conflictsResolved,
            'total_keys'         => count($this->vaultStore),
            'merkle_root'        => $this->merkle->getRootHash(),
        ];
    }

    /**
     * Lists all keys in the vault store.
     */
    public function listKeys(): array
    {
        return array_keys($this->vaultStore);
    }

    /**
     * Clears all store state (for testing).
     */
    public function reset(): void
    {
        $this->vaultStore = [];
        $this->peerClocks = [];
        $this->merkle->reset();
    }
}
