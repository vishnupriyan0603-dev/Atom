<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\ZeroKnowledgeProofVerifierEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 91 — Phase91SecurityPassTest security & safety tests (5 tests).
 */
class Phase91SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretNeverExposedInProofPayload(): void
    {
        $engine = new ZeroKnowledgeProofVerifierEngine($this->redactor);
        $secret = 'sk-1122334455667788990011223344_ultra_secret';
        $proofRes = $engine->generateProof($secret, 'user_identity');

        $this->assertTrue($proofRes['success']);
        $proofJson = json_encode($proofRes);

        // Raw secret witness should never appear in proof or public key
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $proofJson);
        $this->assertStringNotContainsString('ultra_secret', $proofJson);
    }

    public function testHighThroughputZkpVerification(): void
    {
        $engine = new ZeroKnowledgeProofVerifierEngine($this->redactor);
        $proofRes = $engine->generateProof('benchmark_secret_key', 'user_bench');

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->verifyProof($proofRes['public_key'], $proofRes['proof'], 'user_bench');
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testMalformedProofFailsWithoutThrowing(): void
    {
        $engine = new ZeroKnowledgeProofVerifierEngine($this->redactor);
        $res = $engine->verifyProof('12345', ['invalid_key' => 'xyz']);

        $this->assertFalse($res['valid']);
        $this->assertSame('MALFORMED_PROOF_STRUCTURE', $res['error']);
    }

    public function testLargeRollupBatchScalability(): void
    {
        $engine = new ZeroKnowledgeProofVerifierEngine($this->redactor);
        $txs = [];
        for ($i = 0; $i < 100; $i++) {
            $txs[] = ['from' => "usr_{$i}", 'to' => "usr_" . ($i + 1), 'amount' => $i];
        }

        $res = $engine->aggregateRollup($txs);
        $this->assertTrue($res['success']);
        $this->assertSame(100, $res['batch_size']);
        $this->assertSame(64, strlen($res['state_root']));
    }

    public function testNoDangerousEvalOrShellExecutionInSecuritySubsystem(): void
    {
        $files = [
            'src/Security/ZeroKnowledgeProofVerifierEngine.php',
            'src/Security/PostQuantumKemEngine.php',
            'src/Security/PostQuantumSignatureEngine.php',
            'src/Security/GeoFencingFirewallEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
