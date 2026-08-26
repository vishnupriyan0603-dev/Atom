<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomSituationReasonerEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for AtomSituationReasonerEngine (Atom Brain Phase 3).
 */
class AtomSituationReasonerEngineTest extends TestCase
{
    private AtomSituationReasonerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomSituationReasonerEngine(new SecretRedactor());
    }

    public function testCalculateEmiAccurateAmortization(): void
    {
        // Principal: 150000, 9.5% annual rate, 36 months
        $res = $this->engine->calculateEmi(150000, 9.5, 36);
        $this->assertTrue($res['success']);
        $this->assertEquals(150000, $res['principal']);
        $this->assertGreaterThan(4500, $res['monthly_emi']);
        $this->assertLessThan(5200, $res['monthly_emi']);
        $this->assertNotEmpty($res['assumptions']);
        $this->assertStringContainsString('Monthly EMI:', $res['formatted_summary']);
    }

    public function testCalculateEmiZeroInterestAndValidation(): void
    {
        $resZero = $this->engine->calculateEmi(120000, 0.0, 12);
        $this->assertTrue($resZero['success']);
        $this->assertEquals(10000.0, $resZero['monthly_emi']);
        $this->assertEquals(0.0, $resZero['total_interest']);

        $resInvalid = $this->engine->calculateEmi(-5000, 10, 0);
        $this->assertFalse($resInvalid['success']);
    }

    public function testEvaluateTradeOffSynthesis(): void
    {
        $options = [
            ['name' => 'MySQL 8.0', 'pros' => ['Ubiquitous', 'Fast reads'], 'cons' => ['Complex sharding']],
            ['name' => 'PostgreSQL 16', 'pros' => ['Rich types', 'Extensible'], 'cons' => ['Higher RAM footprint']]
        ];

        $tradeOff = $this->engine->evaluateTradeOff(
            'Primary Transactional DB',
            $options,
            'MySQL 8.0',
            'Optimal match with existing CodeIgniter 4 Active Record ecosystem'
        );

        $this->assertEquals('Primary Transactional DB', $tradeOff['topic']);
        $this->assertEquals('MySQL 8.0', $tradeOff['recommended_option']);
        $this->assertNotEmpty($tradeOff['proactive_suggestions']);
    }

    public function testProactiveFollowUpSuggestions(): void
    {
        $bikeSuggestions = $this->engine->generateProactiveSuggestions('What is the price and EMI for GT 650?');
        $this->assertNotEmpty($bikeSuggestions);
        $this->assertContains('View 3-year EMI breakdown', $bikeSuggestions);

        $phpSuggestions = $this->engine->generateProactiveSuggestions('How to write a unit test in PHP?');
        $this->assertNotEmpty($phpSuggestions);
        $this->assertContains('Run PHPUnit test suite', $phpSuggestions);
    }

    public function testToolNeedEvaluationAndMinimalism(): void
    {
        // Conversational query does NOT need tools (Rule 14 minimalism)
        $evalConv = $this->engine->evaluateToolNeed('Hello, how is your day going?');
        $this->assertFalse($evalConv['needs_tool']);

        // Explicit calculation needs calc tool
        $evalCalc = $this->engine->evaluateToolNeed('calculate 25000 * 1.18 for GST');
        $this->assertTrue($evalCalc['needs_tool']);
        $this->assertEquals('calc', $evalCalc['tool']);

        // System inspection query needs system_inspect tool
        $evalSys = $this->engine->evaluateToolNeed('What is the current server memory usage and php version?');
        $this->assertTrue($evalSys['needs_tool']);
        $this->assertEquals('system_inspect', $evalSys['tool']);
    }

    public function testExecuteSandboxTools(): void
    {
        // 1. Calc tool
        $calcRes = $this->engine->executeTool('calc', ['expression' => '(500 * 12) + 250']);
        $this->assertTrue($calcRes['success']);
        $this->assertEquals(6250.0, $calcRes['result']);

        // 2. System Inspect tool
        $sysRes = $this->engine->executeTool('system_inspect');
        $this->assertTrue($sysRes['success']);
        $this->assertNotEmpty($sysRes['php_version']);

        // 3. Code diagnostics tool
        $diagRes = $this->engine->executeTool('code_diagnostics', ['target' => 'backend']);
        $this->assertTrue($diagRes['success']);
        $this->assertEquals('healthy', $diagRes['status']);

        // 4. Regex test tool
        $regexRes = $this->engine->executeTool('regex_test', [
            'pattern' => '/^[a-z0-9_]+$/i',
            'subject' => 'atom_brain_42'
        ]);
        $this->assertTrue($regexRes['success']);
        $this->assertTrue($regexRes['is_match']);

        // 5. JSON validate tool
        $jsonRes = $this->engine->executeTool('json_validate', [
            'json' => '{"engine": "Atom Brain", "version": 3}'
        ]);
        $this->assertTrue($jsonRes['success']);
        $this->assertTrue($jsonRes['valid']);

        // 6. Invalid tool
        $invalidRes = $this->engine->executeTool('dangerous_bash_cmd');
        $this->assertFalse($invalidRes['success']);
    }

    public function testCalculateFuelVsEvAmortization(): void
    {
        $res = $this->engine->calculateFuelVsEv(35.0, 103.0, 45.0, 7.5, 35.0);
        $this->assertTrue($res['success']);
        $this->assertEquals(35.0, $res['daily_distance_km']);
        $this->assertGreaterThan(0, $res['savings']['annual']);
        $this->assertGreaterThan(50.0, $res['savings']['savings_percentage']);
        $this->assertStringContainsString('EV saves', $res['formatted_summary']);
    }

    public function testCalculateServerCapacitySizing(): void
    {
        $res = $this->engine->calculateServerCapacity(1000, 2.5, 15.0);
        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(4, $res['recommended_architecture']['cpu_cores']);
        $this->assertGreaterThanOrEqual(4, $res['recommended_architecture']['ram_gb']);
        $this->assertStringContainsString('Recommended:', $res['formatted_summary']);
    }
}

