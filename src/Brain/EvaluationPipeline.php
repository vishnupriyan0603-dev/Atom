<?php

namespace Atom\Brain;

class EvaluationPipeline
{
    private float $improvementThresholdPct;

    public function __construct(float $improvementThresholdPct = 5.0)
    {
        $this->improvementThresholdPct = $improvementThresholdPct;
    }

    /**
     * Evaluates a candidate configuration against production baseline across all 7 metrics.
     */
    public function evaluateCandidate(EvaluationMetric $baseline, EvaluationMetric $candidate): array
    {
        $baselineScore = $baseline->calculateCompositeScore();
        $candidateScore = $candidate->calculateCompositeScore();

        // 1. Strict Regression Checks
        if ($candidate->hallucinationRate > $baseline->hallucinationRate + 1.0) {
            return [
                'promoted'         => false,
                'status'           => 'rejected_hallucination_spike',
                'reason'           => 'Candidate exhibited elevated hallucination rate compared to baseline.',
                'baseline_score'   => $baselineScore,
                'candidate_score'  => $candidateScore,
                'improvement_pct'  => 0.0,
            ];
        }

        if ($candidate->regressionRate > $baseline->regressionRate + 1.0) {
            return [
                'promoted'         => false,
                'status'           => 'rejected_regression_spike',
                'reason'           => 'Candidate exhibited elevated regression failure rate.',
                'baseline_score'   => $baselineScore,
                'candidate_score'  => $candidateScore,
                'improvement_pct'  => 0.0,
            ];
        }

        // 2. Threshold benchmark test (+5% improvement requirement)
        $improvementPct = $baselineScore > 0 ? (($candidateScore - $baselineScore) / $baselineScore) * 100 : 0.0;
        $improvementPct = round($improvementPct, 2);

        if ($improvementPct >= $this->improvementThresholdPct) {
            return [
                'promoted'         => true,
                'status'           => 'awaiting_human_approval',
                'reason'           => "Candidate achieved +{$improvementPct}% composite score improvement over baseline.",
                'baseline_score'   => $baselineScore,
                'candidate_score'  => $candidateScore,
                'improvement_pct'  => $improvementPct,
            ];
        }

        return [
            'promoted'         => false,
            'status'           => 'failed_improvement_threshold',
            'reason'           => "Candidate improvement (+{$improvementPct}%) failed to beat threshold of +{$this->improvementThresholdPct}%.",
            'baseline_score'   => $baselineScore,
            'candidate_score'  => $candidateScore,
            'improvement_pct'  => $improvementPct,
        ];
    }
}
