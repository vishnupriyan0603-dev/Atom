<?php

namespace Atom\Telemetry;

class Span
{
    public string $name;
    public string $traceId;
    public string $spanId;
    public ?string $parentSpanId;
    public array $attributes;
    public float $startTime;
    public ?float $endTime = null;
    public string $status; // ok, error, unset

    public function __construct(
        string $name,
        ?string $traceId = null,
        ?string $parentSpanId = null,
        array $attributes = []
    ) {
        $this->name = $name;
        $this->traceId = $traceId ?? uniqid('trace_', true);
        $this->spanId = uniqid('span_', true);
        $this->parentSpanId = $parentSpanId;
        $this->attributes = $attributes;
        $this->startTime = microtime(true);
        $this->status = 'unset';
    }

    public function end(string $status = 'ok'): void
    {
        $this->endTime = microtime(true);
        $this->status = $status;
    }

    public function getDurationMs(): float
    {
        $end = $this->endTime ?? microtime(true);
        return round(($end - $this->startTime) * 1000, 2);
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'trace_id'       => $this->traceId,
            'span_id'        => $this->spanId,
            'parent_span_id' => $this->parentSpanId,
            'attributes'     => $this->attributes,
            'start_time'     => $this->startTime,
            'end_time'       => $this->endTime,
            'duration_ms'    => $this->getDurationMs(),
            'status'         => $this->status,
        ];
    }
}
