<?php

use PHPUnit\Framework\TestCase;
use Atom\Desktop\WindowManager;

/**
 * Phase 27 — WindowManager unit tests (5 tests).
 */
class WindowManagerTest extends TestCase
{
    private WindowManager $windowManager;

    protected function setUp(): void
    {
        $this->windowManager = new WindowManager();
    }

    public function testGetActiveWindowReturnsDevContext(): void
    {
        $win = $this->windowManager->getActiveWindow();
        $this->assertArrayHasKey('platform', $win);
        $this->assertArrayHasKey('window_title', $win);
        $this->assertArrayHasKey('application_name', $win);
        $this->assertTrue($win['is_dev_context']);
    }

    public function testGetRunningProcessesReturnsKnownTools(): void
    {
        $res = $this->windowManager->getRunningProcesses();
        $this->assertGreaterThanOrEqual(1, $res['total_detected']);
        $this->assertNotEmpty($res['processes']);
    }

    public function testProcessStructureHasRequiredFields(): void
    {
        $res = $this->windowManager->getRunningProcesses();
        $first = $res['processes'][0];

        $this->assertArrayHasKey('process_name', $first);
        $this->assertArrayHasKey('display_name', $first);
        $this->assertArrayHasKey('status', $first);
        $this->assertArrayHasKey('category', $first);
    }

    public function testPlatformFamilyMatchesRuntime(): void
    {
        $win = $this->windowManager->getActiveWindow();
        $this->assertSame(PHP_OS_FAMILY, $win['platform']);
    }

    public function testWindowTitleIsNonEmptyString(): void
    {
        $win = $this->windowManager->getActiveWindow();
        $this->assertNotEmpty($win['window_title']);
        $this->assertIsString($win['window_title']);
    }
}
