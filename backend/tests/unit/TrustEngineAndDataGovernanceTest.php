<?php

use PHPUnit\Framework\TestCase;
use Atom\Governance\TrustEngine;

class TrustEngineAndDataGovernanceTest extends TestCase
{
    public function testTrustEngineResolvesTrustLevelsAndThresholds()
    {
        $trust = new TrustEngine();

        $adminTrust = $trust->getTrustLevel(1);
        $this->assertEquals('privileged', $adminTrust);
        $this->assertTrue($trust->meetsTrustThreshold($adminTrust, 'standard'));

        $userTrust = $trust->getTrustLevel(2);
        $this->assertEquals('standard', $userTrust);
        $this->assertFalse($trust->meetsTrustThreshold($userTrust, 'privileged'));
    }
}
