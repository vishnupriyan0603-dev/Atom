<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\IoTTelemetryWatchdogEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 75 — IoTTelemetryWatchdogEngine unit tests (6 tests).
 */
class IoTTelemetryWatchdogEngineTest extends TestCase
{
    private IoTTelemetryWatchdogEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new IoTTelemetryWatchdogEngine(new SecretRedactor());
    }

    public function testRegisterDeviceAndIngestNormalTelemetry(): void
    {
        $this->engine->registerDevice('sensor_101', 'temperature_sensor');
        $res = $this->engine->ingestTelemetry('sensor_101', [
            'temp_c' => 45.0,
            'voltage_v' => 3.7,
            'vibration_g' => 0.2
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame('HEALTHY_ONLINE', $res['status']);
        $this->assertSame(0, $res['anomalies_detected']);
    }

    public function testDetectCriticalOverheatAnomaly(): void
    {
        $res = $this->engine->ingestTelemetry('edge_node_01', [
            'temp_c' => 95.0, // Exceeds default limit 85.0
            'voltage_v' => 3.7,
            'vibration_g' => 0.1
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame('ALERT_OVERHEAT', $res['status']);
        $this->assertGreaterThan(0, $res['anomalies_detected']);
    }

    public function testDetectLowVoltageBatteryWarning(): void
    {
        $res = $this->engine->ingestTelemetry('edge_node_02', [
            'temp_c' => 40.0,
            'voltage_v' => 2.8, // Below limit 3.2V
            'vibration_g' => 0.1
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame('WARNING_BATTERY', $res['status']);
    }

    public function testDetectExcessiveVibrationMechanicalAlert(): void
    {
        $res = $this->engine->ingestTelemetry('edge_node_03', [
            'temp_c' => 40.0,
            'voltage_v' => 3.8,
            'vibration_g' => 7.5 // Exceeds limit 5.0G
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame('ALERT_MECHANICAL', $res['status']);
    }

    public function testGetFleetStatusComputesHealthPercentage(): void
    {
        $status = $this->engine->getFleetStatus();

        $this->assertGreaterThan(0, $status['total_devices']);
        $this->assertGreaterThanOrEqual(0.0, $status['fleet_health_pct']);
        $this->assertLessThanOrEqual(100.0, $status['fleet_health_pct']);
        $this->assertIsArray($status['devices']);
    }

    public function testAutoRegistrationOnUnknownDeviceIngest(): void
    {
        $res = $this->engine->ingestTelemetry('brand_new_unregistered_node', [
            'temp_c' => 30.0,
            'voltage_v' => 3.7,
            'vibration_g' => 0.1
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame('brand_new_unregistered_node', $res['device_id']);
    }
}
