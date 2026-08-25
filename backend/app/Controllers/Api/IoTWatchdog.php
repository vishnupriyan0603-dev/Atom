<?php

namespace App\Controllers\Api;

use Atom\Network\IoTTelemetryWatchdogEngine;

/**
 * IoTWatchdog API Controller — Phase 75
 */
class IoTWatchdog extends BaseApiController
{
    private static ?IoTTelemetryWatchdogEngine $engine = null;

    private function getEngine(): IoTTelemetryWatchdogEngine
    {
        if (self::$engine === null) {
            self::$engine = new IoTTelemetryWatchdogEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/network/iot/fleet-status
     */
    public function fleetStatus()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getFleetStatus(), 'IoT fleet status retrieved');
    }

    /**
     * POST /api/network/iot/ingest
     */
    public function ingest()
    {
        $json = $this->request->getJSON(true) ?? [];
        $deviceId = $json['device_id'] ?? 'edge_node_01';
        $metrics = $json['metrics'] ?? ['temp_c' => 50.0, 'voltage_v' => 3.7, 'vibration_g' => 0.3];

        $engine = $this->getEngine();
        $res = $engine->ingestTelemetry($deviceId, $metrics);

        return $this->respondSuccess($res, 'IoT telemetry ingested');
    }

    /**
     * POST /api/network/iot/register-device
     */
    public function registerDevice()
    {
        $json = $this->request->getJSON(true) ?? [];
        $deviceId = $json['device_id'] ?? 'sensor_node_' . bin2hex(random_bytes(3));
        $type = $json['device_type'] ?? 'edge_sensor';
        $thresholds = $json['thresholds'] ?? [];

        $engine = $this->getEngine();
        $ok = $engine->registerDevice($deviceId, $type, $thresholds);

        return $this->respondSuccess(['registered' => $ok, 'device_id' => $deviceId], 'Device registered');
    }
}
