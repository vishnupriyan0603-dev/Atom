<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\DistributedTracerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 60 — DistributedTracerEngine unit tests (6 tests).
 */
class DistributedTracerEngineTest extends TestCase
{
    private DistributedTracerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DistributedTracerEngine(new SecretRedactor());
    }

    public function testGenerateW3CTraceparentFormat(): void
    {
        $tp = $this->engine->generateTraceparent();

        $this->assertStringStartsWith('00-', $tp);
        $this->assertStringEndsWith('-01', $tp);

        $parsed = $this->engine->parseTraceparent($tp);
        $this->assertTrue($parsed['valid']);
        $this->assertSame(32, strlen($parsed['trace_id']));
        $this->assertSame(16, strlen($parsed['parent_id']));
    }

    public function testStartAndEndSpanCalculatesDuration(): void
    {
        $start = $this->engine->startSpan('TestOperation', 'VoiceEngine');
        $spanId = $start['span_id'];

        usleep(5000); // 5ms sleep

        $end = $this->engine->endSpan($spanId, 'OK');
        $this->assertTrue($end['success']);
        $this->assertNotNull($end['span']['duration_ms']);
        $this->assertGreaterThan(0.0, $end['span']['duration_ms']);
        $this->assertSame('OK', $end['span']['status']);
    }

    public function testChildSpanInheritsTraceId(): void
    {
        $root = $this->engine->startSpan('RootOperation', 'Gateway');
        $child = $this->engine->startSpan('ChildOperation', 'ABAC', $root['traceparent']);

        $this->assertSame($root['trace_id'], $child['trace_id']);
        $this->assertSame($root['span_id'], $child['span']['parent_id']);
    }

    public function testListTracesReturnsSeededAndRecordedTraces(): void
    {
        $traces = $this->engine->listTraces();

        $this->assertNotEmpty($traces);
        $this->assertArrayHasKey('trace_id', $traces[0]);
        $this->assertArrayHasKey('spans', $traces[0]);
    }

    public function testEndUnknownSpanFailsGracefully(): void
    {
        $res = $this->engine->endSpan('unknown_span_id');
        $this->assertFalse($res['success']);
    }

    public function testParseMalformedTraceparentFallback(): void
    {
        $parsed = $this->engine->parseTraceparent('malformed-header-data');
        $this->assertFalse($parsed['valid']);
        $this->assertSame(32, strlen($parsed['trace_id']));
    }
}
