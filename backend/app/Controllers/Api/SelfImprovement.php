<?php

namespace App\Controllers\Api;

use Atom\Brain\SelfImprovementEngine;
use Atom\Database\Connection;
use Atom\Knowledge\KnowledgeGraph;
use Atom\Security\HumanApprovalGate;

class SelfImprovement extends BaseApiController
{
    private function getConn(): ?Connection
    {
        $db = \Config\Database::connect();
        $pdo = $db->connID;
        if ($pdo instanceof \PDO) {
            return Connection::fromPdo($pdo);
        }
        return null;
    }

    public function flaws()
    {
        $engine = new SelfImprovementEngine($this->getConn());
        $result = $engine->detectFlaws();
        return $this->respondSuccess($result, 'Flaw detection metrics retrieved');
    }

    public function experiments()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('atom_experiments');
        $experiments = $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
        return $this->respondSuccess($experiments, 'Experiments retrieved');
    }

    public function createExperiment()
    {
        $json = $this->request->getJSON(true) ?? [];
        $title = $json['title'] ?? 'Prompt Optimization Experiment';
        $target = $json['target_component'] ?? 'prompt_template';
        $baseline = $json['baseline_config'] ?? ['temperature' => 0.7, 'top_k' => 5];
        $candidate = $json['candidate_config'] ?? ['temperature' => 0.5, 'top_k' => 10];
        $bScore = (float)($json['baseline_score'] ?? 0.80);
        $cScore = (float)($json['candidate_score'] ?? 0.92);

        $engine = new SelfImprovementEngine($this->getConn());
        $expId = $engine->createExperiment($title, $target, $baseline, $candidate);

        if ($expId > 0) {
            $evalResult = $engine->evaluateExperiment($expId, $bScore, $cScore);
            return $this->respondSuccess($evalResult, 'Experiment created and evaluated');
        }

        return $this->respondError('Failed to create experiment');
    }

    public function approvals()
    {
        $gate = new HumanApprovalGate($this->getConn());
        $pending = $gate->getPendingApprovals();
        return $this->respondSuccess($pending, 'Pending human approval requests');
    }

    public function approve($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Approval ID required');
        }

        $gate = new HumanApprovalGate($this->getConn());
        $approvedBy = $this->request->getJSON(true)['approved_by'] ?? 'HUMAN_OPERATOR';
        $success = $gate->approve((int)$id, $approvedBy);

        if ($success) {
            return $this->respondSuccess(null, "Approval #{$id} granted and candidate promoted.");
        }
        return $this->respondError("Failed to approve #{$id} or request not pending.");
    }

    public function reject($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Approval ID required');
        }

        $json = $this->request->getJSON(true) ?? [];
        $reason = $json['reason'] ?? 'REJECTED_BY_HUMAN';
        $rejectedBy = $json['rejected_by'] ?? 'HUMAN_OPERATOR';

        $gate = new HumanApprovalGate($this->getConn());
        $success = $gate->reject((int)$id, $reason, $rejectedBy);

        if ($success) {
            return $this->respondSuccess(null, "Approval #{$id} rejected and experiment rolled back.");
        }
        return $this->respondError("Failed to reject #{$id} or request not pending.");
    }

    public function triples()
    {
        $kg = new KnowledgeGraph($this->getConn());
        $subject = $this->request->getGet('subject');
        $predicate = $this->request->getGet('predicate');
        $object = $this->request->getGet('object');

        $triples = $kg->queryTriples($subject, $predicate, $object);
        return $this->respondSuccess($triples, 'Knowledge graph triples retrieved');
    }
}
