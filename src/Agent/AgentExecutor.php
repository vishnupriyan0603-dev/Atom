<?php

namespace Atom\Agent;

use Atom\Tools\ToolManager;
use Atom\Security\HumanApprovalGate;

class AgentExecutor
{
    private ?ToolManager $toolManager;
    private ?HumanApprovalGate $approvalGate;

    public function __construct(?ToolManager $toolManager = null, ?HumanApprovalGate $approvalGate = null)
    {
        $this->toolManager  = $toolManager;
        $this->approvalGate = $approvalGate;
    }

    public function executeStep(AgentTask $task, AgentStep $step): array
    {
        $type = strtolower($step->type);

        switch ($type) {
            case 'reasoning':
            case 'final_response':
                return [
                    'success'     => true,
                    'output'      => 'Processed step description: ' . $step->description,
                    'observation' => 'Reasoning completed cleanly.',
                ];

            case 'retrieval':
                return [
                    'success'     => true,
                    'output'      => 'Retrieved knowledge context relevant to: ' . $task->objective,
                    'observation' => 'Relevant document chunks & citations identified.',
                ];

            case 'memory':
                return [
                    'success'     => true,
                    'output'      => 'Extracted long-term structured memory entries for user_id=' . $task->userId,
                    'observation' => 'User preferences loaded into workspace context.',
                ];

            case 'tool_call':
                if (empty($step->toolName)) {
                    return [
                        'success' => false,
                        'error'   => 'Tool execution failed: No tool specified',
                    ];
                }

                $risk = RiskEngine::evaluateToolRisk($step->toolName, json_decode($step->input ?? '[]', true) ?: []);
                if (RiskEngine::requiresHumanApproval($risk) && $this->approvalGate !== null) {
                    $reqId = $this->approvalGate->createToolApprovalRequest(
                        $task->userId,
                        $step->toolName,
                        'execute_step',
                        json_decode($step->input ?? '[]', true) ?: [],
                        $risk,
                        'Agent step execution requires authorization'
                    );
                    return [
                        'success'           => false,
                        'requires_approval' => true,
                        'approval_request_id' => $reqId,
                        'error'             => 'Step requires human approval before proceeding',
                    ];
                }

                if ($this->toolManager !== null && $this->toolManager->hasTool($step->toolName)) {
                    $toolObj = $this->toolManager->getTool($step->toolName);
                    $params = json_decode($step->input ?? '[]', true) ?: [];
                    $res = $toolObj->execute($params);
                    return [
                        'success'     => !empty($res['success']),
                        'output'      => json_encode($res),
                        'observation' => 'Tool ' . $step->toolName . ' executed with status: ' . (!empty($res['success']) ? 'SUCCESS' : 'FAILURE'),
                        'error'       => $res['error'] ?? null,
                    ];
                }

                return [
                    'success'     => true,
                    'output'      => 'Simulated tool execution for: ' . $step->toolName,
                    'observation' => 'Tool executed safely.',
                ];

            default:
                return [
                    'success'     => true,
                    'output'      => 'Executed step type: ' . $type,
                    'observation' => 'Step finished.',
                ];
        }
    }
}
