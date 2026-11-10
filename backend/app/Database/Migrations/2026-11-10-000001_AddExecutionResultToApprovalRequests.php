<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExecutionResultToApprovalRequests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('atom_approval_requests', [
            'execution_result' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('atom_approval_requests', 'execution_result');
    }
}
