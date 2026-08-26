<?php

namespace App\Controllers\Api;

use Atom\Security\ZeroKnowledgeProofVerifierEngine;

/**
 * ZkProof API Controller — Phase 91
 */
class ZkProof extends BaseApiController
{
    private static ?ZeroKnowledgeProofVerifierEngine $engine = null;

    private function getEngine(): ZeroKnowledgeProofVerifierEngine
    {
        if (self::$engine === null) {
            self::$engine = new ZeroKnowledgeProofVerifierEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/security/zkp/generate
     */
    public function generate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $secret = $json['secret'] ?? 'super_confidential_master_key_123';
        $identity = $json['identity'] ?? 'user_alice_root';

        $engine = $this->getEngine();
        $res = $engine->generateProof($secret, $identity);

        return $this->respondSuccess($res, 'Zero-Knowledge Proof generated');
    }

    /**
     * POST /api/security/zkp/verify
     */
    public function verify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pubKey = (string) ($json['public_key'] ?? '');
        $proof = $json['proof'] ?? [];
        $identity = $json['identity'] ?? 'user_alice_root';

        $engine = $this->getEngine();
        $res = $engine->verifyProof($pubKey, $proof, $identity);

        return $this->respondSuccess($res, 'Zero-Knowledge Proof verified');
    }

    /**
     * POST /api/security/zkp/rollup
     */
    public function rollup()
    {
        $json = $this->request->getJSON(true) ?? [];
        $txs = $json['transactions'] ?? [
            ['from' => 'alice', 'to' => 'bob', 'amount' => 50],
            ['from' => 'bob', 'to' => 'carol', 'amount' => 25],
            ['from' => 'carol', 'to' => 'dave', 'amount' => 10],
            ['from' => 'dave', 'to' => 'alice', 'amount' => 5],
        ];

        $engine = $this->getEngine();
        $res = $engine->aggregateRollup($txs);

        return $this->respondSuccess($res, 'Batch transactions aggregated into zk-Rollup state root');
    }
}
