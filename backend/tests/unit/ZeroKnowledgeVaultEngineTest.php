<?php

use PHPUnit\Framework\TestCase;
use Atom\Vault\ZeroKnowledgeVaultEngine;

/**
 * Phase 33 — ZeroKnowledgeVaultEngine unit tests (5 tests).
 */
class ZeroKnowledgeVaultEngineTest extends TestCase
{
    private ZeroKnowledgeVaultEngine $vault;

    protected function setUp(): void
    {
        $this->vault = new ZeroKnowledgeVaultEngine();
    }

    public function testAes256GcmEncryptionAndDecryptionRoundTrip(): void
    {
        $plaintext = 'Confidential Master Secret Token: 9812470123984';
        $passphrase = 'super_secret_master_vault_pass_2026';

        $encrypted = $this->vault->encrypt($plaintext, $passphrase);

        $this->assertSame('aes-256-gcm', $encrypted['algorithm']);
        $this->assertNotEmpty($encrypted['ciphertext']);
        $this->assertNotEmpty($encrypted['iv']);
        $this->assertNotEmpty($encrypted['tag']);
        $this->assertNotEmpty($encrypted['salt']);

        $decrypted = $this->vault->decrypt($encrypted, $passphrase);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptionFailsWithWrongPassphrase(): void
    {
        $this->expectException(\RuntimeException::class);

        $encrypted = $this->vault->encrypt('Secret message', 'correct_passphrase_123');
        $this->vault->decrypt($encrypted, 'wrong_passphrase_456');
    }

    public function testTamperedCiphertextThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $encrypted = $this->vault->encrypt('Sensitive content', 'vault_pass_123456');

        // Tamper with ciphertext
        $encrypted['ciphertext'] = base64_encode('tampered_content_bytes');

        $this->vault->decrypt($encrypted, 'vault_pass_123456');
    }

    public function testKeyDerivationProducesDistinctKeysWithDifferentSalts(): void
    {
        $pass = 'strong_vault_passphrase';
        $salt1 = random_bytes(16);
        $salt2 = random_bytes(16);

        $key1 = $this->vault->deriveKey($pass, $salt1);
        $key2 = $this->vault->deriveKey($pass, $salt2);

        $this->assertNotSame($key1, $key2);
        $this->assertSame(32, strlen($key1));
        $this->assertSame(32, strlen($key2));
    }

    public function testEmptyPlaintextThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->vault->encrypt('', 'master_pass_123');
    }
}
