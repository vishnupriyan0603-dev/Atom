<?php

namespace Atom\Telemetry;

class TelemetryMetric
{
    public string $name;
    public float $value;
    public string $type; // counter, gauge, histogram
    public array $tags;
    public string $timestamp;

    public function __construct(
        string $name,
        float $value,
        string $type = 'gauge',
        array $tags = [],
        ?string $timestamp = null
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->type = strtolower($type);
        $this->tags = $tags;
        $this->timestamp = $timestamp ?? date('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'value'     => $this->value,
            'type'      => $this->type,
            'tags'      => $this->tags,
            'timestamp' => $this->timestamp,
        ];
    }
}
