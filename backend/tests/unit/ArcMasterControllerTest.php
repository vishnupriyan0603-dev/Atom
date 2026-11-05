<?php

use PHPUnit\Framework\TestCase;
use Atom\Arc\ArcMasterController;
use Atom\Arc\ArcOneGitController;
use Atom\Arc\ArcTwoDesktopController;

require_once dirname(__DIR__, 2) . '/../src/Arc/ArcOneGitController.php';
require_once dirname(__DIR__, 2) . '/../src/Arc/ArcTwoDesktopController.php';
require_once dirname(__DIR__, 2) . '/../src/Arc/ArcMasterController.php';

/**
 * Unit tests for ARC Master Controller / Orchestrator.
 */
class ArcMasterControllerTest extends TestCase
{
    private ArcMasterController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ArcMasterController();
    }

    public function testSubControllersAccessible(): void
    {
        $this->assertInstanceOf(ArcOneGitController::class, $this->controller->arcOne());
        $this->assertInstanceOf(ArcTwoDesktopController::class, $this->controller->arcTwo());
    }

    public function testGetSystemStateReturnsAggregatedReport(): void
    {
        $state = $this->controller->getSystemState();
        $this->assertIsArray($state);
        $this->assertSame('ARC Master Orchestrator', $state['controller']);
        $this->assertArrayHasKey('arc_one', $state);
        $this->assertArrayHasKey('arc_two', $state);
        $this->assertTrue($state['arc_one']['git_ready']);
        $this->assertArrayHasKey('total_dispatches', $state);
    }

    public function testDispatchGitStatusDotNotation(): void
    {
        $res = $this->controller->dispatch('git.status');
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertSame('status', $res['action']);
        $this->assertArrayHasKey('branch', $res);

        $audit = $this->controller->getAuditLog();
        $this->assertCount(1, $audit);
        $this->assertSame('arc_one', $audit[0]['target']);
        $this->assertSame('status', $audit[0]['action']);
    }

    public function testDispatchDesktopActionsDotNotation(): void
    {
        $res = $this->controller->dispatch('desktop.close_application', ['process' => 'notepad_nonexistent_test']);
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);

        $audit = $this->controller->getAuditLog();
        $this->assertCount(1, $audit);
        $this->assertSame('arc_two', $audit[0]['target']);
    }

    public function testDispatchRejectsUnknownTarget(): void
    {
        $res = $this->controller->dispatch('unknown_target', 'test_action');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Unknown ARC component target', $res['error']);
    }

    public function testDispatchRejectsUnknownArcOneAction(): void
    {
        $res = $this->controller->dispatch('arc_one', 'unknown_git_action');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString("Unknown ARC ONE action", $res['error']);
    }

    public function testDispatchRejectsUnknownArcTwoAction(): void
    {
        $res = $this->controller->dispatch('arc_two', 'unknown_desktop_action');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString("Unknown ARC TWO action", $res['error']);
    }
}

