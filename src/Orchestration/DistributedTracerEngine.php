<?php

namespace Atom\Orchestration;

use Atom\Security\SecretRedactor;

/**
 * DistributedTracerEngine — Phase 60 (Grand Landmark Milestone)
 * W3C-compliant distributed context propagation and OpenTelemetry span tracer.
 */
class DistributedTracerEngine
{
    private SecretRedactor $redactor;
    private array $traces = [];
    private array $activeSpans = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleTraces();
    }

    /**
     * Generate W3C-compliant traceparent header: 00-{trace_id}-{span_id}-01
     */
    public function generateTraceparent(?string $traceId = null, ?string $spanId = null): string
    {
        $tId = $traceId ?? bin2hex(random_bytes(16));
        $sId = $spanId ?? bin2hex(random_bytes(8));
        return "00-{$tId}-{$sId}-01";
    }

    /**
     * Parse incoming W3C traceparent header.
     */
    public function parseTraceparent(string $traceparent): array
    {
        $parts = explode('-', trim($traceparent));
        if (count($parts) === 4 && $parts[0] === '00') {
            return [
                'valid' => true,
                'version' => $parts[0],
                'trace_id' => $parts[1],
                'parent_id' => $parts[2],
                'flags' => $parts[3],
            ];
        }

        // Fallback new trace
        return [
            'valid' => false,
            'version' => '00',
            'trace_id' => bin2hex(random_bytes(16)),
            'parent_id' => bin2hex(random_bytes(8)),
            'flags' => '01',
        ];
    }

    /**
     * Start a new span in a trace.
     */
    public function startSpan(string $name, string $subsystem, ?string $traceparent = null, array $tags = []): array
    {
        $parsed = $traceparent ? $this->parseTraceparent($traceparent) : $this->parseTraceparent($this->generateTraceparent());
        $spanId = bin2hex(random_bytes(8));
        $startTime = microtime(true);

        $span = [
            'span_id' => $spanId,
            'trace_id' => $parsed['trace_id'],
            'parent_id' => $parsed['parent_id'],
            'name' => $name,
            'subsystem' => $subsystem,
            'start_time' => $startTime,
            'end_time' => null,
            'duration_ms' => null,
            'tags' => $tags,
            'status' => 'IN_PROGRESS',
        ];

        $this->activeSpans[$spanId] = $span;

        return [
            'span_id' => $spanId,
            'trace_id' => $parsed['trace_id'],
            'traceparent' => "00-{$parsed['trace_id']}-{$spanId}-01",
            'span' => $span,
        ];
    }

    /**
     * End an active span and compute duration.
     */
    public function endSpan(string $spanId, string $status = 'OK'): array
    {
        if (!isset($this->activeSpans[$spanId])) {
            return ['success' => false, 'error' => 'Span ID not found'];
        }

        $span = &$this->activeSpans[$spanId];
        $span['end_time'] = microtime(true);
        $span['duration_ms'] = round(($span['end_time'] - $span['start_time']) * 1000, 2);
        $span['status'] = $status;

        $traceId = $span['trace_id'];
        if (!isset($this->traces[$traceId])) {
            $this->traces[$traceId] = [
                'trace_id' => $traceId,
                'root_name' => $span['name'],
                'start_time' => $span['start_time'],
                'spans' => [],
            ];
        }

        $this->traces[$traceId]['spans'][] = $span;
        $finishedSpan = $span;
        unset($this->activeSpans[$spanId]);

        return [
            'success' => true,
            'span' => $finishedSpan,
            'trace_id' => $traceId,
        ];
    }

    /**
     * List all recorded traces.
     */
    public function listTraces(): array
    {
        return array_values($this->traces);
    }

    private function seedSampleTraces(): void
    {
        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';
        $now = microtime(true);

        $this->traces[$traceId] = [
            'trace_id' => $traceId,
            'root_name' => 'ATOM Crossbar Command Dispatch',
            'start_time' => $now,
            'spans' => [
                ['span_id' => '00f067aa0ba902b7', 'trace_id' => $traceId, 'parent_id' => null, 'name' => 'Gateway Ingest', 'subsystem' => 'Orchestration Gateway', 'duration_ms' => 1.2, 'status' => 'OK'],
                ['span_id' => '5fb397be34d23b0f', 'trace_id' => $traceId, 'parent_id' => '00f067aa0ba902b7', 'name' => 'ABAC Zero-Trust Evaluation', 'subsystem' => 'ABAC Firewall', 'duration_ms' => 0.8, 'status' => 'OK'],
                ['span_id' => '325e69d0c644d673', 'trace_id' => $traceId, 'parent_id' => '00f067aa0ba902b7', 'name' => 'Token Bucket Rate Limit Check', 'subsystem' => 'Rate Limiter', 'duration_ms' => 0.4, 'status' => 'OK'],
                ['span_id' => 'a982f1b4c3e87019', 'trace_id' => $traceId, 'parent_id' => '00f067aa0ba902b7', 'name' => 'Spectral Audio Denoising & Formant Shift', 'subsystem' => 'Voice Engine', 'duration_ms' => 4.6, 'status' => 'OK'],
            ],
        ];
    }
}
