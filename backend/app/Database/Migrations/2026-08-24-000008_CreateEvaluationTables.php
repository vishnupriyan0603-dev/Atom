<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEvaluationTables extends Migration
{
    public function up()
    {
        // Table: atom_eval_datasets
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
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'active',
            ],
            'case_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
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
        $this->forge->addKey('status');
        $this->forge->createTable('atom_eval_datasets', true);

        // Table: atom_eval_cases
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'dataset_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'dataset_version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'input_json' => [
                'type' => 'TEXT',
            ],
            'expected_output_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'evaluation_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'exact_match',
            ],
            'difficulty' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'medium',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('dataset_id');
        $this->forge->createTable('atom_eval_cases', true);

        // Table: atom_eval_runs
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'dataset_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'agent',
            ],
            'target_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '1',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'completed',
            ],
            'total_cases' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'completed_cases' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'failed_cases' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'aggregate_score' => [
                'type'       => 'FLOAT',
                'default'    => 1.0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('dataset_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_eval_runs', true);

        // Table: atom_eval_results
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'run_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'case_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'score' => [
                'type'       => 'FLOAT',
                'default'    => 1.0,
            ],
            'passed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'output_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'metrics_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('run_id');
        $this->forge->createTable('atom_eval_results', true);

        // Table: atom_eval_promotions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'candidate_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'candidate_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'candidate_version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'promoted',
            ],
            'decision_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'decision_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('atom_eval_promotions', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_eval_promotions', true);
        $this->forge->dropTable('atom_eval_results', true);
        $this->forge->dropTable('atom_eval_runs', true);
        $this->forge->dropTable('atom_eval_cases', true);
        $this->forge->dropTable('atom_eval_datasets', true);
    }
}
