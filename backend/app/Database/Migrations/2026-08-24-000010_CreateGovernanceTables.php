<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGovernanceTables extends Migration
{
    public function up()
    {
        // Table: atom_governance_policies
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'owner_id' => [
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
                'default'    => 'active',
            ],
            'priority' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'system',
            ],
            'rules_json' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('owner_id');
        $this->forge->createTable('atom_governance_policies', true);

        // Table: atom_governance_decisions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'actor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'resource' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'decision' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'allow',
            ],
            'reason_codes_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'policy_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_id');
        $this->forge->createTable('atom_governance_decisions', true);

        // Table: atom_governance_kill_switches
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'target_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('atom_governance_kill_switches', true);

        // Table: atom_governance_audit
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'actor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'resource' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'details_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_id');
        $this->forge->createTable('atom_governance_audit', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_governance_audit', true);
        $this->forge->dropTable('atom_governance_kill_switches', true);
        $this->forge->dropTable('atom_governance_decisions', true);
        $this->forge->dropTable('atom_governance_policies', true);
    }
}
