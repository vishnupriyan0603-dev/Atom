<?php

use PHPUnit\Framework\TestCase;
use Atom\Auth\ScopedApiTokenManager;

/**
 * Phase 36 — ScopedApiTokenManager unit tests (5 tests).
 */
class ScopedApiTokenManagerTest extends TestCase
{
    private ScopedApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        $this->tokenManager = new ScopedApiTokenManager('test_secret_signing_key_123');
    }

    public function testGenerateAndValidateValidToken(): void
    {
        $tok = $this->tokenManager->generateToken('usr_test', 'default', ['repo:read', 'swarm:dispatch'], 3600);

        $this->assertNotEmpty($tok['token_string']);
        $this->assertStringStartsWith('atm_', $tok['token_string']);

        $res = $this->tokenManager->validateToken($tok['token_string'], 'repo:read');
        $this->assertTrue($res['valid']);
        $this->assertSame('usr_test', $res['payload']['sub']);
        $this->assertSame('default', $res['payload']['tenant_id']);
    }

    public function testExpiredTokenFailsValidation(): void
    {
        // Issue token with -10 second TTL (already expired)
        $tok = $this->tokenManager->generateToken('usr_test', 'default', ['repo:read'], -10);

        $res = $this->tokenManager->validateToken($tok['token_string']);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('expired', $res['error']);
    }

    public function testRevokedTokenFailsValidation(): void
    {
        $tok = $this->tokenManager->generateToken('usr_test', 'default', ['repo:read'], 3600);
        $this->tokenManager->revokeToken($tok['token_id']);

        $res = $this->tokenManager->validateToken($tok['token_string']);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('revoked', $res['error']);
    }

    public function testTamperedSignatureFailsValidation(): void
    {
        $tok = $this->tokenManager->generateToken('usr_test', 'default', ['repo:read'], 3600);
        $tampered = $tok['token_string'] . 'tampered';

        $res = $this->tokenManager->validateToken($tampered);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('signature', $res['error']);
    }

    public function testMissingRequiredScopeFailsValidation(): void
    {
        $tok = $this->tokenManager->generateToken('usr_test', 'default', ['repo:read'], 3600);

        $res = $this->tokenManager->validateToken($tok['token_string'], 'vault:decrypt');
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString("missing required scope 'vault:decrypt'", $res['error']);
    }
}
