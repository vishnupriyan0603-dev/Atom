<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\EvaluationMetric;
use Atom\Brain\EvaluationPipeline;

class EvaluationPipelineTest extends TestCase
{
    public function testEvaluationMetricCompositeScoreCalculation()
    {
        $metric = new EvaluationMetric(
            answerQuality: 90.0,
            retrievalAccuracy: 88.0,
            hallucinationRate: 1.0,
            latencyMs: 120,
            toolSuccessRate: 100.0,
            userSatisfaction: 92.0,
            regressionRate: 0.5
        );

        $composite = $metric->calculateCompositeScore();
        $this->assertGreaterThan(85.0, $composite);
        $this->assertLessThanOrEqual(100.0, $composite);
    }

    public function testPipelinePromotesSuperiorCandidate()
    {
        $pipeline = new EvaluationPipeline(5.0);

        $baseline = new EvaluationMetric(answerQuality: 70.0, retrievalAccuracy: 70.0, toolSuccessRate: 80.0);
        $candidate = new EvaluationMetric(answerQuality: 95.0, retrievalAccuracy: 95.0, toolSuccessRate: 98.0);

        $result = $pipeline->evaluateCandidate($baseline, $candidate);

        $this->assertTrue($result['promoted']);
        $this->assertEquals('awaiting_human_approval', $result['status']);
        $this->assertGreaterThan(5.0, $result['improvement_pct']);
    }

    public function testPipelineBlocksHallucinationSpikeRegression()
    {
        $pipeline = new EvaluationPipeline(5.0);

        $baseline = new EvaluationMetric(answerQuality: 80.0, hallucinationRate: 1.0);
        $candidate = new EvaluationMetric(answerQuality: 95.0, hallucinationRate: 8.0); // 8% hallucination spike

        $result = $pipeline->evaluateCandidate($baseline, $candidate);

        $this->assertFalse($result['promoted']);
        $this->assertEquals('rejected_hallucination_spike', $result['status']);
    }
}
