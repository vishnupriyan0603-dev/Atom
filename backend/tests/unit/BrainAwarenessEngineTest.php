<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\AwarenessEngine;
use Atom\Brain\Device\DeviceAbstraction;

/**
 * Phase 23 — AwarenessEngine unit tests (5 tests).
 */
class BrainAwarenessEngineTest extends TestCase
{
    private AwarenessEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new AwarenessEngine(sys_get_temp_dir());
    }

    public function testEnvironmentDataContainsRequiredKeys(): void
    {
        $data = $this->engine->getEnvironmentData();
        $this->assertArrayHasKey('time_ist',     $data);
        $this->assertArrayHasKey('day_of_week',  $data);
        $this->assertArrayHasKey('time_of_day',  $data);
        $this->assertArrayHasKey('php_version',  $data);
        $this->assertArrayHasKey('file_count',   $data);
    }

    public function testPhpVersionMatchesRuntime(): void
    {
        $data = $this->engine->getEnvironmentData();
        $this->assertSame(PHP_VERSION, $data['php_version']);
    }

    public function testEnvironmentBlockIsNonEmpty(): void
    {
        $block = $this->engine->getEnvironmentBlock();
        $this->assertStringContainsString('AWARENESS', $block);
        $this->assertStringContainsString('Time (IST)', $block);
    }

    public function testDeviceDetectionReturnsValidType(): void
    {
        $device = new DeviceAbstraction('cli');
        $this->assertSame('cli', $device->getDeviceType());

        $webDevice = new DeviceAbstraction('web');
        $this->assertSame('web', $webDevice->getDeviceType());
    }

    public function testTimeOfDayIsValidCategory(): void
    {
        $data = $this->engine->getEnvironmentData();
        $validTods = ['Morning', 'Afternoon', 'Evening', 'Night'];
        $this->assertContains($data['time_of_day'], $validTods);
    }
}
