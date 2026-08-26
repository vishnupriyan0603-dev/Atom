<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomMetaCognitionEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for AtomMetaCognitionEngine (Atom Brain Phase 6).
 */
class AtomMetaCognitionEngineTest extends TestCase
{
    private AtomMetaCognitionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomMetaCognitionEngine(new SecretRedactor());
    }

    public function testEvaluateHighQualityTurn(): void
    {
        $userInput = 'Can you show me how to execute an Active Record query in CodeIgniter 4?';
        $assistantResponse = 'In CodeIgniter 4, you can use the Query Builder: `$db = \Config\Database::connect(); $query = $db->table("users")->where("active", 1)->get();` This ensures automatic parameter escaping and safe query construction.';

        $res = $this->engine->evaluateTurn($userInput, $assistantResponse);

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(85.0, $res['composite_score']);
        $this->assertContains($res['grade'], ['A+', 'A']);
        $this->assertEquals('HIGH_CALIBRATION', $res['meta_verdict']);

        $dims = $res['dimensions'];
        $this->assertGreaterThanOrEqual(90, $dims['factuality']['score']);
        $this->assertGreaterThanOrEqual(90, $dims['persona_consistency']['score']);
        $this->assertGreaterThanOrEqual(90, $dims['conciseness']['score']);
        $this->assertGreaterThanOrEqual(90, $dims['tool_appropriateness']['score']);
        $this->assertEquals(100, $dims['safety_integrity']['score']);
    }

    public function testEvaluateRoboticDisclaimersPenalized(): void
    {
        $userInput = 'Tell me your thoughts on PHP 8.3 vs Node.js.';
        $roboticResponse = 'As an AI language model, I do not have feelings or personal preferences, but PHP 8.3 has JIT and Node.js has V8 engine.';

        $res = $this->engine->evaluateTurn($userInput, $roboticResponse);

        $this->assertTrue($res['success']);
        $this->assertLessThanOrEqual(60, $res['dimensions']['persona_consistency']['score']);
        $this->assertStringContainsString('robotic AI disclaimer', implode(' ', $res['dimensions']['persona_consistency']['notes']));
    }

    public function testMasterTelemetryStructure(): void
    {
        $telemetry = $this->engine->getMasterTelemetry();

        $this->assertTrue($telemetry['success']);
        $this->assertEquals('Atom Brain v6.0-Master', $telemetry['brain_version']);
        $this->assertEquals('OPTIMAL', $telemetry['overall_health']);
        $this->assertGreaterThanOrEqual(90.0, $telemetry['evolution_index']);

        $active = $telemetry['active_subsystems'];
        $this->assertArrayHasKey('phase_1_persona', $active);
        $this->assertArrayHasKey('phase_2_memory', $active);
        $this->assertArrayHasKey('phase_3_reasoner', $active);
        $this->assertArrayHasKey('phase_4_voice', $active);
        $this->assertArrayHasKey('phase_5_planner', $active);
        $this->assertArrayHasKey('phase_6_metacognition', $active);

        foreach ($active as $subsystem) {
            $this->assertEquals('active', $subsystem['status']);
            $this->assertEquals('100%', $subsystem['readiness']);
        }
    }

    public function testEvolveSynapseWeightsCalibration(): void
    {
        $dummyEvals = [
            ['composite_score' => 95.0],
            ['composite_score' => 98.0],
        ];

        $evolveRes = $this->engine->evolveSynapseWeights($dummyEvals);

        $this->assertTrue($evolveRes['success']);
        $this->assertGreaterThanOrEqual(90.0, $evolveRes['updated_weights']['evolution_index']);
        $this->assertGreaterThan(0, $evolveRes['updated_weights']['total_calibrations']);
        $this->assertStringContainsString('Synapse prompt weights calibrated', $evolveRes['evolution_summary']);
    }
}
