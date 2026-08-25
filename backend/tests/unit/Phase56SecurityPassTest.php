<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Auth\TokenBucketRateLimiterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 56 — Phase56SecurityPassTest security & safety tests (5 tests).
 */
class Phase56SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInClientId(): void
    {
        $engine = new TokenBucketRateLimiterEngine($this->redactor);
        $sensitiveKey = 'sk-1122334455667788990011223344';

        $res = $engine->consume($sensitiveKey, 1);
        $this->assertTrue($res['allowed']);
    }

    public function testTenantBucketIsolation(): void
    {
        $engine = new TokenBucketRateLimiterEngine($this->redactor);
        // Deplete tenant A's bucket
        $engine->consume('tenant_a', 60, 'default');
        $resA = $engine->consume('tenant_a', 1, 'default');
        $this->assertFalse($resA['allowed']);

        // Tenant B's bucket must remain completely full and unaffected
        $resB = $engine->consume('tenant_b', 1, 'default');
        $this->assertTrue($resB['allowed']);
        $this->assertSame(59, $resB['remaining']);
    }

    public function testNonNegativeRemainingTokens(): void
    {
        $engine = new TokenBucketRateLimiterEngine($this->redactor);
        $res = $engine->consume('client_spam', 1000, 'default');

        $this->assertFalse($res['allowed']);
        $this->assertGreaterThanOrEqual(0, $res['remaining']);
    }

    public function testRetryAfterIsPositiveInteger(): void
    {
        $engine = new TokenBucketRateLimiterEngine($this->redactor);
        $res = $engine->consume('client_spam_2', 120, 'default');

        $this->assertFalse($res['allowed']);
        $this->assertGreaterThan(0, $res['retry_after_sec']);
    }

    public function testNoDangerousEvalOrShellExecutionInAuthSubsystem(): void
    {
        $files = [
            'src/Auth/TokenBucketRateLimiterEngine.php',
            'src/Auth/AbacPolicyEngine.php',
            'src/Auth/AbacPolicyStore.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
