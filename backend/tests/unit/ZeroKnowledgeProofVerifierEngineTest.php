<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\ZeroKnowledgeProofVerifierEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 91 — ZeroKnowledgeProofVerifierEngine unit tests (6 tests).
 */
class ZeroKnowledgeProofVerifierEngineTest extends TestCase
{
    private ZeroKnowledgeProofVerifierEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ZeroKnowledgeProofVerifierEngine(new SecretRedactor());
    }

    public function testGenerateAndVerifyZkpRoundTripSuccess(): void
    {
        $proofRes = $this->engine->generateProof('my_secret_vault_passphrase', 'user_alice_root');

        $this->assertTrue($proofRes['success']);
        $this->assertArrayHasKey('public_key', $proofRes);
        $this->assertArrayHasKey('commitment_a', $proofRes['proof']);
        $this->assertArrayHasKey('response_z', $proofRes['proof']);

        $verifyRes = $this->engine->verifyProof($proofRes['public_key'], $proofRes['proof'], 'user_alice_root');
        $this->assertTrue($verifyRes['valid']);
        $this->assertSame('PROOF_ACCEPTED_ZERO_KNOWLEDGE_PRESERVED', $verifyRes['status']);
        $this->assertSame($verifyRes['lhs_commitment'], $verifyRes['rhs_evaluation']);
    }

    public function testVerifyZkpWrongIdentityFailsVerification(): void
    {
        $proofRes = $this->engine->generateProof('valid_secret', 'user_alice_root');

        // Verification with different identity should fail Fiat-Shamir hash check
        $verifyRes = $this->engine->verifyProof($proofRes['public_key'], $proofRes['proof'], 'user_mallory_imposter');
        $this->assertFalse($verifyRes['valid']);
        $this->assertSame('CHALLENGE_MISMATCH_FIAT_SHAMIR_FAILED', $verifyRes['reason']);
    }

    public function testVerifyZkpTamperedResponseZRejected(): void
    {
        $proofRes = $this->engine->generateProof('valid_secret', 'user_alice_root');

        // Mutate response z
        $tamperedProof = $proofRes['proof'];
        $tamperedProof['response_z'] = bcadd($tamperedProof['response_z'], '1');

        $verifyRes = $this->engine->verifyProof($proofRes['public_key'], $tamperedProof, 'user_alice_root');
        $this->assertFalse($verifyRes['valid']);
        $this->assertSame('PROOF_REJECTED_INVALID_SECRET', $verifyRes['status']);
    }

    public function testEmptySecretFailsProofGeneration(): void
    {
        $res = $this->engine->generateProof('');
        $this->assertFalse($res['success']);
        $this->assertNull($res['proof']);
    }

    public function testAggregateRollupProducesDeterministicMerkleRoot(): void
    {
        $txs = [
            ['from' => 'alice', 'to' => 'bob', 'amount' => 10],
            ['from' => 'bob', 'to' => 'carol', 'amount' => 5],
        ];

        $res1 = $this->engine->aggregateRollup($txs);
        $res2 = $this->engine->aggregateRollup($txs);

        $this->assertTrue($res1['success']);
        $this->assertSame(2, $res1['batch_size']);
        $this->assertSame($res1['state_root'], $res2['state_root']);
        $this->assertSame($res1['validity_proof'], $res2['validity_proof']);
    }

    public function testEmptyRollupFailsGracefully(): void
    {
        $res = $this->engine->aggregateRollup([]);
        $this->assertFalse($res['success']);
        $this->assertSame('', $res['state_root']);
    }
}
