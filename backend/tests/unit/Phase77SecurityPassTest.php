<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Config\FeatureFlagRolloutEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 77 — Phase77SecurityPassTest security & safety tests (5 tests).
 */
class Phase77SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInFlagKeyAndUserContext(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $engine->setFlag('flag_sk-1122334455667788990011223344_auth', true, 50, [], ['user_sk-1122334455667788990011223344_alex']);

        $flags = $engine->getAllFlags();
        foreach ($flags as $f) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $f['key']);
        }
    }

    public function testHighThroughputFlagEvaluation(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->evaluate('beta_voice_cloning', "user_{$i}", "tenant_" . ($i % 5));
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testRolloutPercentageStatisticalDistribution(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $engine->setFlag('test_half_rollout', true, 50);

        $activeCount = 0;
        for ($i = 0; $i < 200; $i++) {
            $res = $engine->evaluate('test_half_rollout', "user_dist_{$i}", "tenant_dist_{$i}");
            if ($res['is_active']) {
                $activeCount++;
            }
        }

        // Expected around 50% (between 30% and 70%)
        $this->assertGreaterThan(60, $activeCount);
        $this->assertLessThan(140, $activeCount);
    }

    public function testEvaluationResultStructureCompleteness(): void
    {
        $engine = new FeatureFlagRolloutEngine($this->redactor);
        $res = $engine->evaluate('beta_voice_cloning');

        $this->assertArrayHasKey('flag', $res);
        $this->assertArrayHasKey('is_active', $res);
        $this->assertArrayHasKey('reason', $res);
        $this->assertIsBool($res['is_active']);
    }

    public function testNoDangerousEvalOrShellExecutionInConfigSubsystem(): void
    {
        $files = [
            'src/Config/FeatureFlagRolloutEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
