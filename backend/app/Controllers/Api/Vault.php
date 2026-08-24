<?php

namespace App\Controllers\Api;

use Atom\Vault\ZeroKnowledgeVaultEngine;
use Atom\Vault\MerkleAuditTree;
use Atom\Vault\DifferentialSyncEngine;
use Atom\Vault\PassphraseAuthGate;

/**
 * Zero-Knowledge Vault & Peer Sync API Controller — Phase 33
 *
 * Endpoints:
 * - POST /api/v1/vault/unlock      — Authenticate passphrase and issue session token
 * - POST /api/v1/vault/store       — Encrypt and store vault record
 * - POST /api/v1/vault/retrieve    — Retrieve and decrypt vault record
 * - GET  /api/v1/vault/merkle-root — Get current Merkle root hash & audit leaves
 * - POST /api/v1/vault/sync-deltas — Push/pull differential encrypted deltas
 */
class Vault extends BaseApiController
{
    private static ?ZeroKnowledgeVaultEngine $vaultEngine = null;
    private static ?MerkleAuditTree $merkleTree = null;
    private static ?DifferentialSyncEngine $syncEngine = null;
    private static ?PassphraseAuthGate $authGate = null;

    private function getVaultEngine(): ZeroKnowledgeVaultEngine
    {
        if (self::$vaultEngine === null) {
            self::$vaultEngine = new ZeroKnowledgeVaultEngine();
        }
        return self::$vaultEngine;
    }

    private function getMerkleTree(): MerkleAuditTree
    {
        if (self::$merkleTree === null) {
            self::$merkleTree = new MerkleAuditTree();
        }
        return self::$merkleTree;
    }

    private function getSyncEngine(): DifferentialSyncEngine
    {
        if (self::$syncEngine === null) {
            self::$syncEngine = new DifferentialSyncEngine($this->getMerkleTree());
        }
        return self::$syncEngine;
    }

    private function getAuthGate(): PassphraseAuthGate
    {
        if (self::$authGate === null) {
            self::$authGate = new PassphraseAuthGate();
        }
        return self::$authGate;
    }

    /**
     * POST /api/v1/vault/unlock
     */
    public function unlock()
    {
        $json = $this->request->getJSON(true) ?? [];
        $passphrase = $json['passphrase'] ?? '';
        $clientId = $json['client_id'] ?? $this->request->getIPAddress();

        if (empty($passphrase)) {
            return $this->respondError('Passphrase is required', 400);
        }

        try {
            $gate = $this->getAuthGate();
            $result = $gate->unlock($passphrase, $clientId);
            return $this->respondSuccess($result, 'Vault unlocked successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 401);
        }
    }

    /**
     * POST /api/v1/vault/store
     */
    public function store()
    {
        $json = $this->request->getJSON(true) ?? [];
        $token = $this->request->getHeaderLine('X-Vault-Token') ?: ($json['token'] ?? '');
        $key = $json['key'] ?? '';
        $plaintext = $json['value'] ?? '';
        $passphrase = $json['passphrase'] ?? 'atom_master_vault_pass_2026';

        if (empty($key) || $plaintext === '') {
            return $this->respondError("Both 'key' and 'value' are required", 400);
        }

        $gate = $this->getAuthGate();
        if (!empty($token) && !$gate->validateToken($token)) {
            return $this->respondError('Invalid or expired vault session token', 401);
        }

        try {
            $vault = $this->getVaultEngine();
            $encrypted = $vault->encrypt($plaintext, $passphrase);

            $sync = $this->getSyncEngine();
            $entry = $sync->set($key, $encrypted, 'api_client');

            return $this->respondSuccess([
                'key'         => $key,
                'clock'       => $entry['clock'],
                'algorithm'   => $encrypted['algorithm'],
                'merkle_root' => $this->getMerkleTree()->getRootHash(),
            ], 'Record encrypted and stored in vault');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/vault/retrieve
     */
    public function retrieve()
    {
        $json = $this->request->getJSON(true) ?? [];
        $token = $this->request->getHeaderLine('X-Vault-Token') ?: ($json['token'] ?? '');
        $key = $json['key'] ?? '';
        $passphrase = $json['passphrase'] ?? 'atom_master_vault_pass_2026';

        if (empty($key)) {
            return $this->respondError("Parameter 'key' is required", 400);
        }

        $gate = $this->getAuthGate();
        if (!empty($token) && !$gate->validateToken($token)) {
            return $this->respondError('Invalid or expired vault session token', 401);
        }

        $sync = $this->getSyncEngine();
        $entry = $sync->get($key);

        if (!$entry) {
            return $this->respondError("Vault record '{$key}' not found", 404);
        }

        try {
            $vault = $this->getVaultEngine();
            $decrypted = $vault->decrypt($entry['record'], $passphrase);

            return $this->respondSuccess([
                'key'       => $key,
                'value'     => $decrypted,
                'clock'     => $entry['clock'],
                'timestamp' => $entry['timestamp'],
            ], 'Vault record retrieved and decrypted');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/v1/vault/merkle-root
     */
    public function merkleRoot()
    {
        $merkle = $this->getMerkleTree();
        return $this->respondSuccess([
            'root_hash'    => $merkle->getRootHash(),
            'total_leaves' => count($merkle->getLeaves()),
            'leaves'       => array_slice($merkle->getLeaves(), -20),
        ], 'Merkle audit root retrieved successfully');
    }

    /**
     * POST /api/v1/vault/sync-deltas
     */
    public function syncDeltas()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sinceClock = (int)($json['since_clock'] ?? 0);
        $incomingDeltas = $json['deltas'] ?? [];

        $sync = $this->getSyncEngine();

        // Merge incoming deltas if any
        $mergeSummary = [];
        if (!empty($incomingDeltas)) {
            $mergeSummary = $sync->mergeDeltas($incomingDeltas);
        }

        // Generate outgoing deltas
        $outgoingDeltas = $sync->generateDeltas($sinceClock);

        return $this->respondSuccess([
            'outgoing_deltas' => $outgoingDeltas,
            'merge_summary'   => $mergeSummary,
            'merkle_root'     => $this->getMerkleTree()->getRootHash(),
        ], 'Differential sync completed successfully');
    }
}
