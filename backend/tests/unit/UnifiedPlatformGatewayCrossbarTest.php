<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\UnifiedPlatformGatewayCrossbar;
use Atom\Security\SecretRedactor;

/**
 * Phase 50 — UnifiedPlatformGatewayCrossbar unit tests (6 tests).
 */
class UnifiedPlatformGatewayCrossbarTest extends TestCase
{
    private UnifiedPlatformGatewayCrossbar $crossbar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crossbar = new UnifiedPlatformGatewayCrossbar(new SecretRedactor());
    }

    public function testGetPlatformStatusReportsHealthyIndex(): void
    {
        $status = $this->crossbar->getPlatformStatus();

        $this->assertSame('ATOM Autonomous AI Engineering Assistant', $status['platform']);
        $this->assertSame(100.0, $status['health_score']);
        $this->assertSame('OPTIMAL', $status['status']);
        $this->assertGreaterThanOrEqual(10, $status['total_subsystems']);
        $this->assertIsArray($status['subsystems']);
    }

    public function testDispatchVoiceSynthesisCommand(): void
    {
        $res = $this->crossbar->dispatchCommand('synthesize_voice', ['text' => 'Hello Atom']);

        $this->assertTrue($res['success']);
        $this->assertSame('Voice & Formant Shifter', $res['subsystem']);
        $this->assertSame(245.0, $res['data']['f0_hz']);
    }

    public function testDispatchVisionOcrCommand(): void
    {
        $res = $this->crossbar->dispatchCommand('ocr_vision');

        $this->assertTrue($res['success']);
        $this->assertSame('Neural Vision & OCR', $res['subsystem']);
        $this->assertGreaterThan(0, $res['data']['symbols_extracted']);
    }

    public function testDispatchPostQuantumHandshakeCommand(): void
    {
        $res = $this->crossbar->dispatchCommand('quantum_handshake');

        $this->assertTrue($res['success']);
        $this->assertSame('Post-Quantum Cryptography', $res['subsystem']);
        $this->assertSame('NIST_LEVEL_5_PROTECTED', $res['data']['quantum_security']);
    }

    public function testDispatchAbacPolicyEvaluationCommand(): void
    {
        $res = $this->crossbar->dispatchCommand('evaluate_policy');

        $this->assertTrue($res['success']);
        $this->assertSame('ABAC Zero-Trust Firewall', $res['subsystem']);
        $this->assertSame('PERMIT', $res['data']['decision']);
    }

    public function testFallbackCommandRouting(): void
    {
        $res = $this->crossbar->dispatchCommand('custom_unknown_action');

        $this->assertTrue($res['success']);
        $this->assertSame('Autonomous Crossbar Gateway', $res['subsystem']);
    }
}
