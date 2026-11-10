<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Atom\Security\HumanApprovalGate;

final class ApprovalTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';

    public function testToolApprovalRequestLifecycle(): void
    {
        $gate = new HumanApprovalGate();

        // Create tool approval request
        $reqId = $gate->createToolApprovalRequest(
            userId: 1,
            toolName: 'database.write',
            action: 'DROP_TABLE',
            parameters: ['table' => 'users'],
            riskLevel: 'high',
            reason: 'High risk database drop requested'
        );

        $this->assertGreaterThan(0, $reqId);

        // Fetch pending requests
        $pending = $gate->getApprovalRequests('pending');
        $found = false;
        foreach ($pending as $p) {
            if ((int)$p['id'] === $reqId) {
                $found = true;
                $this->assertEquals('database.write', $p['tool_name']);
                $this->assertEquals('high', $p['risk_level']);
                break;
            }
        }
        $this->assertTrue($found, "Created approval request #{$reqId} should be in pending list.");

        // Approve request
        $approved = $gate->approveToolRequest($reqId, 'TEST_ADMIN');
        $this->assertTrue($approved);

        // Verify status is updated to approved
        $all = $gate->getApprovalRequests('approved');
        $approvedFound = false;
        foreach ($all as $a) {
            if ((int)$a['id'] === $reqId) {
                $approvedFound = true;
                $this->assertEquals('approved', $a['status']);
                $this->assertEquals('TEST_ADMIN', $a['approved_by']);
                break;
            }
        }
        $this->assertTrue($approvedFound);
    }

    public function testToolApprovalRequestRejection(): void
    {
        $gate = new HumanApprovalGate();

        $reqId = $gate->createToolApprovalRequest(
            userId: 1,
            toolName: 'filesystem.write',
            action: 'DELETE_FILE',
            parameters: ['path' => 'config.php'],
            riskLevel: 'high',
            reason: 'Critical file deletion request'
        );

        $this->assertGreaterThan(0, $reqId);

        // Reject request
        $rejected = $gate->rejectToolRequest($reqId, 'TEST_ADMIN', 'Dangerous operation rejected');
        $this->assertTrue($rejected);

        $all = $gate->getApprovalRequests('rejected');
        $rejectedFound = false;
        foreach ($all as $r) {
            if ((int)$r['id'] === $reqId) {
                $rejectedFound = true;
                $this->assertEquals('rejected', $r['status']);
                $this->assertEquals('Dangerous operation rejected', $r['reason']);
                break;
            }
        }
        $this->assertTrue($rejectedFound);
    }

    public function testAssistantActionApprovalIsOwnerBoundAndSingleUse(): void
    {
        $gate = new HumanApprovalGate();
        $requestId = $gate->createToolApprovalRequest(
            userId: 11,
            toolName: 'brain_reasoner',
            action: 'brain_tool:calc',
            parameters: ['expression' => '2 + 2'],
            riskLevel: 'medium',
            reason: 'Review calculation',
            expiresInMinutes: 15
        );

        $this->assertNull($gate->approveOwnedToolRequest($requestId, 12));
        $this->assertNotNull($gate->approveOwnedToolRequest($requestId, 11));
        $this->assertTrue($gate->completeToolRequest($requestId, 11, 'executed', ['success' => true, 'result' => 4]));
        $this->assertNull($gate->approveOwnedToolRequest($requestId, 11));
        $this->assertCount(0, $gate->getApprovalRequests('pending', 12));
        $this->assertCount(1, $gate->getApprovalRequests('executed', 11));
    }

    public function testExpiredAssistantActionCannotBeApproved(): void
    {
        $gate = new HumanApprovalGate();
        $requestId = $gate->createToolApprovalRequest(
            userId: 21,
            toolName: 'brain_reasoner',
            action: 'brain_tool:system_inspect',
            parameters: [],
            riskLevel: 'medium',
            reason: 'Review system inspection',
            expiresInMinutes: 0
        );

        $this->assertNull($gate->approveOwnedToolRequest($requestId, 21));
        $expired = $gate->getApprovalRequests('expired', 21);
        $this->assertCount(1, array_filter($expired, static fn (array $item): bool => (int)$item['id'] === $requestId));
    }

    public function testChatToolApprovalIsBoundToOwnerAndOriginalTurn(): void
    {
        $gate = new HumanApprovalGate();
        $requestId = $gate->createToolApprovalRequest(
            userId: 31,
            toolName: 'brain_chat',
            action: 'brain_chat_tool:search_code',
            parameters: [
                'chat_id' => 52,
                'user_message_id' => 73,
                'tool_arguments' => ['query' => 'approval gate'],
            ],
            riskLevel: 'low',
            reason: '{"action":"search_code","target":"workspace"}',
            expiresInMinutes: 15
        );

        $this->assertGreaterThan(0, $requestId);
        $this->assertNull($gate->approveOwnedToolRequest($requestId, 32));
        $claimed = $gate->approveOwnedToolRequest($requestId, 31);
        $this->assertNotNull($claimed);
        $this->assertSame('brain_chat_tool:search_code', $claimed['action']);
        $this->assertTrue($gate->completeToolRequest($requestId, 31, 'executed', ['success' => true, 'matches' => 2]));
        $this->assertNull($gate->approveOwnedToolRequest($requestId, 31));

        $executed = $gate->getExecutedChatToolRequests(31, 52, 73);
        $this->assertCount(1, $executed);
        $this->assertSame(['query' => 'approval gate'], $executed[0]['decoded_parameters']['tool_arguments']);
        $this->assertSame(['success' => true, 'matches' => 2], $executed[0]['decoded_result']);
        $this->assertCount(0, $gate->getExecutedChatToolRequests(32, 52, 73));
        $this->assertCount(0, $gate->getExecutedChatToolRequests(31, 52, 74));
    }
}
