<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomMetaCognitionEngine;
use Atom\Security\SecretRedactor;

/**
 * Security and safety pass test suite for Atom Brain Phase 6 (Autonomous Meta-Cognition & Master Telemetry).
 */
class AtomBrainPhase6SecurityPassTest extends TestCase
{
    private AtomMetaCognitionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomMetaCognitionEngine(new SecretRedactor());
    }

    public function testSecretRedactionInMetaCognitiveEvaluation(): void
    {
        $rawSecret = 'sk-proj-topsecret1234567890abcdef';
        $secretInput = "Tell me about token {$rawSecret}";
        $secretResponse = "Your token is {$rawSecret} and password is \"TopSecret999!\"";

        $res = $this->engine->evaluateTurn($secretInput, $secretResponse);

        $this->assertTrue($res['success']);
        // Verify raw secret was completely redacted before evaluation
        $this->assertStringNotContainsString($rawSecret, json_encode($res));
        $this->assertEquals(100, $res['dimensions']['safety_integrity']['score']);
    }

    public function testHighThroughputMetaEvaluation(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->engine->evaluateTurn("User query turn {$i}", "Assistant concise response {$i} for benchmarking.");
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.5, $elapsed, '50 meta-cognitive turn evaluations should complete in under 1.5s');
    }

    public function testEmptyTurnHandling(): void
    {
        $emptyRes = $this->engine->evaluateTurn('', '');
        $this->assertFalse($emptyRes['success']);
    }

    public function testEvolutionWeightBoundsIntegrity(): void
    {
        $res = $this->engine->evolveSynapseWeights([]);
        $this->assertTrue($res['success']);
        $index = $res['updated_weights']['evolution_index'];

        $this->assertGreaterThanOrEqual(0.0, $index);
        $this->assertLessThanOrEqual(100.0, $index);
    }
}
