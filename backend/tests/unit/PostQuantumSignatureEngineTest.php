<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\PostQuantumSignatureEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 45 — PostQuantumSignatureEngine unit tests (6 tests).
 */
class PostQuantumSignatureEngineTest extends TestCase
{
    private PostQuantumSignatureEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PostQuantumSignatureEngine(8380417, new SecretRedactor());
    }

    public function testGenerateQuantumSignatureKeypair(): void
    {
        $kp = $this->engine->generateKeypair();

        $this->assertNotEmpty($kp['verification_key']);
        $this->assertNotEmpty($kp['signing_key']);
        $this->assertSame('ATOM-MLWE-SIG-5', $kp['algorithm']);
        $this->assertNotEmpty($kp['fingerprint']);
    }

    public function testSignAndVerifyMessageRoundtrip(): void
    {
        $kp = $this->engine->generateKeypair();
        $msg = 'Deploy swarm work order #8812 to edge mesh cluster';

        $sigResult = $this->engine->sign($msg, $kp['signing_key']);
        $this->assertTrue($sigResult['success']);
        $this->assertNotEmpty($sigResult['signature']);

        $valid = $this->engine->verify($msg, $sigResult['signature'], $kp['verification_key']);
        $this->assertTrue($valid);
    }

    public function testCorruptedSignatureVerificationFails(): void
    {
        $kp = $this->engine->generateKeypair();
        $msg = 'Authenticate transaction';

        $sigResult = $this->engine->sign($msg, $kp['signing_key']);

        $corruptedSig = base64_encode(json_encode(['c' => 'corrupted', 'z' => [999999999]]));
        $valid = $this->engine->verify($msg, $corruptedSig, $kp['verification_key']);

        $this->assertFalse($valid);
    }

    public function testInvalidSigningKeyRejection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->sign('test', 'invalid_signing_key');
    }

    public function testNonRepudiationDistinctMessages(): void
    {
        $kp = $this->engine->generateKeypair();
        $msgA = 'Action A';
        $msgB = 'Action B';

        $sigA = $this->engine->sign($msgA, $kp['signing_key']);
        $sigB = $this->engine->sign($msgB, $kp['signing_key']);

        $this->assertNotSame($sigA['signature'], $sigB['signature']);
    }

    public function testLatticeBoundValidation(): void
    {
        $kp = $this->engine->generateKeypair();
        $sigResult = $this->engine->sign('Bound test message', $kp['signing_key']);

        $decoded = json_decode(base64_decode($sigResult['signature']), true);
        $this->assertIsArray($decoded['z']);
        foreach ($decoded['z'] as $coeff) {
            $this->assertLessThanOrEqual(131072, abs($coeff));
        }
    }
}
