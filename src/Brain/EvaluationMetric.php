<?php

namespace Atom\Brain;

class EvaluationMetric
{
    public float $answerQuality;     // 0.0 to 100.0
    public float $retrievalAccuracy; // 0.0 to 100.0
    public float $hallucinationRate; // 0.0 to 100.0 (lower is better)
    public int $latencyMs;           // in milliseconds (lower is better)
    public float $toolSuccessRate;   // 0.0 to 100.0
    public float $userSatisfaction;  // 0.0 to 100.0
    public float $regressionRate;    // 0.0 to 100.0 (lower is better)

    public function __construct(
        float $answerQuality = 80.0,
        float $retrievalAccuracy = 85.0,
        float $hallucinationRate = 2.0,
        int $latencyMs = 250,
        float $toolSuccessRate = 95.0,
        float $userSatisfaction = 88.0,
        float $regressionRate = 1.0
    ) {
        $this->answerQuality = max(0.0, min(100.0, $answerQuality));
        $this->retrievalAccuracy = max(0.0, min(100.0, $retrievalAccuracy));
        $this->hallucinationRate = max(0.0, min(100.0, $hallucinationRate));
        $this->latencyMs = max(1, $latencyMs);
        $this->toolSuccessRate = max(0.0, min(100.0, $toolSuccessRate));
        $this->userSatisfaction = max(0.0, min(100.0, $userSatisfaction));
        $this->regressionRate = max(0.0, min(100.0, $regressionRate));
    }

    /**
     * Computes weighted composite quality score (0.0 to 100.0).
     */
    public function calculateCompositeScore(): float
    {
        $qualityScore     = $this->answerQuality * 0.30;
        $retrievalScore   = $this->retrievalAccuracy * 0.25;
        $toolScore        = $this->toolSuccessRate * 0.20;
        $userScore        = $this->userSatisfaction * 0.15;
        $hallucinationPen = (100.0 - $this->hallucinationRate) * 0.05;
        $regressionPen    = (100.0 - $this->regressionRate) * 0.05;

        $composite = $qualityScore + $retrievalScore + $toolScore + $userScore + $hallucinationPen + $regressionPen;
        return round(max(0.0, min(100.0, $composite)), 2);
    }

    public function toArray(): array
    {
        return [
            'answer_quality'     => $this->answerQuality,
            'retrieval_accuracy' => $this->retrievalAccuracy,
            'hallucination_rate' => $this->hallucinationRate,
            'latency_ms'         => $this->latencyMs,
            'tool_success_rate'  => $this->toolSuccessRate,
            'user_satisfaction'  => $this->userSatisfaction,
            'regression_rate'    => $this->regressionRate,
            'composite_score'    => $this->calculateCompositeScore(),
        ];
    }
}
