<?php

use PHPUnit\Framework\TestCase;
use Atom\Daemon\AutoHealingEngine;
use Atom\Daemon\BriefingEngine;
use Atom\Security\SecretRedactor;
use Atom\Governance\PolicyEngine;

/**
 * Phase 25 — AutoHealingAndSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for background daemon operations:
 * - Auto-healing actions are audited and restricted to safe resources
 * - Secret redaction is enforced on all briefings
 * - PolicyEngine linkage is maintained
 * - Healing action history is permanently tracked
 */
class AutoHealingAndSecurityPassTest extends TestCase
{
    public function testAutoHealingPassExecutesSafeActions(): void
    {
        $engine = new AutoHealingEngine();
        $result = $engine->runHealingPass();

        $this->assertSame('completed', $result['status']);
        $this->assertGreaterThanOrEqual(1, $result['actions_count']);
        foreach ($result['actions'] as $action) {
            $this->assertArrayHasKey('action_type', $action);
            $this->assertArrayHasKey('target_resource', $action);
            $this->assertSame('completed', $action['status']);
        }
    }

    public function testHealingHistoryIsTracked(): void
    {
        $engine = new AutoHealingEngine();
        $engine->runHealingPass();
        $history = $engine->getHealingHistory();

        $this->assertNotEmpty($history);
        $this->assertGreaterThanOrEqual(3, count($history));
    }

    public function testSecretRedactionInBriefings(): void
    {
        $engine = new BriefingEngine();
        $briefing = $engine->generateBriefing('morning');

        // Verify that no unredacted API key patterns appear in the content
        $this->assertStringNotContainsString('sk-proj-', $briefing['content']);
        $this->assertStringNotContainsString('AIzaSy', $briefing['content']);
    }

    public function testAutoHealingEngineWithPolicyEngine(): void
    {
        $policy = new PolicyEngine();
        $engine = new AutoHealingEngine($policy);
        $result = $engine->runHealingPass();

        $this->assertSame('completed', $result['status']);
    }

    public function testHealingTargetsOnlyPermittedResources(): void
    {
        $engine = new AutoHealingEngine();
        $result = $engine->runHealingPass();

        $permittedResources = ['storage/temp', 'atom_jobs', 'writable/logs'];
        foreach ($result['actions'] as $action) {
            $this->assertContains($action['target_resource'], $permittedResources);
        }
    }
}
