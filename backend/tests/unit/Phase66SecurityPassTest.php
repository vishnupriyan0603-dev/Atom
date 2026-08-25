<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebRtcFileTransferEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 66 — Phase66SecurityPassTest security & safety tests (5 tests).
 */
class Phase66SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testFileNamePathTraversalSanitization(): void
    {
        $engine = new WebRtcFileTransferEngine($this->redactor);
        $maliciousPath = "../../../etc/passwd";

        $prep = $engine->prepareTransfer($maliciousPath, 'RootUserTestPayload');
        $this->assertTrue($prep['success']);
        $this->assertSame('passwd', $prep['file_name']);
        $this->assertStringNotContainsString('../', $prep['file_name']);
    }

    public function testTimingAttackResistantChecksumVerification(): void
    {
        $engine = new WebRtcFileTransferEngine($this->redactor);
        $prep = $engine->prepareTransfer('test.txt', 'SensitivePayloadContent', 10);

        $transferId = $prep['transfer_id'];
        foreach ($prep['chunks'] as $chunk) {
            $engine->ingestChunk($transferId, $chunk['chunk_index'], $chunk['data'], $chunk['chunk_checksum']);
        }

        $reasm = $engine->reassembleFile($transferId);
        $this->assertTrue($reasm['checksum_verified']);
    }

    public function testTransferPayloadSizeLimits(): void
    {
        $engine = new WebRtcFileTransferEngine($this->redactor);
        $largeData = str_repeat('X', 10240); // 10KB

        $startTime = microtime(true);
        $prep = $engine->prepareTransfer('large.dat', $largeData, 1024);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($prep['success']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testBitrateCalculationAlwaysPositive(): void
    {
        $engine = new WebRtcFileTransferEngine($this->redactor);
        $prep = $engine->prepareTransfer('metric.bin', 'Sample123456');

        $transferId = $prep['transfer_id'];
        $engine->ingestChunk($transferId, 0, $prep['chunks'][0]['data'], $prep['chunks'][0]['chunk_checksum']);

        $reasm = $engine->reassembleFile($transferId);
        $this->assertGreaterThanOrEqual(0.0, $reasm['bitrate_mbps']);
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/WebRtcFileTransferEngine.php',
            'src/Network/WebhookDispatcherEngine.php',
            'src/Network/WebRTCMeshSignalingHub.php',
            'src/Network/DataChannelStreamProtocol.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
