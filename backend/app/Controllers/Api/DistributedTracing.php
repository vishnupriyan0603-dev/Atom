<?php

namespace App\Controllers\Api;

use Atom\Orchestration\DistributedTracerEngine;

/**
 * DistributedTracing API Controller — Phase 60
 */
class DistributedTracing extends BaseApiController
{
    private static ?DistributedTracerEngine $engine = null;

    private function getEngine(): DistributedTracerEngine
    {
        if (self::$engine === null) {
            self::$engine = new DistributedTracerEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/tracing/traces
     */
    public function listTraces()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'total_traces' => count($engine->listTraces()),
            'traces' => $engine->listTraces(),
            'standard' => 'W3C Distributed Tracing + OpenTelemetry 1.28.0',
        ], 'Distributed traces retrieved');
    }

    /**
     * POST /api/tracing/spans/start
     */
    public function startSpan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $name = $json['name'] ?? 'SpanOperation';
        $subsystem = $json['subsystem'] ?? 'CrossbarGateway';
        $traceparent = $json['traceparent'] ?? null;
        $tags = $json['tags'] ?? [];

        $engine = $this->getEngine();
        $span = $engine->startSpan($name, $subsystem, $traceparent, $tags);

        return $this->respondSuccess($span, 'Trace span started');
    }

    /**
     * POST /api/tracing/spans/end
     */
    public function endSpan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $spanId = $json['span_id'] ?? '';
        $status = $json['status'] ?? 'OK';

        $engine = $this->getEngine();
        $res = $engine->endSpan($spanId, $status);

        return $this->respondSuccess($res, 'Trace span finished');
    }
}
