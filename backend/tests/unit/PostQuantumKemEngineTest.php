<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\PostQuantumKemEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 45 — PostQuantumKemEngine unit tests (6 tests).
 */
class PostQuantumKemEngineTest extends TestCase
{
    private PostQuantumKemEngine $kem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kem = new PostQuantumKemEngine(256, 3329, new SecretRedactor());
    }

    public function testGeneratePostQuantumKeypair(): void
    {
        $kp = $this->kem->generateKeypair();

        $this->assertNotEmpty($kp['public_key']);
        $this->assertNotEmpty($kp['secret_key']);
        $this->assertSame('ATOM-MLWE-KEM-768', $kp['algorithm']);
        $this->assertNotEmpty($kp['fingerprint']);
    }

    public function testEncapsulateAndDecapsulateAgreement(): void
    {
        $kp = $this->kem->generateKeypair();

        $enc = $this->kem->encapsulate($kp['public_key']);
        $this->assertTrue($enc['success']);
        $this->assertNotEmpty($enc['ciphertext']);
        $this->assertNotEmpty($enc['shared_secret']);

        $dec = $this->kem->decapsulate($enc['ciphertext'], $kp['secret_key']);
        $this->assertTrue($dec['success']);
        $this->assertNotEmpty($dec['shared_secret']);

        // Symmetric session key derived from HKDF must be non-empty and 64 hex chars (32 bytes)
        $this->assertSame(64, strlen($enc['derived_aes_key']));
        $this->assertSame(64, strlen($dec['derived_aes_key']));
    }

    public function testEncapsulateRejectsInvalidPublicKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->kem->encapsulate('invalid_base64_pk');
    }

    public function testDecapsulateRejectsInvalidCiphertext(): void
    {
        $kp = $this->kem->generateKeypair();
        $this->expectException(\InvalidArgumentException::class);
        $this->kem->decapsulate('invalid_ciphertext', $kp['secret_key']);
    }

    public function testDerivedAesKeysConsistency(): void
    {
        $kp = $this->kem->generateKeypair();
        $enc = $this->kem->encapsulate($kp['public_key']);

        $this->assertNotEmpty($enc['derived_aes_key']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $enc['derived_aes_key']);
    }

    public function testDistinctKeypairsProduceUniqueFingerprints(): void
    {
        $kp1 = $this->kem->generateKeypair();
        $kp2 = $this->kem->generateKeypair();

        $this->assertNotSame($kp1['public_key'], $kp2['public_key']);
        $this->assertNotSame($kp1['fingerprint'], $kp2['fingerprint']);
    }
}
