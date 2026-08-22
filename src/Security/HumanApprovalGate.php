<?php

namespace Atom\Security;

use Atom\Database\Connection;
use CodeIgniter\Database\BaseConnection;

class HumanApprovalGate
{
    private ?Connection $connection;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection;
    }

    private function getDb(): BaseConnection
    {
        return \Config\Database::connect();
    }

    /**
     * Request human approval for a high-impact self-improvement experiment promotion.
     */
    public function requestApproval(int $experimentId, string $action, string $reason, string $requestedBy = 'ATOM_SELF_IMPROVEMENT_ENGINE'): int
    {
        $db = $this->getDb();
        $builder = $db->table('atom_human_approvals', false);

        $data = [
            'experiment_id' => $experimentId,
            'action'        => $action,
            'requested_by'  => $requestedBy,
            'status'        => 'pending',
            'reason'        => $reason,
            'created_at'    => date('Y-m-d H:i:s')
        ];

        if ($builder->insert($data)) {
            return (int)$db->insertID();
        }
        return 0;
    }

    /**
     * Approve a pending change.
     */
    public function approve(int $approvalId, string $approvedBy = 'HUMAN_OPERATOR'): bool
    {
        $db = $this->getDb();
        $row = $db->table($db->prefixTable('atom_human_approvals'), true)
                  ->where('id', $approvalId)
                  ->where('status', 'pending')
                  ->get()->getRowArray();

        if (!$row) {
            return false;
        }

        $expId = (int)$row['experiment_id'];

        // Mark approval
        $db->table($db->prefixTable('atom_human_approvals'), true)->where('id', $approvalId)->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'resolved_at' => date('Y-m-d H:i:s')
        ]);

        // Promote experiment
        $db->table($db->prefixTable('atom_experiments'), true)->where('id', $expId)->update([
            'status'         => 'promoted',
            'human_approved' => 1,
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Reject a pending change.
     */
    public function reject(int $approvalId, string $reason = 'REJECTED_BY_HUMAN', string $rejectedBy = 'HUMAN_OPERATOR'): bool
    {
        $db = $this->getDb();
        $row = $db->table($db->prefixTable('atom_human_approvals'), true)
                  ->where('id', $approvalId)
                  ->where('status', 'pending')
                  ->get()->getRowArray();

        if (!$row) {
            return false;
        }

        $expId = (int)$row['experiment_id'];

        $db->table($db->prefixTable('atom_human_approvals'), true)->where('id', $approvalId)->update([
            'status'      => 'rejected',
            'approved_by' => $rejectedBy,
            'reason'      => $reason,
            'resolved_at' => date('Y-m-d H:i:s')
        ]);

        $db->table($db->prefixTable('atom_experiments'), true)->where('id', $expId)->update([
            'status'         => 'rolled_back',
            'human_approved' => 0,
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Fetch pending approval requests.
     */
    public function getPendingApprovals(): array
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_human_approvals') . ' a', true)
                  ->select('a.*, e.title as experiment_title, e.target_component, e.improvement_pct')
                  ->join($db->prefixTable('atom_experiments') . ' e', 'a.experiment_id = e.id')
                  ->where('a.status', 'pending')
                  ->orderBy('a.created_at', 'DESC')
                  ->get()->getResultArray();
    }
}
