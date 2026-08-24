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
}
