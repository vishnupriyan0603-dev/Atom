<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoutingTables extends Migration
{
    public function up()
    {
        // Table: atom_routing_policies
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
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'model',
            ],
            'enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'priority' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'default_candidate' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'gemini-1.5-flash',
            ],
            'fallback_candidate' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'groq-llama3-70b',
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
        $this->forge->createTable('atom_routing_policies', true);

        // Table: atom_routing_candidates
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'policy_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'model',
            ],
            'target_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'gemini',
            ],
            'capabilities_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'evaluation_score' => [
                'type'       => 'FLOAT',
                'default'    => 0.95,
            ],
            'health_score' => [
                'type'       => 'FLOAT',
                'default'    => 1.0,
            ],
            'traffic_weight' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 100,
            ],
            'enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('policy_id');
        $this->forge->createTable('atom_routing_candidates', true);

        // Table: atom_routing_decisions
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
            'policy_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'selected_candidate' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'reason_codes_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'score' => [
                'type'       => 'FLOAT',
                'default'    => 1.0,
            ],
            'fallback_used' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'latency_ms' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 150,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('atom_routing_decisions', true);

        // Table: atom_routing_health
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'healthy',
            ],
            'error_rate' => [
                'type'       => 'FLOAT',
                'default'    => 0.0,
            ],
            'latency_ms' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 120,
            ],
            'circuit_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'closed',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('provider');
        $this->forge->createTable('atom_routing_health', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_routing_health', true);
        $this->forge->dropTable('atom_routing_decisions', true);
        $this->forge->dropTable('atom_routing_candidates', true);
        $this->forge->dropTable('atom_routing_policies', true);
    }
}
