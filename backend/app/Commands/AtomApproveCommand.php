<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Atom\Database\Connection;
use Atom\Security\HumanApprovalGate;

class AtomApproveCommand extends BaseCommand
{
    protected $group       = 'ATOM';
    protected $name        = 'atom:approve';
    protected $description = 'Approves a pending self-improvement experiment promotion.';
    protected $usage       = 'atom:approve <approval_id>';

    public function run(array $params)
    {
        $id = $params[0] ?? null;
        if (empty($id)) {
            CLI::error("Error: Please specify approval ID. Example: php spark atom:approve 1");
            return;
        }

        $db = \Config\Database::connect();
        $pdo = $db->connID;
        $conn = ($pdo instanceof \PDO) ? Connection::fromPdo($pdo) : null;

        $gate = new HumanApprovalGate($conn);
        $success = $gate->approve((int)$id, 'HUMAN_OPERATOR_CLI');

        if ($success) {
            CLI::write("SUCCESS: Approval #{$id} granted! Candidate configuration has been promoted to production.", 'green');
        } else {
            CLI::error("ERROR: Failed to approve #{$id}. Verify that request exists and is in 'pending' status.");
        }
    }
}
