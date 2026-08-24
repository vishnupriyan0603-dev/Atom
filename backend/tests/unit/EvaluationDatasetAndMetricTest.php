<?php

use PHPUnit\Framework\TestCase;
use Atom\Evaluation\MetricEngine;

class EvaluationDatasetAndMetricTest extends TestCase
{
    public function testMetricEngineEvaluatesExactMatch()
    {
        $engine = new MetricEngine();

        $case = ['evaluation_type' => 'exact_match', 'expected_output' => 'Hello World'];
        $res = $engine->evaluateCase($case, 'Hello World');

        $this->assertTrue($res['passed']);
        $this->assertEquals(1.0, $res['score']);
    }

    public function testMetricEngineEvaluatesSemanticMatch()
    {
        $engine = new MetricEngine();

        $case = ['evaluation_type' => 'semantic_match', 'expected_output' => 'Summary text'];
        $res = $engine->evaluateCase($case, 'A valid summary output text');

        $this->assertTrue($res['passed']);
        $this->assertEquals(0.95, $res['score']);
    }
}
