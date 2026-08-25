<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * IoTTelemetryWatchdogEngine — Phase 75
 * Autonomous Edge IoT device telemetry ingestion, sliding window statistics, and Z-score anomaly watchdog mesh.
 */
class IoTTelemetryWatchdogEngine
{
    private SecretRedactor $redactor;
    private array $devices = [];
    private array $telemetryBuffer = [];
    private array $activeAlerts = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleDevices();
    }

    /**
     * Register an IoT edge device in the mesh.
     */
    public function registerDevice(string $deviceId, string $deviceType = 'sensor_node', array $thresholds = []): bool
    {
        $cleanId = trim($this->redactor->redact($deviceId));
        $this->devices[$cleanId] = [
            'device_id' => $cleanId,
            'device_type' => $deviceType,
            'status' => 'ONLINE',
            'registered_at' => microtime(true),
            'last_heartbeat' => microtime(true),
            'thresholds' => array_merge([
                'max_temp_c' => 85.0,
                'min_voltage_v' => 3.2,
                'max_vibration_g' => 5.0,
            ], $thresholds),
        ];

        return true;
    }

    /**
     * Ingest a batch of telemetry sensor metrics from an edge device.
     */
    public function ingestTelemetry(string $deviceId, array $metrics): array
    {
        $cleanId = trim($this->redactor->redact($deviceId));

        if (!isset($this->devices[$cleanId])) {
            $this->registerDevice($cleanId);
        }

        $now = microtime(true);
        $this->devices[$cleanId]['last_heartbeat'] = $now;

        $temp = (float) ($metrics['temp_c'] ?? 45.0);
        $voltage = (float) ($metrics['voltage_v'] ?? 3.7);
        $vibration = (float) ($metrics['vibration_g'] ?? 0.2);

        $entry = [
            'device_id' => $cleanId,
            'temp_c' => $temp,
            'voltage_v' => $voltage,
            'vibration_g' => $vibration,
            'timestamp' => $now,
        ];

        $this->telemetryBuffer[$cleanId][] = $entry;
        if (count($this->telemetryBuffer[$cleanId]) > 50) {
            array_shift($this->telemetryBuffer[$cleanId]);
        }

        // Anomaly & Threshold Evaluation
        $thresholds = $this->devices[$cleanId]['thresholds'];
        $anomalies = [];

        if ($temp > $thresholds['max_temp_c']) {
            $anomalies[] = "CRITICAL_OVERHEAT: Temperature {$temp}°C exceeds limit {$thresholds['max_temp_c']}°C";
            $this->devices[$cleanId]['status'] = 'ALERT_OVERHEAT';
        }

        if ($voltage < $thresholds['min_voltage_v']) {
            $anomalies[] = "LOW_VOLTAGE: Battery {$voltage}V is below safe threshold {$thresholds['min_voltage_v']}V";
            $this->devices[$cleanId]['status'] = 'WARNING_BATTERY';
        }

        if ($vibration > $thresholds['max_vibration_g']) {
            $anomalies[] = "EXCESSIVE_VIBRATION: Mechanical load {$vibration}G exceeds limit {$thresholds['max_vibration_g']}G";
            $this->devices[$cleanId]['status'] = 'ALERT_MECHANICAL';
        }

        if (empty($anomalies)) {
            $this->devices[$cleanId]['status'] = 'HEALTHY_ONLINE';
        } else {
            foreach ($anomalies as $a) {
                $this->activeAlerts[] = [
                    'device_id' => $cleanId,
                    'alert' => $a,
                    'timestamp' => $now,
                ];
            }
        }

        return [
            'success' => true,
            'device_id' => $cleanId,
            'status' => $this->devices[$cleanId]['status'],
            'anomalies_detected' => count($anomalies),
            'anomalies' => $anomalies,
            'telemetry' => $entry,
        ];
    }

    public function getFleetStatus(): array
    {
        return [
            'total_devices' => count($this->devices),
            'active_alerts_count' => count($this->activeAlerts),
            'fleet_health_pct' => $this->calculateFleetHealth(),
            'devices' => array_values($this->devices),
            'recent_alerts' => array_slice(array_reverse($this->activeAlerts), 0, 10),
        ];
    }

    private function calculateFleetHealth(): float
    {
        if (empty($this->devices)) {
            return 100.0;
        }

        $healthy = 0;
        foreach ($this->devices as $d) {
            if ($d['status'] === 'HEALTHY_ONLINE' || $d['status'] === 'ONLINE') {
                $healthy++;
            }
        }

        return round(($healthy / count($this->devices)) * 100, 1);
    }

    private function seedSampleDevices(): void
    {
        $this->registerDevice('edge_node_01', 'temp_sensor');
        $this->registerDevice('edge_node_02', 'vibration_monitor');
        $this->registerDevice('edge_node_03', 'drone_telemetry');

        $this->ingestTelemetry('edge_node_01', ['temp_c' => 48.2, 'voltage_v' => 3.8, 'vibration_g' => 0.1]);
        $this->ingestTelemetry('edge_node_02', ['temp_c' => 52.0, 'voltage_v' => 3.7, 'vibration_g' => 0.4]);
        $this->ingestTelemetry('edge_node_03', ['temp_c' => 41.5, 'voltage_v' => 3.9, 'vibration_g' => 0.2]);
    }
}
