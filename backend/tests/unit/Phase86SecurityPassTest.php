<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\ReverseProxyLoadBalancerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 86 — Phase86SecurityPassTest security & safety tests (5 tests).
 */
class Phase86SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInUpstreamNodeAndHost(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine($this->redactor);
        $engine->registerUpstream('node_sk-1122334455667788990011223344_alpha', '10.0.sk-1122334455667788990011223344.1');

        $status = $engine->getUpstreamStatus();
        foreach ($status['upstreams'] as $u) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $u['node_id']);
        }
    }

    public function testHighThroughputProxyDispatch(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->routeRequest("192.168.1." . ($i % 250), "/api/v1/resource_{$i}");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testForwardedHeadersAlwaysPresent(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine($this->redactor);
        $res = $engine->routeRequest('10.20.30.40');

        $this->assertTrue($res['success']);
        $this->assertSame('10.20.30.40', $res['headers_injected']['X-Forwarded-For']);
        $this->assertSame('https', $res['headers_injected']['X-Forwarded-Proto']);
    }

    public function testWeightClampingBetweenOneAndHundred(): void
    {
        $engine = new ReverseProxyLoadBalancerEngine($this->redactor);
        $engine->registerUpstream('clamped_node', '127.0.0.1', 8080, 9999);

        $status = $engine->getUpstreamStatus();
        $map = array_column($status['upstreams'], null, 'node_id');
        $this->assertSame(100, $map['clamped_node']['weight']);
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/ReverseProxyLoadBalancerEngine.php',
            'src/Network/WebRtcFileTransferEngine.php',
            'src/Network/WebRTCMeshSignalingHub.php',
            'src/Network/DataChannelStreamProtocol.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
