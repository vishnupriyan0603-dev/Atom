<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\EventMeshTopicBrokerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 92 — Phase92SecurityPassTest security & safety tests (5 tests).
 */
class Phase92SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInTopicAndSubscriber(): void
    {
        $engine = new EventMeshTopicBrokerEngine($this->redactor);
        $engine->subscribe('tenant/sk-1122334455667788990011223344/events', 'sub_sk-1122334455667788990011223344');

        $status = $engine->getBrokerStatus();
        $statusJson = json_encode($status);

        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $statusJson);
    }

    public function testHighThroughputEventFanout(): void
    {
        $engine = new EventMeshTopicBrokerEngine($this->redactor);
        for ($i = 0; $i < 50; $i++) {
            $engine->subscribe("telemetry/{$i}/#", "sub_worker_{$i}");
        }

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->publish("telemetry/" . ($i % 50) . "/temperature/celsius", ['val' => $i]);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testNonMatchingPatternNeverReceivesMessage(): void
    {
        $engine = new EventMeshTopicBrokerEngine($this->redactor);
        $engine->subscribe('isolated/channel/private', 'secret_sub');

        $res = $engine->publish('isolated/channel/public', ['test' => true]);
        $this->assertSame(0, $res['matched_subscribers_count']);
    }

    public function testMultiLevelWildcardAtStartMatchesAll(): void
    {
        $engine = new EventMeshTopicBrokerEngine($this->redactor);
        $engine->subscribe('#', 'global_firehose_listener');

        $res = $engine->publish('any/arbitrary/nested/path/to/event', ['data' => 1]);
        $this->assertGreaterThanOrEqual(1, $res['matched_subscribers_count']);
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/EventMeshTopicBrokerEngine.php',
            'src/Network/StreamFrameCompressorEngine.php',
            'src/Network/ReverseProxyLoadBalancerEngine.php',
            'src/Network/WebRtcFileTransferEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
