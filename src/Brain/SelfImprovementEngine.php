<?php

namespace Atom\Brain;

use Atom\Database\Connection;
use Atom\Security\HumanApprovalGate;
use CodeIgniter\Database\BaseConnection;

class SelfImprovementEngine
{
    private ?Connection $connection;
    private HumanApprovalGate $approvalGate;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection;
        $this->approvalGate = new HumanApprovalGate($connection);
    }

    private function getDb(): BaseConnection
    {
        return \Config\Database::connect();
    }

    /**
     * Log evaluation metrics for an interaction response.
     */
    public function logEvaluation(int $chatId, int $messageId, string $promptVersion, string $modelName, int $ragCount, ?string $feedback, float $accuracyScore, int $latencyMs, int $tokensUsed = 0): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_evaluations'), true)->insert([
            'chat_id'             => $chatId,
            'message_id'          => $messageId,
            'prompt_version'      => $promptVersion,
            'model_name'          => $modelName,
            'rag_retrieval_count' => $ragCount,
            'user_feedback'       => $feedback,
            'accuracy_score'      => $accuracyScore,
            'latency_ms'          => $latencyMs,
            'tokens_used'         => $tokensUsed,
            'created_at'          => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Analyze recent evaluations to detect weakness areas or accuracy drops.
     */
    public function detectFlaws(int $limit = 50): array
    {
        $db = $this->getDb();
        $metrics = $db->table($db->prefixTable('atom_evaluations'), true)
                      ->select('user_feedback, AVG(accuracy_score) as avg_accuracy, AVG(latency_ms) as avg_latency, COUNT(*) as total_count')
                      ->groupBy('user_feedback')
                      ->get()->getResultArray();

        $badCount = 0;
        $totalCount = 0;
        foreach ($metrics as $m) {
            $totalCount += (int)$m['total_count'];
            if ($m['user_feedback'] === 'bad') {
                $badCount += (int)$m['total_count'];
            }
        }

        $errorRate = $totalCount > 0 ? ($badCount / $totalCount) : 0.0;

        return [
            'status'                  => 'success',
            'total_evaluations'       => $totalCount,
            'negative_feedback_count' => $badCount,
            'error_rate'              => round($errorRate, 4),
            'needs_improvement'       => $errorRate > 0.15 || $badCount >= 3,
            'metrics'                 => $metrics
        ];
    }

    /**
     * Create a candidate experiment for prompt or RAG tuning.
     */
    public function createExperiment(string $title, string $targetComponent, array $baselineConfig, array $candidateConfig): int
    {
        $db = $this->getDb();
        $builder = $db->table($db->prefixTable('atom_experiments'), true);

        $inserted = $builder->insert([
            'title'            => $title,
            'target_component' => $targetComponent,
            'baseline_config'  => json_encode($baselineConfig),
            'candidate_config' => json_encode($candidateConfig),
            'status'           => 'running',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s')
        ]);

        if ($inserted) {
            return (int)$db->insertID();
        }
        return 0;
    }

    /**
     * Benchmark an experiment, evaluate candidate score, and queue for human approval if positive.
     */
    public function evaluateExperiment(int $experimentId, float $baselineScore, float $candidateScore): array
    {
        $db = $this->getDb();
        $improvementPct = $baselineScore > 0 ? (($candidateScore - $baselineScore) / $baselineScore) * 100 : 0.0;

        if ($improvementPct >= 5.0) {
            // Passed sandbox benchmark -> Set status to awaiting_approval & trigger HumanApprovalGate
            $db->table($db->prefixTable('atom_experiments'), true)->where('id', $experimentId)->update([
                'baseline_score'  => $baselineScore,
                'candidate_score' => $candidateScore,
                'improvement_pct' => round($improvementPct, 2),
                'status'          => 'awaiting_approval',
                'updated_at'      => date('Y-m-d H:i:s')
            ]);

            $approvalId = $this->approvalGate->requestApproval(
                $experimentId,
                'PROMOTION',
                "Candidate configuration improved score by " . round($improvementPct, 2) . "% (Baseline: $baselineScore, Candidate: $candidateScore)"
            );

            return [
                'status'          => 'awaiting_human_approval',
                'experiment_id'   => $experimentId,
                'approval_id'     => $approvalId,
                'improvement_pct' => round($improvementPct, 2)
            ];
        } else {
            // Failed benchmark -> Set status to failed
            $db->table($db->prefixTable('atom_experiments'), true)->where('id', $experimentId)->update([
                'baseline_score'  => $baselineScore,
                'candidate_score' => $candidateScore,
                'improvement_pct' => round($improvementPct, 2),
                'status'          => 'failed',
                'updated_at'      => date('Y-m-d H:i:s')
            ]);

            return [
                'status'          => 'experiment_failed',
                'experiment_id'   => $experimentId,
                'improvement_pct' => round($improvementPct, 2),
                'message'         => 'Candidate failed to beat baseline threshold of +5%'
            ];
        }
    }
}
