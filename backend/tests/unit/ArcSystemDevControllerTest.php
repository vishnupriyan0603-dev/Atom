<?php

use PHPUnit\Framework\TestCase;
use Atom\Arc\ArcSystemDevController;
use Atom\Arc\ArcMasterController;

require_once dirname(__DIR__, 2) . '/../src/Arc/ArcSystemDevController.php';
require_once dirname(__DIR__, 2) . '/../src/Arc/ArcMasterController.php';

/**
 * Unit tests for ARC THREE — Advanced Developer System & Technical Control Controller (Phase 109).
 */
class ArcSystemDevControllerTest extends TestCase
{
    private ArcSystemDevController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ArcSystemDevController();
    }

    public function testGetPlatformReturnsOsFamily(): void
    {
        $platform = $this->controller->getPlatform();
        $this->assertNotEmpty($platform);
        $this->assertSame(PHP_OS_FAMILY, $platform);
    }

    public function testListProcessesReturnsProcessList(): void
    {
        $res = $this->controller->listProcesses(null, 10);
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('processes', $res);
        $this->assertGreaterThanOrEqual(1, $res['count']);

        $first = $res['processes'][0];
        $this->assertArrayHasKey('pid', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('memory_mb', $first);
    }

    public function testGetProcessDetailsRejectsEmptyInput(): void
    {
        $res = $this->controller->getProcessDetails('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('cannot be empty', $res['error']);
    }

    public function testGetListeningPortsReturnsSocketMap(): void
    {
        $res = $this->controller->getListeningPorts();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('ports', $res);
        $this->assertIsArray($res['ports']);
    }

    public function testCheckPortAvailabilityValidatesBounds(): void
    {
        $invalid = $this->controller->checkPortAvailability(999999);
        $this->assertIsArray($invalid);
        $this->assertFalse($invalid['success']);
        $this->assertStringContainsString('between 1 and 65535', $invalid['error']);

        $valid = $this->controller->checkPortAvailability(65000);
        $this->assertIsArray($valid);
        $this->assertTrue($valid['success']);
        $this->assertIsBool($valid['is_available']);
    }

    public function testDetectDeveloperToolchainDiscoversInstalledRuntimes(): void
    {
        $res = $this->controller->detectDeveloperToolchain();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('toolchain', $res);

        $tc = $res['toolchain'];
        $this->assertArrayHasKey('php', $tc);
        $this->assertTrue($tc['php']['installed']);
        $this->assertArrayHasKey('git', $tc);
    }

    public function testGetHardwareTelemetryReturnsStructuredMetrics(): void
    {
        $res = $this->controller->getHardwareTelemetry();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('telemetry', $res);
        $this->assertArrayHasKey('available', $res);

        $t = $res['telemetry'];
        $this->assertArrayHasKey('cpu', $t);
        $this->assertArrayHasKey('ram', $t);
        $this->assertArrayHasKey('disks', $t);
        if (!$res['available']) {
            $this->assertNull($t['cpu']);
            $this->assertNull($t['ram']);
            $this->assertSame([], $t['disks']);
        }
    }

    public function testGetEnvironmentVariablesReturnsMap(): void
    {
        $res = $this->controller->getEnvironmentVariables();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('variables', $res);
        $this->assertGreaterThan(0, $res['count']);
    }

    public function testMasterControllerDispatchesArcThreeActions(): void
    {
        $master = new ArcMasterController();
        $res = $master->dispatch('sys.ports');
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);

        $toolRes = $master->dispatch('sys.toolchain');
        $this->assertIsArray($toolRes);
        $this->assertTrue($toolRes['success']);

        $state = $master->getSystemState();
        $this->assertArrayHasKey('arc_three', $state);
        $this->assertTrue($state['arc_three']['system_ready']);
    }
}

