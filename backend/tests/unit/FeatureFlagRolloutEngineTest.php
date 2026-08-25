<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Config\FeatureFlagRolloutEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 77 — FeatureFlagRolloutEngine unit tests (6 tests).
 */
class FeatureFlagRolloutEngineTest extends TestCase
{
    private FeatureFlagRolloutEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FeatureFlagRolloutEngine(new SecretRedactor());
    }

    public function testEvaluateUserWhitelistMatch(): void
    {
        $res = $this->engine->evaluate('beta_voice_cloning', 'user_alex', 'default');

        $this->assertTrue($res['is_active']);
        $this->assertSame('USER_WHITELIST_MATCH', $res['reason']);
    }

    public function testEvaluateTenantWhitelistMatch(): void
    {
        $res = $this->engine->evaluate('beta_voice_cloning', 'random_user', 'tenant_vip');

        $this->assertTrue($res['is_active']);
        $this->assertSame('TENANT_WHITELIST_MATCH', $res['reason']);
    }

    public function testEvaluateMasterKillSwitchDisabled(): void
    {
        $res = $this->engine->evaluate('legacy_xml_export', 'admin', 'tenant_vip');

        $this->assertFalse($res['is_active']);
        $this->assertSame('MASTER_SWITCH_DISABLED', $res['reason']);
    }

    public function testEvaluateNonExistentFlagReturnsDefaultDisabled(): void
    {
        $res = $this->engine->evaluate('non_existent_future_flag_xyz');

        $this->assertFalse($res['is_active']);
        $this->assertSame('FLAG_NOT_FOUND_DEFAULT_DISABLED', $res['reason']);
    }

    public function testSetFlagClampsRolloutPercentage(): void
    {
        $this->engine->setFlag('clamped_flag_high', true, 150);
        $this->engine->setFlag('clamped_flag_low', true, -20);

        $flags = $this->engine->getAllFlags();
        $keyMap = array_column($flags, null, 'key');

        $this->assertSame(100, $keyMap['clamped_flag_high']['rollout_pct']);
        $this->assertSame(0, $keyMap['clamped_flag_low']['rollout_pct']);
    }

    public function testFullRolloutAlwaysEvaluatesActive(): void
    {
        $res = $this->engine->evaluate('post_quantum_v2', 'any_user_1', 'any_tenant_1');

        $this->assertTrue($res['is_active']);
        $this->assertSame('FULL_ROLLOUT_100_PCT', $res['reason']);
    }
}
