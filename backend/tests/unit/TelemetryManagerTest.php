<?php

use PHPUnit\Framework\TestCase;
use Atom\Telemetry\TelemetryManager;

class TelemetryManagerTest extends TestCase
{
    protected function setUp(): void
    {
        TelemetryManager::getInstance()->clear();
    }

    public function testSpanTracingAndDurationTiming()
    {
        $manager = TelemetryManager::getInstance();

        $span = $manager->startSpan('llm_inference', attributes: ['model' => 'ollama-llama3.1']);
        usleep(50000); // 50ms simulation
        $manager->endSpan($span, 'ok');

        $spans = $manager->getCompletedSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals('llm_inference', $spans[0]['name']);
        $this->assertGreaterThanOrEqual(40.0, $spans[0]['duration_ms']);
        $this->assertEquals('ok', $spans[0]['status']);
    }

    public function testMetricRecordingAndAggregation()
    {
        $manager = TelemetryManager::getInstance();

        $manager->recordMetric('request_duration_ms', 120.5, 'gauge');
        $manager->recordMetric('tokens_generated', 450.0, 'counter');

        $metrics = $manager->getMetrics();
        $this->assertCount(2, $metrics);
        $this->assertEquals('request_duration_ms', $metrics[0]['name']);
        $this->assertEquals(120.5, $metrics[0]['value']);
    }
}
