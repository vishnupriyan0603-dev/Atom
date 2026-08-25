<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioDuplexStreamSession;
use Atom\Voice\RealtimeFormantShifterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 46 — AudioDuplexStreamSession unit tests (6 tests).
 */
class AudioDuplexStreamSessionTest extends TestCase
{
    private AudioDuplexStreamSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new AudioDuplexStreamSession('test_session_01', new RealtimeFormantShifterEngine(), new SecretRedactor(), 50);
    }

    public function testProcessIngressFrameAndIncrementCounter(): void
    {
        $frame = array_fill(0, 128, 0.2);
        $result = $this->session->processIngressFrame($frame);

        $this->assertTrue($result['success']);
        $this->assertSame('test_session_01', $result['session_id']);
        $this->assertSame(1, $result['frame_id']);
        $this->assertNotEmpty($result['fft_spectrum']);
    }

    public function testBargeInDetectionOnHighEnergyVoice(): void
    {
        $loudVoiceFrame = array_fill(0, 128, 0.85); // High energy > 0.08
        $result = $this->session->processIngressFrame($loudVoiceFrame);

        $this->assertTrue($result['barge_in_triggered']);
    }

    public function testSilenceDoesNotTriggerBargeIn(): void
    {
        $silentFrame = array_fill(0, 128, 0.001);
        $result = $this->session->processIngressFrame($silentFrame);

        $this->assertFalse($result['barge_in_triggered']);
    }

    public function testRingBufferMaxCapacityRetention(): void
    {
        for ($i = 0; $i < 70; $i++) {
            $this->session->processIngressFrame([0.1, 0.2]);
        }

        $telemetry = $this->session->getSessionTelemetry();
        $this->assertSame(70, $telemetry['total_frames_processed']);
        $this->assertLessThanOrEqual(50, $telemetry['ring_buffer_size']);
    }

    public function testSessionTelemetryJitterAndLatency(): void
    {
        $this->session->processIngressFrame([0.2, 0.3]);
        $telemetry = $this->session->getSessionTelemetry();

        $this->assertSame('test_session_01', $telemetry['session_id']);
        $this->assertGreaterThan(0.0, $telemetry['estimated_latency_ms']);
        $this->assertSame(0.0, $telemetry['packet_loss_pct']);
        $this->assertArrayHasKey('formant_parameters', $telemetry);
    }

    public function testGetSessionIdAndShifterInstance(): void
    {
        $this->assertSame('test_session_01', $this->session->getSessionId());
        $this->assertInstanceOf(RealtimeFormantShifterEngine::class, $this->session->getShifter());
    }
}
