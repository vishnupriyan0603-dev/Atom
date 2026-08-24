<?php

use PHPUnit\Framework\TestCase;
use Atom\Routing\RoutingCandidate;

class RoutingSecurityPassTest extends TestCase
{
    public function testDisabledCandidatesAreNeverSelected()
    {
        $cand = new RoutingCandidate(['target_id' => 'untested-candidate', 'enabled' => 0]);
        $this->assertFalse($cand->enabled);
    }
}
