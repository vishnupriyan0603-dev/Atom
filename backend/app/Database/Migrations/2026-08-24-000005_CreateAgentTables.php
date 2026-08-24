<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgentTables extends Migration
{
    public function up()
    {
        // Table: atom_agent_tasks
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
            'conversation_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => 'Agent Task',
            ],
            'objective' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'pending',
            ],
            'priority' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'normal',
            ],
            'current_step' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'max_steps' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 20,
            ],
            'max_tool_calls' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'max_tokens' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 8000,
            ],
            'max_runtime_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 300,
            ],
            'max_cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
                'default'    => '1.0000',
            ],
            'max_replans' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 3,
            ],
            'risk_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'low',
            ],
            'requires_approval' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'result' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_agent_tasks', true);

        // Table: atom_agent_steps
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'sequence' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'pending',
            ],
            'tool_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'input' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'output' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'observation' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'retry_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'requires_approval' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_agent_steps', true);

        // Table: atom_agent_events
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'step_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('event_type');
        $this->forge->createTable('atom_agent_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_agent_events', true);
        $this->forge->dropTable('atom_agent_steps', true);
        $this->forge->dropTable('atom_agent_tasks', true);
    }
}
