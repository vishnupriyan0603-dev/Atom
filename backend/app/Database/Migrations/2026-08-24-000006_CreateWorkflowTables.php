<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkflowTables extends Migration
{
    public function up()
    {
        // Table: atom_workflows
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
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'published',
            ],
            'current_version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'webhook_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'disabled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('owner_user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_workflows', true);

        // Table: atom_workflow_versions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'workflow_id' => [
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
        $this->forge->addKey('workflow_id');
        $this->forge->createTable('atom_workflow_versions', true);

        // Table: atom_workflow_executions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'workflow_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'workflow_version_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'owner_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'running',
            ],
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'current_node_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'variables_json' => [
                'type' => 'TEXT',
                'null' => true,
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
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('workflow_id');
        $this->forge->addKey('owner_user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_workflow_executions', true);

        // Table: atom_workflow_execution_nodes
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'execution_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'node_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'completed',
            ],
            'input_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'output_json' => [
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
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('execution_id');
        $this->forge->createTable('atom_workflow_execution_nodes', true);

        // Table: atom_workflow_events
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'execution_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'node_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
        $this->forge->addKey('execution_id');
        $this->forge->createTable('atom_workflow_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_workflow_events', true);
        $this->forge->dropTable('atom_workflow_execution_nodes', true);
        $this->forge->dropTable('atom_workflow_executions', true);
        $this->forge->dropTable('atom_workflow_versions', true);
        $this->forge->dropTable('atom_workflows', true);
    }
}
