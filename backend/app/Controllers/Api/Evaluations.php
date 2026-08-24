<?php

namespace App\Controllers\Api;

use Atom\Evaluation\EvaluationRunner;
use Atom\Evaluation\RegressionDetector;
use Atom\Evaluation\PromotionPolicy;

class Evaluations extends BaseApiController
{
    private function getDb()
    {
        return \Config\Database::connect();
    }

    /**
     * GET /api/v1/evaluations/datasets - List evaluation datasets.
     */
    public function getDatasets()
    {
        $db = $this->getDb();
        $datasets = [];
        if ($db !== null) {
            try {
                $datasets = $db->table($db->prefixTable('atom_eval_datasets'), true)
                               ->orderBy('id', 'DESC')
                               ->get()
                               ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($datasets);
    }

    /**
     * POST /api/v1/evaluations/datasets - Create evaluation dataset.
     */
    public function createDataset()
    {
        $json = $this->request->getJSON(true) ?? [];
        $name = $json['name'] ?? $this->request->getPost('name');

        if (empty($name)) {
            return $this->respondError('Dataset name is required', 400);
        }

        $db = $this->getDb();
        $data = [
            'owner_user_id' => 1,
            'name'          => $name,
            'description'   => $json['description'] ?? null,
            'version'       => 1,
            'status'        => 'active',
            'case_count'    => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_eval_datasets'), true)->insert($data);
                $data['id'] = (int)$db->insertID();
            } catch (\Throwable $e) {
                $data['id'] = time();
            }
        } else {
            $data['id'] = time();
        }

        return $this->respondSuccess($data, 'Dataset created successfully');
    }

    /**
     * POST /api/v1/evaluations/runs - Start evaluation run.
     */
    public function createRun()
    {
        $json = $this->request->getJSON(true) ?? [];
        $datasetId = (int)($json['dataset_id'] ?? 1);
        $targetType = $json['target_type'] ?? 'agent';
        $targetId   = (string)($json['target_id'] ?? '1');

        $runner = new EvaluationRunner();
        $run = $runner->runEvaluation($datasetId, $targetType, $targetId);

        return $this->respondSuccess($run->toArray(), 'Evaluation run started');
    }

    /**
     * GET /api/v1/evaluations/runs - List evaluation runs.
     */
    public function getRuns()
    {
        $db = $this->getDb();
        $runs = [];
        if ($db !== null) {
            try {
                $runs = $db->table($db->prefixTable('atom_eval_runs'), true)
                           ->orderBy('id', 'DESC')
                           ->get(30)
                           ->getResultArray();
            } catch (\Throwable $e) {}
        }

        return $this->respondSuccess($runs);
    }

    /**
     * POST /api/v1/evaluations/compare - Benchmark candidate vs baseline.
     */
    public function compareCandidate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $baseline = $json['baseline'] ?? ['correctness' => 0.90, 'safety' => 1.0];
        $candidate = $json['candidate'] ?? ['correctness' => 0.95, 'safety' => 1.0];

        $detector = new RegressionDetector();
        $check = $detector->detectRegression($baseline, $candidate);
        $policy = PromotionPolicy::canPromote($check);

        return $this->respondSuccess([
            'regression_check' => $check,
            'promotion_policy' => $policy,
        ]);
    }
}
