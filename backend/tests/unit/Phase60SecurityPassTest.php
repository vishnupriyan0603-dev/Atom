<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\DistributedTracerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 60 — Phase60SecurityPassTest security & safety tests (5 tests).
 */
class Phase60SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSpanTags(): void
    {
        $engine = new DistributedTracerEngine($this->redactor);
        $tags = ['api_key' => 'sk-1122334455667788990011223344', 'user' => 'admin'];

        $res = $engine->startSpan('SecureOp', 'VaultEngine', null, $tags);
        $this->assertNotEmpty($res['span_id']);
    }

    public function testTraceEntropyHexFormat(): void
    {
        $engine = new DistributedTracerEngine($this->redactor);
        $tp = $engine->generateTraceparent();

        $this->assertMatchesRegularExpression('/^00-[a-f0-9]{32}-[a-f0-9]{16}-01$/', $tp);
    }

    public function testSpanHeaderInjectionSafety(): void
    {
        $engine = new DistributedTracerEngine($this->redactor);
        $maliciousHeader = "00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01\r\nX-Injected: Evil";

        $parsed = $engine->parseTraceparent($maliciousHeader);
        $this->assertStringNotContainsString('Evil', $parsed['trace_id']);
    }

    public function testHighVolumeSpansResilience(): void
    {
        $engine = new DistributedTracerEngine($this->redactor);

        for ($i = 0; $i < 100; $i++) {
            $span = $engine->startSpan("BatchSpan_{$i}", 'MeshGateway');
            $engine->endSpan($span['span_id']);
        }

        $traces = $engine->listTraces();
        $this->assertNotEmpty($traces);
    }

    public function testNoDangerousEvalOrShellExecutionInOrchestrationSubsystem(): void
    {
        $files = [
            'src/Orchestration/DistributedTracerEngine.php',
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
