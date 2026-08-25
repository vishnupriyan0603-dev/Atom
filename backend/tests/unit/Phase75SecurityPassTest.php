<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\IoTTelemetryWatchdogEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 75 — Phase75SecurityPassTest security & safety tests (5 tests).
 */
class Phase75SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInDeviceId(): void
    {
        $engine = new IoTTelemetryWatchdogEngine($this->redactor);
        $engine->registerDevice('sensor_sk-1122334455667788990011223344_node');

        $status = $engine->getFleetStatus();
        $allIds = array_column($status['devices'], 'device_id');

        foreach ($allIds as $id) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $id);
        }
    }

    public function testHighThroughputTelemetryIngestion(): void
    {
        $engine = new IoTTelemetryWatchdogEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->ingestTelemetry("sensor_node_{$i}", [
                'temp_c' => 40.0 + ($i % 30),
                'voltage_v' => 3.7,
                'vibration_g' => 0.1,
            ]);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testFleetHealthPercentageNeverExceeds100(): void
    {
        $engine = new IoTTelemetryWatchdogEngine($this->redactor);
        $status = $engine->getFleetStatus();

        $this->assertLessThanOrEqual(100.0, $status['fleet_health_pct']);
        $this->assertGreaterThanOrEqual(0.0, $status['fleet_health_pct']);
    }

    public function testExtremeSensorMetricValuesSafety(): void
    {
        $engine = new IoTTelemetryWatchdogEngine($this->redactor);
        $res = $engine->ingestTelemetry('extreme_node', [
            'temp_c' => 99999.0,
            'voltage_v' => -50.0,
            'vibration_g' => 1000.0,
        ]);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['anomalies_detected']);
    }

    public function testNoDangerousEvalOrShellExecutionInNetworkSubsystem(): void
    {
        $files = [
            'src/Network/IoTTelemetryWatchdogEngine.php',
            'src/Network/WebRtcFileTransferEngine.php',
            'src/Network/WebhookDispatcherEngine.php',
            'src/Network/WebRTCMeshSignalingHub.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
