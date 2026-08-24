<?php

use PHPUnit\Framework\TestCase;
use Atom\Vault\PassphraseAuthGate;

/**
 * Phase 33 — PassphraseAuthGate unit tests (5 tests).
 */
class PassphraseAuthGateTest extends TestCase
{
    private PassphraseAuthGate $gate;

    protected function setUp(): void
    {
        PassphraseAuthGate::reset();
        $this->gate = new PassphraseAuthGate('test_secret_passphrase_2026');
    }

    public function testUnlockSuccessWithValidPassphrase(): void
    {
        $res = $this->gate->unlock('test_secret_passphrase_2026');

        $this->assertTrue($res['unlocked']);
        $this->assertNotEmpty($res['token']);
        $this->assertSame(3600, $res['expires_in']);
        $this->assertTrue($this->gate->validateToken($res['token']));
    }

    public function testUnlockFailsWithInvalidPassphrase(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->gate->unlock('wrong_passphrase');
    }

    public function testBruteForceLockoutAfterFiveFailures(): void
    {
        $clientId = 'attacker_ip_192_168_1_50';

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->gate->unlock('wrong_guess_' . $i, $clientId);
            } catch (\RuntimeException $e) {
                // Expected failure
            }
        }

        // 6th attempt should be blocked by brute force lockout
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Vault locked due to repeated failed attempts/');
        $this->gate->unlock('test_secret_passphrase_2026', $clientId);
    }

    public function testValidateTokenRejectsUnknownOrEmptyToken(): void
    {
        $this->assertFalse($this->gate->validateToken(''));
        $this->assertFalse($this->gate->validateToken('unknown_token_hash'));
    }

    public function testLockInvalidatesSessionToken(): void
    {
        $res = $this->gate->unlock('test_secret_passphrase_2026');
        $token = $res['token'];

        $this->assertTrue($this->gate->validateToken($token));

        $locked = $this->gate->lock($token);
        $this->assertTrue($locked);
        $this->assertFalse($this->gate->validateToken($token));
    }
}
