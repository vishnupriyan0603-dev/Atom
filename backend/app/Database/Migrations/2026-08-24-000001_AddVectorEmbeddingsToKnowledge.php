<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVectorEmbeddingsToKnowledge extends Migration
{
    public function up(): void
    {
        $fields = [
            'embedding_json' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'content',
            ],
            'token_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'embedding_json',
            ],
            'vector_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'token_count',
            ],
            'chunk_index' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'vector_hash',
            ],
        ];

        if ($this->db->tableExists('knowledge_items')) {
            $this->forge->addColumn('knowledge_items', $fields);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('knowledge_items')) {
            $this->forge->dropColumn('knowledge_items', ['embedding_json', 'token_count', 'vector_hash', 'chunk_index']);
        }
    }
}
