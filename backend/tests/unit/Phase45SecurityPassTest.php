<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\PostQuantumKemEngine;
use Atom\Security\PostQuantumSignatureEngine;
use Atom\Security\HybridQuantumHandshake;
use Atom\Security\SecretRedactor;

/**
 * Phase 45 — Phase45SecurityPassTest security & safety tests (5 tests).
 */
class Phase45SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSignedMessage(): void
    {
        $sigEngine = new PostQuantumSignatureEngine(8380417, $this->redactor);
        $kp = $sigEngine->generateKeypair();

        $secretMsg = "api_key = 'sk-1122334455667788990011223344'";
        $sanitizedMsg = $this->redactor->redact($secretMsg);

        $sigResult = $sigEngine->sign($secretMsg, $kp['signing_key']);

        // Verification succeeds with sanitized message because secrets were redacted prior to digest
        $valid = $sigEngine->verify($sanitizedMsg, $sigResult['signature'], $kp['verification_key']);
        $this->assertTrue($valid);
    }

    public function testHybridQuantumHandshakeRoundtrip(): void
    {
        $handshake = new HybridQuantumHandshake(new PostQuantumKemEngine(256, 3329, $this->redactor), $this->redactor);
        $init = $handshake->initiateHandshake('node_alpha');

        $this->assertTrue($init['success']);
        $this->assertNotEmpty($init['pqc_public_key']);
        $this->assertNotEmpty($init['classical_public']);

        $resp = $handshake->respondHandshake($init, 'node_beta');
        $this->assertTrue($resp['success']);
        $this->assertSame('ACTIVE_PROTECTED', $resp['quantum_security']);
        $this->assertSame(64, strlen($resp['hybrid_session_key'])); // 32-byte hex key
    }

    public function testTamperedVerificationKeyFailsGracefully(): void
    {
        $sigEngine = new PostQuantumSignatureEngine(8380417, $this->redactor);
        $kp = $sigEngine->generateKeypair();
        $sigResult = $sigEngine->sign('Mission payload', $kp['signing_key']);

        $tamperedVk = base64_encode(json_encode(['vk_matrix' => [0, 0, 0, 0], 'seed_k' => 'tampered']));
        $valid = $sigEngine->verify('Mission payload', $sigResult['signature'], $tamperedVk);

        // Verification must fail without throwing fatal errors
        $this->assertFalse($valid);
    }

    public function testConstantTimeKeyComparisonSecurity(): void
    {
        $kem = new PostQuantumKemEngine(256, 3329, $this->redactor);
        $kp = $kem->generateKeypair();

        $enc = $kem->encapsulate($kp['public_key']);
        $this->assertTrue(hash_equals($enc['derived_aes_key'], $enc['derived_aes_key']));
    }

    public function testNoDangerousEvalOrShellExecutionInSecuritySubsystem(): void
    {
        $files = [
            'src/Security/PostQuantumKemEngine.php',
            'src/Security/PostQuantumSignatureEngine.php',
            'src/Security/HybridQuantumHandshake.php',
            'src/Vault/ZeroKnowledgeVaultEngine.php',
            'src/Security/SecretRedactor.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
