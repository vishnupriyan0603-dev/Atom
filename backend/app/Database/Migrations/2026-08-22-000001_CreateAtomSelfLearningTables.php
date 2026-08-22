<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAtomSelfLearningTables extends Migration
{
    public function up()
    {
        // 1. atom_memories
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'memory_type'    => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'fact'],
            'topic'          => ['type' => 'VARCHAR', 'constraint' => 128],
            'content'        => ['type' => 'TEXT'],
            'source'         => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'conversation'],
            'confidence'     => ['type' => 'FLOAT', 'default' => 1.0],
            'relevance_score'=> ['type' => 'FLOAT', 'default' => 1.0],
            'access_count'   => ['type' => 'INT', 'default' => 0],
            'last_accessed_at'=>['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('memory_type');
        $this->forge->addKey('topic');
        $this->forge->createTable('atom_memories', true);

        // 2. atom_knowledge_items
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'         => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'general'],
            'content'          => ['type' => 'TEXT'],
            'source_uri'       => ['type' => 'TEXT', 'null' => true],
            'embedding'        => ['type' => 'BLOB', 'null' => true],
            'confidence_score' => ['type' => 'FLOAT', 'default' => 0.90],
            'version'          => ['type' => 'INT', 'default' => 1],
            'checksum'         => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => ''],
            'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category');
        $this->forge->addKey('checksum');
        $this->forge->createTable('atom_knowledge_items', true);

        // 3. atom_knowledge_triples
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'subject'        => ['type' => 'VARCHAR', 'constraint' => 128],
            'predicate'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'object'         => ['type' => 'VARCHAR', 'constraint' => 128],
            'confidence'     => ['type' => 'FLOAT', 'default' => 0.95],
            'source_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('subject');
        $this->forge->addKey('predicate');
        $this->forge->addKey('object');
        $this->forge->createTable('atom_knowledge_triples', true);

        // 4. atom_evaluations
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'chat_id'             => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'message_id'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'prompt_version'      => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'v1.0'],
            'model_name'          => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'default'],
            'rag_retrieval_count' => ['type' => 'INT', 'default' => 0],
            'user_feedback'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'accuracy_score'      => ['type' => 'FLOAT', 'null' => true],
            'latency_ms'          => ['type' => 'INT', 'default' => 0],
            'tokens_used'         => ['type' => 'INT', 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('chat_id');
        $this->forge->createTable('atom_evaluations', true);

        // 5. atom_experiments
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 128],
            'target_component' => ['type' => 'VARCHAR', 'constraint' => 64],
            'baseline_config'  => ['type' => 'TEXT'],
            'candidate_config' => ['type' => 'TEXT'],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'running'],
            'baseline_score'   => ['type' => 'FLOAT', 'default' => 0.0],
            'candidate_score'  => ['type' => 'FLOAT', 'default' => 0.0],
            'improvement_pct'  => ['type' => 'FLOAT', 'default' => 0.0],
            'human_approved'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('atom_experiments', true);

        // 6. atom_human_approvals
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'experiment_id' => ['type' => 'INT', 'unsigned' => true],
            'action'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'requested_by'  => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'ATOM_SELF_IMPROVEMENT_ENGINE'],
            'approved_by'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
            'reason'        => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'resolved_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('experiment_id');
        $this->forge->addKey('status');
        $this->forge->createTable('atom_human_approvals', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_human_approvals', true);
        $this->forge->dropTable('atom_experiments', true);
        $this->forge->dropTable('atom_evaluations', true);
        $this->forge->dropTable('atom_knowledge_triples', true);
        $this->forge->dropTable('atom_knowledge_items', true);
        $this->forge->dropTable('atom_memories', true);
    }
}
