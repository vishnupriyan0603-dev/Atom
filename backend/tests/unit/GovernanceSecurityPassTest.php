<?php

use PHPUnit\Framework\TestCase;
use Atom\Governance\PolicySimulator;

class GovernanceSecurityPassTest extends TestCase
{
    public function testPolicySimulatorDryRunDoesNotMutateState()
    {
        $sim = new PolicySimulator();
        $res = $sim->simulate(1, 'tool.read', 'workspace');

        $this->assertTrue($res['simulation']);
        $this->assertEquals('allow', $res['decision']);
    }
}
