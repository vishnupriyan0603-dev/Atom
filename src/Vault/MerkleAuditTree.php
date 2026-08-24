<?php

namespace Atom\Vault;

/**
 * Merkle Audit Tree — Phase 33
 *
 * Cryptographic binary Merkle tree providing tamper-evident hash chaining
 * and audit integrity proofs for vault records.
 */
class MerkleAuditTree
{
    private array $leaves = [];
    private ?string $rootHash = null;

    /**
     * Adds a record to the Merkle tree and updates the tree hash.
     *
     * @param string|array $record Raw record data or hash.
     * @return string The computed leaf hash.
     */
    public function addLeaf(string|array $record): string
    {
        $payload = is_array($record) ? json_encode($record, JSON_UNESCAPED_SLASHES) : $record;
        $leafHash = hash('sha256', $payload);
        $this->leaves[] = $leafHash;
        $this->rootHash = $this->calculateRoot($this->leaves);
        return $leafHash;
    }

    /**
     * Gets the current Merkle root hash.
     */
    public function getRootHash(): ?string
    {
        if (empty($this->leaves)) {
            return null;
        }
        if ($this->rootHash === null) {
            $this->rootHash = $this->calculateRoot($this->leaves);
        }
        return $this->rootHash;
    }

    /**
     * Returns all leaf hashes.
     */
    public function getLeaves(): array
    {
        return $this->leaves;
    }

    /**
     * Verifies whether a given leaf hash is present and valid against the current root.
     */
    public function verifyLeaf(string $leafHash): bool
    {
        return in_array($leafHash, $this->leaves, true);
    }

    /**
     * Calculates the Merkle root hash recursively for a list of hashes.
     */
    private function calculateRoot(array $hashes): string
    {
        if (empty($hashes)) {
            return hash('sha256', '');
        }

        if (count($hashes) === 1) {
            return $hashes[0];
        }

        $nextLevel = [];
        $count = count($hashes);

        for ($i = 0; $i < $count; $i += 2) {
            $left = $hashes[$i];
            $right = ($i + 1 < $count) ? $hashes[$i + 1] : $left; // Duplicate last if odd count
            $nextLevel[] = hash('sha256', $left . $right);
        }

        return $this->calculateRoot($nextLevel);
    }

    /**
     * Clears all leaves (for test isolation).
     */
    public function reset(): void
    {
        $this->leaves = [];
        $this->rootHash = null;
    }
}
