<?php

use PHPUnit\Framework\TestCase;
use Atom\Governance\KillSwitchManager;
use Atom\Governance\PolicyEngine;

class KillSwitchAndBreakGlassTest extends TestCase
{
    public function testKillSwitchBlocksAccessImmediatelyWhenActive()
    {
        $engine = new PolicyEngine();

        KillSwitchManager::enableKillSwitch('resource', 'restricted_target');
        $this->assertTrue(KillSwitchManager::isKilled('resource', 'restricted_target'));

        $res = $engine->evaluate(1, 'read', 'restricted_target');
        $this->assertTrue($res->isDenied());
        $this->assertContains('KILL_SWITCH_ACTIVE', $res->reasonCodes);

        KillSwitchManager::disableKillSwitch('resource', 'restricted_target');
        $this->assertFalse(KillSwitchManager::isKilled('resource', 'restricted_target'));
    }
}
