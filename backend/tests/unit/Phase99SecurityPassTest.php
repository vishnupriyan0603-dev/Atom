<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\DistributedRateLimiterMeshEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 99 — Phase99SecurityPassTest security & safety tests (5 tests).
 */
class Phase99SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInClientKeyAndNode(): void
    {
        $engine = new DistributedRateLimiterMeshEngine($this->redactor);
        $res = $engine->consume('sk-1122334455667788990011223344_api_key', 1);

        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['client_key']);

        $syncRes = $engine->syncMeshNode('node_sk-1122334455667788990011223344', []);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $syncRes['node_id']);
    }

    public function testHighThroughputRateLimiting(): void
    {
        $engine = new DistributedRateLimiterMeshEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->consume("client_bench_{$i}", 1, 'enterprise');
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testNegativeOrZeroTokensCostClampedToAtLeastOne(): void
    {
        $engine = new DistributedRateLimiterMeshEngine($this->redactor);
        $res = $engine->consume('test_clamp', -50); // negative cost should clamp to 1

        $this->assertTrue($res['allowed']);
        $this->assertSame(99.0, $res['remaining_tokens']);
    }

    public function testTokensNeverExceedMaximumBucketCapacity(): void
    {
        $engine = new DistributedRateLimiterMeshEngine($this->redactor);
        $res = $engine->consume('capacity_cap_user', 1, 'free');

        $this->assertLessThanOrEqual(10.0, $res['remaining_tokens']);
    }

    public function testNoDangerousEvalOrShellExecutionInSecuritySubsystem(): void
    {
        $files = [
            'src/Security/DistributedRateLimiterMeshEngine.php',
            'src/Security/ZeroKnowledgeProofVerifierEngine.php',
            'src/Security/PostQuantumKemEngine.php',
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
