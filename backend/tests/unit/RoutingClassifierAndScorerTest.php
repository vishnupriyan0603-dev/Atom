<?php

use PHPUnit\Framework\TestCase;
use Atom\Routing\RequestClassifier;
use Atom\Routing\RoutingScorer;
use Atom\Routing\RoutingCandidate;

class RoutingClassifierAndScorerTest extends TestCase
{
    public function testRequestClassifierClassifiesCodingAndToolUseRequests()
    {
        $classifier = new RequestClassifier();

        $c1 = $classifier->classifyRequest(['operation' => 'coding_task']);
        $this->assertEquals('coding', $c1['category']);

        $c2 = $classifier->classifyRequest(['tools' => ['web_search']]);
        $this->assertEquals('tool_use', $c2['category']);
        $this->assertContains('tool_calling', $c2['required_features']);
    }

    public function testRoutingScorerCalculatesCompositeScores()
    {
        $scorer = new RoutingScorer();
        $candidate = new RoutingCandidate([
            'evaluation_score' => 0.95,
            'health_score'     => 1.0,
            'enabled'          => 1,
        ]);

        $score = $scorer->scoreCandidate($candidate, ['category' => 'coding']);
        $this->assertEquals(0.97, $score);
    }
}
