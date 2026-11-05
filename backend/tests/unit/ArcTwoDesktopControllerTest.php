<?php

use PHPUnit\Framework\TestCase;
use Atom\Arc\ArcTwoDesktopController;

require_once dirname(__DIR__, 2) . '/../src/Arc/ArcTwoDesktopController.php';

/**
 * Unit tests for ARC TWO — Desktop / Atom Controller.
 */
class ArcTwoDesktopControllerTest extends TestCase
{
    private ArcTwoDesktopController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ArcTwoDesktopController();
    }

    public function testGetPlatformReturnsOsFamily(): void
    {
        $platform = $this->controller->getPlatform();
        $this->assertNotEmpty($platform);
        $this->assertSame(PHP_OS_FAMILY, $platform);
    }

    public function testOpenApplicationRejectsInvalidName(): void
    {
        $res = $this->controller->openApplication('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Invalid application name', $res['error']);
    }

    public function testTypeTextRejectsEmptyText(): void
    {
        $res = $this->controller->typeText('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('cannot be empty', $res['error']);
    }

    public function testPressKeyRejectsEmptyKey(): void
    {
        $res = $this->controller->pressKey('');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('cannot be empty', $res['error']);
    }

    public function testCloseApplicationRejectsInvalidProcess(): void
    {
        $res = $this->controller->closeApplication('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Invalid process name', $res['error']);
    }

    public function testWaitForWindowRejectsEmptyQuery(): void
    {
        $res = $this->controller->waitForWindow('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Empty window title', $res['error']);
    }

    public function testMilestoneOneExecutesCleanly(): void
    {
        $logs = [];
        $logger = function(string $msg) use (&$logs) {
            $logs[] = $msg;
        };

        $res = $this->controller->runMilestoneOne("Hi Vichu", $logger);
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertSame('milestone_1', $res['action']);
        $this->assertSame('Hi Vichu', $res['typed_text']);

        $this->assertContains('[ARC TWO] Starting...', $logs);
        $this->assertContains('[ARC TWO] Opening Notepad...', $logs);
        $this->assertContains('[ARC TWO] Notepad detected.', $logs);
        $this->assertContains('[ARC TWO] Focusing window...', $logs);
        $this->assertContains('[ARC TWO] Typing: Hi Vichu', $logs);
        $this->assertContains('[ARC TWO] SUCCESS', $logs);

        // Clean up Notepad process
        $this->controller->closeApplication('notepad', true);
    }
}

