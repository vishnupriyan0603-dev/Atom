<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomSituationReasonerEngine;
use Atom\Security\SecretRedactor;

/**
 * Security and safety test suite for Atom Brain Phase 3 (Situation Reasoner & Tool Sandbox).
 */
class AtomBrainPhase3SecurityPassTest extends TestCase
{
    private AtomSituationReasonerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomSituationReasonerEngine(new SecretRedactor());
    }

    public function testCalcSandboxCodeInjectionDefense(): void
    {
        // Attempt arbitrary function execution in calc
        $resPhpInfo = $this->engine->executeTool('calc', ['expression' => 'phpinfo()']);
        $this->assertFalse($resPhpInfo['success']);

        $resShell = $this->engine->executeTool('calc', ['expression' => 'system("ls")']);
        $this->assertFalse($resShell['success']);

        $resFile = $this->engine->executeTool('calc', ['expression' => 'file_get_contents(".env")']);
        $this->assertFalse($resFile['success']);

        $resSpecial = $this->engine->executeTool('calc', ['expression' => '`whoami`']);
        $this->assertFalse($resSpecial['success']);
    }

    public function testSecretRedactionInReasoningPrompts(): void
    {
        $secretPrompt = 'Calculate EMI for secret = "SuperSecretVal" at 10% for 24 months with sk-proj-1234567890abcdef1234567890abcdef';
        $res = $this->engine->reason($secretPrompt);

        $this->assertIsArray($res);
        $this->assertStringNotContainsString('sk-proj-1234567890abcdef1234567890abcdef', $res['query']);
    }

    public function testHighThroughputReasoningStress(): void
    {
        $start = microtime(true);
        for ($i = 1; $i <= 50; $i++) {
            $this->engine->calculateEmi(100000 + ($i * 1000), 8.5, 24);
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.5, $elapsed, 'High throughput EMI reasoning should execute within 1.5s');
    }

    public function testToolWhitelistIntegrity(): void
    {
        $tools = $this->engine->getAvailableTools();
        $this->assertIsArray($tools);
        $this->assertCount(5, $tools);

        foreach ($tools as $t) {
            $this->assertNotEmpty($t['name']);
            $this->assertNotEmpty($t['description']);
            $this->assertContains($t['safety_level'], ['safe_sandbox', 'read_only']);
        }
    }

    public function testMalformedCalculationResilience(): void
    {
        $resDivideZero = $this->engine->executeTool('calc', ['expression' => '100 / 0']);
        // Either handled with error or safely returning non-fatal output
        $this->assertIsArray($resDivideZero);

        $resEmpty = $this->engine->executeTool('calc', ['expression' => '']);
        $this->assertFalse($resEmpty['success']);
    }
}
