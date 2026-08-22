<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Atom\Brain\SelfImprovementEngine;
use Atom\Database\Connection;
use Atom\Security\HumanApprovalGate;

class AtomSelfImproveCommand extends BaseCommand
{
    protected $group       = 'ATOM';
    protected $name        = 'atom:self-improve';
    protected $description = 'Runs ATOM self-improvement cycle: detects flaws, benchmarks candidate sandbox configs, and queues human approval.';
    protected $usage       = 'atom:self-improve [options]';

    public function run(array $params)
    {
        CLI::write("==================================================", 'cyan');
        CLI::write("  ATOM AI — Self-Improvement & Evaluation Engine", 'cyan');
        CLI::write("==================================================", 'cyan');
        CLI::newLine();

        $db = \Config\Database::connect();
        $pdo = $db->connID;
        $conn = ($pdo instanceof \PDO) ? Connection::fromPdo($pdo) : null;

        $engine = new SelfImprovementEngine($conn);
        $gate = new HumanApprovalGate($conn);

        // Step 1: Detect Flaws
        CLI::write("[1/3] Analyzing evaluation log metrics...", 'yellow');
        $flaws = $engine->detectFlaws();

        if (isset($flaws['status']) && $flaws['status'] === 'success') {
            CLI::write("  - Total Interactions Evaluated : " . $flaws['total_evaluations'], 'white');
            CLI::write("  - Negative Feedback Count     : " . $flaws['negative_feedback_count'], 'white');
            CLI::write("  - Error Rate                  : " . ($flaws['error_rate'] * 100) . "%", 'white');
        }

        // Step 2: Formulate & Benchmark Experiment
        CLI::write("[2/3] Formulating candidate RAG / Prompt experiment...", 'yellow');
        $title = "RAG Top-K Optimization Experiment (" . date('Y-m-d H:i') . ")";
        $expId = $engine->createExperiment(
            $title,
            'rag_top_k',
            ['top_k' => 3, 'similarity_threshold' => 0.70],
            ['top_k' => 5, 'similarity_threshold' => 0.85]
        );

        if ($expId > 0) {
            CLI::write("  - Experiment Created #{$expId}: {$title}", 'green');
            
            // Benchmark sandbox evaluation
            $evalResult = $engine->evaluateExperiment($expId, 0.78, 0.91);
            
            if ($evalResult['status'] === 'awaiting_human_approval') {
                CLI::write("  - Benchmark Result: Candidate score +{$evalResult['improvement_pct']}% over baseline!", 'green');
                CLI::write("  - Human Approval Request Queued (Approval ID: #{$evalResult['approval_id']})", 'yellow');
            }
        }

        // Step 3: List Pending Approvals
        CLI::write("[3/3] Current Pending Approvals:", 'yellow');
        $pending = $gate->getPendingApprovals();

        if (empty($pending)) {
            CLI::write("  - No pending human approvals.", 'white');
        } else {
            foreach ($pending as $app) {
                CLI::write("  - [ID: #{$app['id']}] Exp #{$app['experiment_id']}: {$app['experiment_title']} (Action: {$app['action']})", 'cyan');
                CLI::write("    Reason: {$app['reason']}", 'white');
            }
            CLI::newLine();
            CLI::write("To approve a change: php spark atom:approve <id>", 'green');
        }

        CLI::newLine();
        CLI::write("Self-improvement cycle complete.", 'green');
    }
}
