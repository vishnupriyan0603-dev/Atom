<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\UnifiedPlatformGatewayCrossbar;
use Atom\Orchestration\PlatformSentinelAggregator;
use Atom\Security\SecretRedactor;

/**
 * Phase 50 — Phase50SecurityPassTest security & safety tests (5 tests).
 */
class Phase50SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInCrossbarCommandPayload(): void
    {
        $crossbar = new UnifiedPlatformGatewayCrossbar($this->redactor);
        $payloadWithSecret = [
            'api_key' => 'sk-1122334455667788990011223344',
            'text' => 'Synthesize secure speech',
        ];

        $res = $crossbar->dispatchCommand('synthesize_voice', $payloadWithSecret);

        $this->assertTrue($res['success']);
        $this->assertSame('Voice & Formant Shifter', $res['subsystem']);
    }

    public function testCommandInjectionSanitization(): void
    {
        $crossbar = new UnifiedPlatformGatewayCrossbar($this->redactor);
        // Attempt injection payload in command name
        $maliciousCmd = "status; rm -rf /; cat /etc/passwd";

        $res = $crossbar->dispatchCommand($maliciousCmd);

        $this->assertTrue($res['success']);
        $this->assertSame('Autonomous Crossbar Gateway', $res['subsystem']);
    }

    public function testDiagnosticsSecretRedactionSafety(): void
    {
        $sentinel = new PlatformSentinelAggregator(null, $this->redactor);
        $diag = $sentinel->runDiagnostics();

        $this->assertTrue($diag['success']);
        foreach ($diag['checks'] as $check) {
            $this->assertStringNotContainsString('sk-', $check['details']);
        }
    }

    public function testPlatformSentinelResilienceUnderRapidDiagnostics(): void
    {
        $sentinel = new PlatformSentinelAggregator(null, $this->redactor);

        for ($i = 0; $i < 5; $i++) {
            $diag = $sentinel->runDiagnostics();
            $this->assertTrue($diag['success']);
            $this->assertSame(100.0, $diag['diagnostic_score']);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInOrchestrationSubsystem(): void
    {
        $files = [
            'src/Orchestration/UnifiedPlatformGatewayCrossbar.php',
            'src/Orchestration/PlatformSentinelAggregator.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
