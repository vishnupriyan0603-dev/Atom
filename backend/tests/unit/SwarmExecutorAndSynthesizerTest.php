<?php

use PHPUnit\Framework\TestCase;
use Atom\Swarm\ResultVerifier;
use Atom\Swarm\ConflictResolver;
use Atom\Swarm\Synthesizer;

class SwarmExecutorAndSynthesizerTest extends TestCase
{
    public function testResultVerifierValidatesWorkerOutput()
    {
        $verifier = new ResultVerifier();
        $res = $verifier->verifyResult(['status' => 'completed', 'output' => 'Found evidence A']);

        $this->assertTrue($res['verified']);
        $this->assertEquals(0.90, $res['confidence']);
    }

    public function testConflictResolverResolvesAgentClaimContradiction()
    {
        $resolver = new ConflictResolver();
        $outA = ['output' => 'Fact X', 'confidence' => 0.95];
        $outB = ['output' => 'Fact Y', 'confidence' => 0.80];

        $res = $resolver->resolveConflict($outA, $outB);
        $this->assertTrue($res['resolved']);
        $this->assertEquals('agent_a', $res['winner']);
    }

    public function testSynthesizerCombinesVerifiedOutputs()
    {
        $synthesizer = new Synthesizer();
        $outputs = [
            ['role' => 'researcher', 'output' => 'Evidence item 1'],
            ['role' => 'analyst', 'output' => 'Analysis summary 1'],
        ];

        $result = $synthesizer->synthesize('Competitor Analysis', $outputs);
        $this->assertStringContainsString('Swarm Synthesis Report', $result);
        $this->assertStringContainsString('Evidence item 1', $result);
    }
}
