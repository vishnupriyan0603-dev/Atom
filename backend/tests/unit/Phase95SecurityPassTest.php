<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\FeatureFlagRolloutEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 95 — Phase95SecurityPassTest security & safety tests (5 tests).
 */
class Phase95SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInFlagKeyAndUser(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $res = $engine->evaluate('flag_sk-1122334455667788990011223344_auth', 'user_sk-1122334455667788990011223344');

        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['flag_key']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['user_id']);
    }

    public function testHighThroughputFlagEvaluation(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->evaluate('quantum_encryption_handshake', "usr_{$i}");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testRolloutPercentageClampingSafety(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $engine->registerFlag('clamped_flag', true, 999); // Should clamp to 100

        $flags = $engine->getAllFlags();
        $map = array_column($flags, null, 'flag_key');
        $this->assertSame(100, $map['clamped_flag']['rollout_pct']);
    }

    public function testUniformDistributionAcrossHashSpace(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $engine->registerFlag('stat_flag', true, 50);

        $enabledCount = 0;
        $total = 400;
        for ($i = 0; $i < $total; $i++) {
            $res = $engine->evaluate('stat_flag', "unique_user_id_seed_{$i}");
            if ($res['enabled']) {
                $enabledCount++;
            }
        }

        // At 50% rollout across 400 users, enabled count should be reasonably balanced (~40% to 60%)
        $ratio = $enabledCount / $total;
        $this->assertGreaterThan(0.40, $ratio);
        $this->assertLessThan(0.60, $ratio);
    }

    public function testNoDangerousEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $files = [
            'src/Infrastructure/FeatureFlagRolloutEngine.php',
            'src/Infrastructure/DynamicCircuitBreakerEngine.php',
            'src/Infrastructure/ChaosEngineeringMeshEngine.php',
            'src/Infrastructure/CanaryTrafficSplitEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
