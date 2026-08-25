<?php

namespace App\Controllers\Api;

use Atom\Telemetry\LatencyHeatmapEngine;

/**
 * LatencyHeatmap API Controller — Phase 67
 */
class LatencyHeatmap extends BaseApiController
{
    private static ?LatencyHeatmapEngine $engine = null;

    private function getEngine(): LatencyHeatmapEngine
    {
        if (self::$engine === null) {
            self::$engine = new LatencyHeatmapEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/telemetry/heatmap/matrix
     */
    public function matrix()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getHeatmapMatrix(), 'Latency heatmap matrix generated');
    }

    /**
     * POST /api/telemetry/heatmap/record
     */
    public function record()
    {
        $json = $this->request->getJSON(true) ?? [];
        $subsystem = $json['subsystem'] ?? 'GatewayCrossbar';
        $durationMs = (float) ($json['duration_ms'] ?? 5.2);

        $engine = $this->getEngine();
        $res = $engine->recordLatency($subsystem, $durationMs);

        return $this->respondSuccess($res, 'Latency recorded');
    }

    /**
     * GET /api/telemetry/heatmap/sla
     */
    public function sla()
    {
        $engine = $this->getEngine();
        $matrix = $engine->getHeatmapMatrix();

        return $this->respondSuccess([
            'sla_compliance_pct' => $matrix['sla_compliance_pct'],
            'sla_threshold_ms' => $matrix['sla_threshold_ms'],
            'total_requests' => $matrix['total_requests'],
            'status' => $matrix['sla_compliance_pct'] >= 99.0 ? 'SLA_HONORED' : 'SLA_WARNING',
        ], 'SLA statistics');
    }
}
