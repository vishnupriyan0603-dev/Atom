<?php

use PHPUnit\Framework\TestCase;
use Atom\Testing\TestSynthesizer;
use Atom\Testing\SelfCorrectionEngine;
use Atom\CiCd\PipelineRunner;

/**
 * Phase 29 — CicdSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for Autonomous Testing & CI/CD Pipeline:
 * - Secret redaction in synthesized test code
 * - Secret redaction in synthesized code patches
 * - Secret redaction in CI/CD pipeline stage logs
 * - Mandatory human approval requirement for code mutations
 * - Protection against leaking workspace secret variables
 */
class CicdSecurityPassTest extends TestCase
{
    public function testSecretRedactionInSynthesizedTests(): void
    {
        $synthesizer = new TestSynthesizer();
        $code = "class KeyManager { public function getKey() { return 'sk-1234567890abcdef1234567890abcdef'; } }";
        $res = $synthesizer->synthesizeTest($code, 'KeyManager');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['test_code']);
    }

    public function testSecretRedactionInPatches(): void
    {
        $engine = new SelfCorrectionEngine();
        $faulty = "public function testSecret() { \$key = 'sk-1234567890abcdef1234567890abcdef'; return false; }";
        $res = $engine->synthesizePatch($faulty, "Failed asserting that false is true");

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['patched_code']);
    }

    public function testPipelineOutputRedactsSecrets(): void
    {
        $runner = new PipelineRunner();
        $res = $runner->runPipeline(['security_scan']);

        $json = json_encode($res);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $json);
        $this->assertStringNotContainsString('DB_PASSWORD', $json);
    }

    public function testSynthesizedPatchEnforcesHumanApproval(): void
    {
        $engine = new SelfCorrectionEngine();
        $res = $engine->synthesizePatch("function auth() { return false; }", "Assertion failure");

        $this->assertTrue($res['requires_approval']);
    }

    public function testPipelineTelemetryContainsNoHostCredentials(): void
    {
        $runner = new PipelineRunner();
        $status = $runner->runPipeline();
        $json = json_encode($status);

        $this->assertStringNotContainsString('OPENAI_API_KEY', $json);
        $this->assertStringNotContainsString('GEMINI_API_KEY', $json);
    }
}
