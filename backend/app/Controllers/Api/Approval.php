<?php

namespace App\Controllers\Api;

use Atom\Security\HumanApprovalGate;

class Approval extends BaseApiController
{
    public function list()
    {
        $status = $this->request->getGet('status');
        $gate = new HumanApprovalGate();
        $requests = $gate->getApprovalRequests($status);
        return $this->respondSuccess($requests);
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? [];
        $toolName = trim($json['tool_name'] ?? '');
        $action = trim($json['action'] ?? 'execute');
        $params = $json['parameters'] ?? [];
        $risk = trim($json['risk_level'] ?? 'high');
        $reason = trim($json['reason'] ?? 'Requested tool authorization');

        if (empty($toolName)) {
            return $this->respondError('tool_name is required');
        }

        $gate = new HumanApprovalGate();
        $reqId = $gate->createToolApprovalRequest(1, $toolName, $action, $params, $risk, $reason);

        if ($reqId > 0) {
            return $this->respondSuccess(['id' => $reqId, 'status' => 'pending'], 'Approval request created');
        }

        return $this->respondError('Failed to create approval request');
    }

    public function approve($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Approval ID required');
        }

        $gate = new HumanApprovalGate();
        $approvedBy = $this->request->getJSON(true)['approved_by'] ?? 'HUMAN_OPERATOR';
        $success = $gate->approveToolRequest((int)$id, $approvedBy);

        if ($success) {
            return $this->respondSuccess(null, "Approval request #{$id} granted.");
        }
        return $this->respondError("Failed to approve #{$id} or request not pending.");
    }

    public function reject($id = null)
    {
        if (empty($id)) {
            return $this->respondError('Approval ID required');
        }

        $json = $this->request->getJSON(true) ?? [];
        $reason = $json['reason'] ?? 'REJECTED_BY_ADMIN';
        $rejectedBy = $json['rejected_by'] ?? 'HUMAN_OPERATOR';

        $gate = new HumanApprovalGate();
        $success = $gate->rejectToolRequest((int)$id, $rejectedBy, $reason);

        if ($success) {
            return $this->respondSuccess(null, "Approval request #{$id} rejected.");
        }
        return $this->respondError("Failed to reject #{$id} or request not pending.");
    }
}
