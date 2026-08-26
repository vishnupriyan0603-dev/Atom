<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\FeatureFlagRolloutEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 95 — FeatureFlagRolloutEngine unit tests (6 tests).
 */
class FeatureFlagRolloutEngineTest extends TestCase
{
    private FeatureFlagRolloutEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FeatureFlagRolloutEngine(new SecretRedactor());
    }

    public function testEvaluateUserDeterministicConsistency(): void
    {
        $this->engine->registerFlag('test_feature_flag', true, 50);

        $res1 = $this->engine->evaluate('test_feature_flag', 'user_consistent_123');
        $res2 = $this->engine->evaluate('test_feature_flag', 'user_consistent_123');

        $this->assertSame($res1['enabled'], $res2['enabled']);
        $this->assertSame($res1['bucket'], $res2['bucket']);
        $this->assertSame($res1['variant'], $res2['variant']);
    }

    public function testGlobalKillSwitchDisablesFlag(): void
    {
        $this->engine->registerFlag('critical_payment_feature', false, 100);

        $res = $this->engine->evaluate('critical_payment_feature', 'user_anyone');
        $this->assertFalse($res['enabled']);
        $this->assertSame('FLAG_GLOBALLY_DISABLED', $res['reason']);
    }

    public function testRoleTargetingOverrideEnablesFlag(): void
    {
        $this->engine->registerFlag('beta_feature', true, 0, ['admin', 'beta_tester']);

        // Guest with 0% rollout gets false
        $resGuest = $this->engine->evaluate('beta_feature', 'guest_1', ['role' => 'guest']);
        $this->assertFalse($resGuest['enabled']);

        // Admin role matches override
        $resAdmin = $this->engine->evaluate('beta_feature', 'admin_1', ['role' => 'admin']);
        $this->assertTrue($resAdmin['enabled']);
        $this->assertSame('ROLE_TARGETING_MATCH', $resAdmin['reason']);
    }

    public function testHundredPercentRolloutEnablesAllUsers(): void
    {
        $this->engine->registerFlag('global_rollout_flag', true, 100);

        for ($i = 0; $i < 20; $i++) {
            $res = $this->engine->evaluate('global_rollout_flag', "user_{$i}");
            $this->assertTrue($res['enabled']);
        }
    }

    public function testUnknownFlagEvaluatesToFalse(): void
    {
        $res = $this->engine->evaluate('non_existent_flag_xyz', 'user_1');
        $this->assertFalse($res['enabled']);
        $this->assertSame('FLAG_NOT_FOUND', $res['reason']);
    }

    public function testGetAllFlagsReturnsList(): void
    {
        $flags = $this->engine->getAllFlags();

        $this->assertGreaterThanOrEqual(3, count($flags));
        $this->assertArrayHasKey('flag_key', $flags[0]);
        $this->assertArrayHasKey('rollout_pct', $flags[0]);
    }
}
