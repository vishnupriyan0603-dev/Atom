<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\DecouplingSynthesizer;
use Atom\Security\SecretRedactor;

/**
 * Phase 43 — DecouplingSynthesizer unit tests (6 tests).
 */
class DecouplingSynthesizerTest extends TestCase
{
    private DecouplingSynthesizer $synthesizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synthesizer = new DecouplingSynthesizer(new SecretRedactor());
    }

    public function testSynthesizeInterfaceInversion(): void
    {
        $cycle = ['Atom\\Services\\OrderService', 'Atom\\Services\\BillingService', 'Atom\\Services\\OrderService'];
        $result = $this->synthesizer->synthesizeDecoupling($cycle, ['strategy' => 'interface_inversion']);

        $this->assertTrue($result['success']);
        $this->assertSame('interface_inversion', $result['strategy']);
        $this->assertSame('OrderServiceInterface', $result['interface_name']);
        $this->assertStringContainsString('interface OrderServiceInterface', $result['patch_code']);
        $this->assertStringContainsString('implements OrderServiceInterface', $result['patch_code']);
    }

    public function testSynthesizeEventDrivenDecoupling(): void
    {
        $cycle = ['NotificationService', 'UserService', 'NotificationService'];
        $result = $this->synthesizer->synthesizeDecoupling($cycle, ['strategy' => 'event_driven']);

        $this->assertTrue($result['success']);
        $this->assertSame('event_driven', $result['strategy']);
        $this->assertStringContainsString('EventDispatcher::dispatch', $result['patch_code']);
        $this->assertStringContainsString('EventListenerInterface', $result['patch_code']);
    }

    public function testSynthesizeMediatorDecoupling(): void
    {
        $cycle = ['AlphaNode', 'BetaNode', 'AlphaNode'];
        $result = $this->synthesizer->synthesizeDecoupling($cycle, ['strategy' => 'mediator']);

        $this->assertTrue($result['success']);
        $this->assertSame('mediator', $result['strategy']);
        $this->assertStringContainsString('Coordinator', $result['patch_code']);
    }

    public function testRejectInvalidCycleWithSingleNode(): void
    {
        $result = $this->synthesizer->synthesizeDecoupling(['SingleNode']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('at least 2 nodes', $result['error']);
    }

    public function testArchitecturalGainMetrics(): void
    {
        $cycle = ['ServiceA', 'ServiceB', 'ServiceA'];
        $result = $this->synthesizer->synthesizeDecoupling($cycle);

        $this->assertTrue($result['architectural_gain']['cycle_broken']);
        $this->assertSame(50.0, $result['architectural_gain']['coupling_reduction_pct']);
        $this->assertStringContainsString('SOLID', $result['architectural_gain']['adheres_to']);
    }

    public function testClassNamespaceStrippingForCleanInterfaceName(): void
    {
        $cycle = ['Atom\\Module\\Subsystem\\ComplexManager', 'Atom\\Module\\Other\\Helper'];
        $result = $this->synthesizer->synthesizeDecoupling($cycle);

        $this->assertSame('ComplexManagerInterface', $result['interface_name']);
    }
}
