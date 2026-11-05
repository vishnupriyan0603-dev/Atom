<?php

namespace Atom\DevOps;

class OpenTelemetryTraceCollector
{
    private array $activeTraces = [];

    /**
     * Start a new distributed trace.
     */
    public function startTrace(string $traceName, array $attributes = []): string
    {
        $traceId = bin2hex(random_bytes(16)); // 128-bit W3C TraceID
        $rootSpanId = bin2hex(random_bytes(8)); // 64-bit SpanID

        $this->activeTraces[$traceId] = [
            'trace_id' => $traceId,
            'name' => $traceName,
            'start_time' => microtime(true),
            'attributes' => $attributes,
            'spans' => [
                [
                    'span_id' => $rootSpanId,
                    'parent_id' => null,
                    'name' => 'root.' . $traceName,
                    'start_time' => microtime(true),
                    'end_time' => null,
                    'duration_ms' => null,
                    'attributes' => $attributes,
                ]
            ],
            'status' => 'ACTIVE',
        ];

        return $traceId;
    }

    /**
     * Add a child span to an active trace.
     */
    public function addSpan(string $traceId, string $spanName, callable $action, array $attributes = []): mixed
    {
        if (!isset($this->activeTraces[$traceId])) {
            return $action();
        }

        $spanId = bin2hex(random_bytes(8));
        $rootSpanId = $this->activeTraces[$traceId]['spans'][0]['span_id'];
        $t0 = microtime(true);

        $result = $action();

        $t1 = microtime(true);
        $durationMs = ($t1 - $t0) * 1000.0;

        $this->activeTraces[$traceId]['spans'][] = [
            'span_id' => $spanId,
            'parent_id' => $rootSpanId,
            'name' => $spanName,
            'start_time' => $t0,
            'end_time' => $t1,
            'duration_ms' => round($durationMs, 3),
            'attributes' => $attributes,
        ];

        return $result;
    }

    /**
     * End trace and export waterfall.
     */
    public function finishTrace(string $traceId): ?array
    {
        if (!isset($this->activeTraces[$traceId])) {
            return null;
        }

        $trace = &$this->activeTraces[$traceId];
        $tEnd = microtime(true);
        $trace['end_time'] = $tEnd;
        $trace['total_duration_ms'] = round(($tEnd - $trace['start_time']) * 1000.0, 3);
        $trace['spans'][0]['end_time'] = $tEnd;
        $trace['spans'][0]['duration_ms'] = $trace['total_duration_ms'];
        $trace['status'] = 'COMPLETED';

        return $trace;
    }
}