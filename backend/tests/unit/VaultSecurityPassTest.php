<?php

use PHPUnit\Framework\TestCase;
use Atom\Vault\ZeroKnowledgeVaultEngine;
use Atom\Vault\PassphraseAuthGate;

/**
 * Phase 33 — VaultSecurityPassTest security & safety tests (5 tests).
 */
class VaultSecurityPassTest extends TestCase
{
    public function testSecretRedactionInVaultOutputs(): void
    {
        $vault = new ZeroKnowledgeVaultEngine();
        $secret = 'User confidential key sk-ant-api03-secret12345678901234567890';
        $encrypted = $vault->encrypt($secret, 'secure_passphrase_2026');

        // Verify that the plaintext secret was encrypted and not leaked in raw ciphertext/IV
        $this->assertStringNotContainsString('sk-ant-api03', $encrypted['ciphertext']);
        $this->assertStringNotContainsString('sk-ant-api03', $encrypted['iv']);
    }

    public function testWeakPassphraseRejectionInKeyDerivation(): void
    {
        $vault = new ZeroKnowledgeVaultEngine();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at least 8 characters/');

        // Passphrase shorter than 8 chars
        $vault->deriveKey('short', random_bytes(16));
    }

    public function testTimingAttackResistanceInPassphraseCheck(): void
    {
        $gateCode = file_get_contents(dirname(__DIR__, 3) . '/src/Vault/PassphraseAuthGate.php');
        $this->assertNotFalse($gateCode);
        $this->assertStringContainsString('hash_equals(', $gateCode);
    }

    public function testTamperedAuthTagFailsDecryptionCleanly(): void
    {
        $vault = new ZeroKnowledgeVaultEngine();
        $encrypted = $vault->encrypt('Secret data', 'master_pass_12345');

        // Tamper with GCM authentication tag
        $encrypted['tag'] = base64_encode(random_bytes(16));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Authentication tag mismatch/');
        $vault->decrypt($encrypted, 'master_pass_12345');
    }

    public function testNoEvalOrShellExecutionInVaultSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $vaultCode = file_get_contents($rootDir . '/src/Vault/ZeroKnowledgeVaultEngine.php');
        $merkleCode = file_get_contents($rootDir . '/src/Vault/MerkleAuditTree.php');
        $syncCode = file_get_contents($rootDir . '/src/Vault/DifferentialSyncEngine.php');
        $gateCode = file_get_contents($rootDir . '/src/Vault/PassphraseAuthGate.php');

        $this->assertNotFalse($vaultCode);
        $this->assertNotFalse($merkleCode);
        $this->assertNotFalse($syncCode);
        $this->assertNotFalse($gateCode);

        $this->assertStringNotContainsString('eval(', $vaultCode);
        $this->assertStringNotContainsString('eval(', $merkleCode);
        $this->assertStringNotContainsString('eval(', $syncCode);
        $this->assertStringNotContainsString('eval(', $gateCode);
        $this->assertStringNotContainsString('exec(', $vaultCode);
        $this->assertStringNotContainsString('shell_exec(', $vaultCode);
    }
}
