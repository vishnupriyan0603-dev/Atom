<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\StreamFrameCompressorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 90 Landmark — Phase90SecurityPassTest security & safety tests (5 tests).
 */
class Phase90SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInEncodedPayload(): void
    {
        $engine = new StreamFrameCompressorEngine($this->redactor);
        $encoded = $engine->encodeFrame('Bearer token: sk-1122334455667788990011223344', 'raw');

        $decoded = $engine->decodeFrame($encoded['binary_frame']);
        $this->assertTrue($decoded['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $decoded['payload']);
    }

    public function testHighThroughputWireFrameProcessing(): void
    {
        $engine = new StreamFrameCompressorEngine($this->redactor);
        $payload = str_repeat("Benchmark event stream payload data 12345. ", 10);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $enc = $engine->encodeFrame($payload, 'deflate', 4);
            $engine->decodeFrame($enc['binary_frame']);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testTruncatedFrameRejection(): void
    {
        $engine = new StreamFrameCompressorEngine($this->redactor);
        $res = $engine->decodeFrame("short_frame");

        $this->assertFalse($res['success']);
        $this->assertSame('INVALID_FRAME_LENGTH', $res['error']);
    }

    public function testLengthMismatchPayloadTamperingRejection(): void
    {
        $engine = new StreamFrameCompressorEngine($this->redactor);
        $enc = $engine->encodeFrame("Normal string", 'deflate');

        // Truncate 3 bytes from data payload
        $truncatedData = substr($enc['binary_frame'], 0, -3);
        $decoded = $engine->decodeFrame($truncatedData);

        $this->assertFalse($decoded['success']);
        $this->assertSame('PAYLOAD_LENGTH_MISMATCH', $decoded['error']);
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/StreamFrameCompressorEngine.php',
            'src/Network/ReverseProxyLoadBalancerEngine.php',
            'src/Network/WebRtcFileTransferEngine.php',
            'src/Network/WebRTCMeshSignalingHub.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
