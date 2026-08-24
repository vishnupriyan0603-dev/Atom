<?php

use PHPUnit\Framework\TestCase;
use Atom\Desktop\DesktopAutomationEngine;

/**
 * Phase 27 — DesktopAutomationEngine unit tests (5 tests).
 */
class DesktopAutomationEngineTest extends TestCase
{
    private DesktopAutomationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DesktopAutomationEngine();
    }

    public function testGetDesktopStateReturnsValidStructure(): void
    {
        $state = $this->engine->getDesktopState();
        $this->assertSame('online', $state['sidecar_status']);
        $this->assertArrayHasKey('active_window', $state);
        $this->assertArrayHasKey('system_info', $state);
        $this->assertArrayHasKey('developer_tools', $state);
    }

    public function testDispatchNotificationSuccess(): void
    {
        $res = $this->engine->dispatchNotification('ATOM Alert', 'Test message content');
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('toast', $res);
        $this->assertSame('ATOM Alert', $res['toast']['title']);
        $this->assertSame('Test message content', $res['toast']['message']);
    }

    public function testExecuteSafeVolumeAction(): void
    {
        $res = $this->engine->executeSafeAction('mute');
        $this->assertTrue($res['success']);
        $this->assertSame('executed', $res['status']);
        $this->assertTrue($res['system_state']['is_muted']);
    }

    public function testExecuteRejectsUnpermittedAction(): void
    {
        $res = $this->engine->executeSafeAction('format_hard_drive');
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('not permitted', $res['error']);
    }

    public function testEngineAccessorsReturnComponents(): void
    {
        $this->assertInstanceOf(\Atom\Desktop\ClipboardIntelligence::class, $this->engine->getClipboard());
        $this->assertInstanceOf(\Atom\Desktop\WindowManager::class, $this->engine->getWindowManager());
        $this->assertInstanceOf(\Atom\Desktop\SystemControlSidecar::class, $this->engine->getSystemControl());
    }
}
