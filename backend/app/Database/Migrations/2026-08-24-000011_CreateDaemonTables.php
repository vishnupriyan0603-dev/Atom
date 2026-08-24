<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDaemonTables extends Migration
{
    public function up()
    {
        // 1. Table: atom_daemon_heartbeats
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pulse_timestamp' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'memory_mb' => [
                'type'       => 'FLOAT',
                'default'    => 0.0,
            ],
            'uptime_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'healthy',
            ],
            'health_score' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 100,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pulse_timestamp');
        $this->forge->createTable('atom_daemon_heartbeats', true);

        // 2. Table: atom_daemon_briefings
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'briefing_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'morning',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'generated',
            ],
            'delivered_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('briefing_type');
        $this->forge->createTable('atom_daemon_briefings', true);

        // 3. Table: atom_daemon_healing_actions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'action_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'target_resource' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'completed',
            ],
            'policy_decision_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'details_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action_type');
        $this->forge->createTable('atom_daemon_healing_actions', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_daemon_healing_actions', true);
        $this->forge->dropTable('atom_daemon_briefings', true);
        $this->forge->dropTable('atom_daemon_heartbeats', true);
    }
}
