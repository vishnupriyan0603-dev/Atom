<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApprovalRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'tool_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'parameters' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'risk_level' => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high'],
                'default'    => 'high',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'expired', 'cancelled', 'executed', 'failed'],
                'default'    => 'pending',
            ],
            'approved_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_approval_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_approval_requests', true);
    }
}
