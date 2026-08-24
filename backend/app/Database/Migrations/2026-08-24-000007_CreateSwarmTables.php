<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSwarmTables extends Migration
{
    public function up()
    {
        // Table: atom_agent_definitions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'owner_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'worker',
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'capabilities_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'allowed_tools_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'allowed_skills_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'risk_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'medium',
            ],
            'max_steps' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'max_tool_calls' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'active',
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('owner_user_id');
        $this->forge->addKey('slug');
        $this->forge->createTable('atom_agent_definitions', true);

        // Table: atom_agent_versions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'agent_definition_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'definition_json' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_definition_id');
        $this->forge->createTable('atom_agent_versions', true);

        // Table: atom_swarm_executions
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
            'workflow_execution_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'objective' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'running',
            ],
            'coordinator_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'max_agents' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 8,
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
            'result' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_swarm_executions', true);

        // Table: atom_swarm_members
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'swarm_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'agent_definition_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'worker',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'completed',
            ],
            'assigned_task' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'result_json' => [
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('swarm_id');
        $this->forge->createTable('atom_swarm_members', true);

        // Table: atom_swarm_events
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'swarm_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'member_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'payload_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('swarm_id');
        $this->forge->createTable('atom_swarm_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_swarm_events', true);
        $this->forge->dropTable('atom_swarm_members', true);
        $this->forge->dropTable('atom_swarm_executions', true);
        $this->forge->dropTable('atom_agent_versions', true);
        $this->forge->dropTable('atom_agent_definitions', true);
    }
}
