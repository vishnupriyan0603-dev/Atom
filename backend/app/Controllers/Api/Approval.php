<?php

namespace App\Controllers\Api;

use Atom\Security\HumanApprovalGate;

class Approval extends BaseApiController
{
    public function list()
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return $this->respondError('Authentication is required', 401);
        }
        $status = $this->request->getGet('status');
        $gate = new HumanApprovalGate();
        $requests = $gate->getApprovalRequests($status, $userId);
        foreach ($requests as &$request) {
            if (isset($request['parameters'])) {
                $request['parameters'] = json_decode((string)$request['parameters'], true) ?? [];
            }
        }
        unset($request);
        return $this->respondSuccess($requests);
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? [];
        $userId = $this->currentUserId();
        if ($userId === null) {
            return $this->respondError('Authentication is required', 401);
        }
        $toolName = is_string($json['tool_name'] ?? null) ? trim($json['tool_name']) : '';
        $action = is_string($json['action'] ?? null) ? trim($json['action']) : 'execute';
        $params = $json['parameters'] ?? [];
        $risk = is_string($json['risk_level'] ?? null) ? trim($json['risk_level']) : 'invalid';
        $reason = is_string($json['reason'] ?? null) ? trim($json['reason']) : 'Requested tool authorization';

        if ($toolName === '' || !is_array($params) || !in_array(strtolower($risk), ['low', 'medium', 'high'], true)) {
            return $this->respondError('A tool name, object parameters, and valid risk_level are required', 400);
        }
        if (strlen(json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '') > 8192) {
            return $this->respondError('Approval parameters exceed the 8 KB limit', 413);
        }

        $gate = new HumanApprovalGate();
        $reqId = $gate->createToolApprovalRequest($userId, $toolName, $action, $params, $risk, $reason);

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

        $userId = $this->currentUserId();
        if ($userId === null) {
            return $this->respondError('Authentication is required', 401);
        }

        $gate = new HumanApprovalGate();
        $owned = $gate->getApprovalRequests(null, $userId);
        $request = null;
        foreach ($owned as $candidate) {
            if ((int)$candidate['id'] === (int)$id) {
                $request = $candidate;
                break;
            }
        }
        if ($request === null || ($request['status'] ?? '') !== 'pending') {
            return $this->respondError('Approval not found, expired, or already resolved', 404);
        }

        if (($request['tool_name'] ?? '') === 'brain_reasoner'
            && str_starts_with((string)($request['action'] ?? ''), 'brain_tool:')) {
            $approvedRequest = $gate->approveOwnedToolRequest((int)$id, $userId);
            if ($approvedRequest === null) {
                return $this->respondError('Approval not found, expired, or already resolved', 409);
            }

            $toolName = substr((string)$approvedRequest['action'], strlen('brain_tool:'));
            $allowed = array_column((new \Atom\Brain\AtomSituationReasonerEngine())->getAvailableTools(), 'name');
            $parameters = json_decode((string)($approvedRequest['parameters'] ?? '{}'), true);
            if (!in_array($toolName, $allowed, true) || !is_array($parameters)) {
                $gate->completeToolRequest((int)$id, $userId, 'failed', ['success' => false, 'error' => 'Stored action is invalid.']);
                return $this->respondError('Stored action is invalid', 422);
            }

            try {
                $result = (new \Atom\Brain\AtomSituationReasonerEngine())->executeTool($toolName, $parameters, true);
                $status = !empty($result['success']) ? 'executed' : 'failed';
            } catch (\Throwable $exception) {
                $result = ['success' => false, 'error' => 'Action execution failed safely.'];
                $status = 'failed';
            }
            $gate->completeToolRequest((int)$id, $userId, $status, $result);
            return $this->respondSuccess([
                'approval_id' => (int)$id,
                'status' => $status,
                'result' => $result,
            ], $status === 'executed' ? 'Approved action executed once' : 'Approved action failed');
        }

        if (($request['tool_name'] ?? '') === 'brain_chat'
            && str_starts_with((string)($request['action'] ?? ''), 'brain_chat_tool:')) {
            $toolName = substr((string)$request['action'], strlen('brain_chat_tool:'));
            $parameters = json_decode((string)($request['parameters'] ?? '{}'), true);
            $arguments = is_array($parameters) ? ($parameters['tool_arguments'] ?? null) : null;
            $chatId = is_array($parameters) ? (int)($parameters['chat_id'] ?? 0) : 0;
            $messageId = is_array($parameters) ? (int)($parameters['user_message_id'] ?? 0) : 0;
            $chat = $chatId > 0 ? (new \App\Models\ChatModel())->findOwned($chatId, $userId) : null;
            $sourceMessage = $chat && $messageId > 0
                ? (new \App\Models\MessageModel())->where('id', $messageId)->where('chat_id', $chatId)->where('user_id', $userId)->where('role', 'user')->first()
                : null;
            if ($toolName === '' || !is_array($arguments) || !$chat || !$sourceMessage) {
                return $this->respondError('Stored chat action is invalid', 422);
            }

            $approvedRequest = $gate->approveOwnedToolRequest((int)$id, $userId);
            if ($approvedRequest === null) {
                return $this->respondError('Approval not found, expired, or already resolved', 409);
            }

            try {
                $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
                $result = \Atom\Brain\AtomBrainFactory::executeApprovedAction($workspaceRoot, $toolName, $arguments);
                $status = !empty($result['success']) ? 'executed' : 'failed';
            } catch (\Throwable $exception) {
                $result = ['success' => false, 'error' => 'Action execution failed safely.'];
                $status = 'failed';
            }
            $gate->completeToolRequest((int)$id, $userId, $status, $result);
            if ($status !== 'executed') {
                return $this->respondSuccess(['approval_id' => (int)$id, 'status' => $status, 'result' => $result], 'Approved action failed');
            }

            $resumed = (new \App\Services\AiChatService())->resumeApprovedChatTool($approvedRequest, $result, $userId);
            if (empty($resumed['success'])) {
                return $this->respondError($resumed['message'] ?? 'Chat continuation failed', 409);
            }
            return $this->respondSuccess([
                'approval_id' => (int)$id,
                'status' => 'executed',
                'result' => $result,
                'chat' => $resumed['data'],
            ], 'Action executed and chat resumed', isset($resumed['data']['pending_approval']) ? 202 : 200);
        }

        $success = $gate->approveToolRequest((int)$id, 'USER:' . $userId, $userId);

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
        $userId = $this->currentUserId();
        if ($userId === null) {
            return $this->respondError('Authentication is required', 401);
        }
        $reason = $json['reason'] ?? 'REJECTED_BY_USER';
        if (!is_string($reason)) {
            return $this->respondError('Rejection reason must be text', 400);
        }
        $reason = substr($reason, 0, 500);
        $rejectedBy = 'USER:' . $userId;

        $gate = new HumanApprovalGate();
        $request = null;
        foreach ($gate->getApprovalRequests('pending', $userId) as $candidate) {
            if ((int)($candidate['id'] ?? 0) === (int)$id) {
                $request = $candidate;
                break;
            }
        }
        $success = $gate->rejectToolRequest((int)$id, $rejectedBy, $reason, $userId);

        if ($success) {
            if (($request['tool_name'] ?? '') === 'brain_chat'
                && str_starts_with((string)($request['action'] ?? ''), 'brain_chat_tool:')) {
                $chatUpdate = (new \App\Services\AiChatService())->recordRejectedChatTool($request, $userId);
                return $this->respondSuccess(['chat' => $chatUpdate['data'] ?? null], "Approval request #{$id} rejected.");
            }
            return $this->respondSuccess(null, "Approval request #{$id} rejected.");
        }
        return $this->respondError("Failed to reject #{$id} or request not pending.");
    }
}
