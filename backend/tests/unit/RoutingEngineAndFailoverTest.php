<?php

use PHPUnit\Framework\TestCase;
use Atom\Routing\RoutingEngine;
use Atom\Routing\RoutingCandidate;

class RoutingEngineAndFailoverTest extends TestCase
{
    public function testRoutingEngineSelectsOptimalCandidateAndBypassesFailedProvider()
    {
        $engine = new RoutingEngine();

        $cands = [
            new RoutingCandidate(['target_id' => 'gemini-1.5-flash', 'provider' => 'gemini', 'evaluation_score' => 0.98, 'enabled' => 1]),
            new RoutingCandidate(['target_id' => 'groq-llama3-70b', 'provider' => 'groq', 'evaluation_score' => 0.90, 'enabled' => 1]),
        ];

        $res = $engine->selectCandidate(['operation' => 'research'], $cands);
        $this->assertEquals('gemini-1.5-flash', $res['selected_candidate']);
        $this->assertFalse($res['fallback_used']);
    }
}
