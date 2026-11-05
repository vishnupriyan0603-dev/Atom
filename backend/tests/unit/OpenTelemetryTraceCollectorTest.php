<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\OpenTelemetryTraceCollector;

class OpenTelemetryTraceCollectorTest extends TestCase
{
    public function testRecordsCompleteTraceWaterfall(): void
    {
        $tracer = new OpenTelemetryTraceCollector();
        $traceId = $tracer->startTrace('API.Request', ['route' => '/api/v1/chat']);

        $res1 = $tracer->addSpan($traceId, 'auth.jwt_verify', function() {
            return ['user_id' => 1];
        });

        $res2 = $tracer->addSpan($traceId, 'db.query', function() {
            return ['rows' => 5];
        });

        $trace = $tracer->finishTrace($traceId);

        $this->assertEquals('COMPLETED', $trace['status']);
        $this->assertCount(3, $trace['spans']); // root + 2 children
        $this->assertGreaterThanOrEqual(0, $trace['total_duration_ms']);
    }
}