<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebhookDlqReplayGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 97 — Phase97SecurityPassTest security & safety tests (5 tests).
 */
class Phase97SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInTargetUrl(): void
    {
        $engine = new WebhookDlqReplayGovernorEngine($this->redactor);
        $res = $engine->enqueue('https://api.test.io/sk-1122334455667788990011223344/webhook', ['data' => 1]);

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['item']['target_url']);
    }

    public function testBackoffNeverExceedsMaximumCeiling(): void
    {
        $engine = new WebhookDlqReplayGovernorEngine($this->redactor);
        $extremeDelay = $engine->calculateBackoff(50); // huge attempt number

        $this->assertLessThanOrEqual(300.0, $extremeDelay);
    }

    public function testHighThroughputDlqEnqueueAndReplay(): void
    {
        $engine = new WebhookDlqReplayGovernorEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->enqueue("https://api.test.io/hook/{$i}", ['idx' => $i]);
        }
        $engine->replayPending(true);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testPermanentlyDeadItemsNeverReplayed(): void
    {
        $engine = new WebhookDlqReplayGovernorEngine($this->redactor);
        $res = $engine->enqueue('https://api.test.io/dead', ['id' => 999], 'ERR_CRITICAL', 5);

        $this->assertSame('PERMANENTLY_DEAD', $res['item']['status']);

        $replayRes = $engine->replayPending(true);
        foreach ($replayRes['replayed_items'] as $item) {
            $this->assertNotSame('https://api.test.io/dead', $item['target_url']);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/WebhookDlqReplayGovernorEngine.php',
            'src/Network/EventMeshTopicBrokerEngine.php',
            'src/Network/StreamFrameCompressorEngine.php',
            'src/Network/ReverseProxyLoadBalancerEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
