<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStructuredMemoriesTable extends Migration
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
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['conversation', 'preference', 'fact', 'instruction', 'project', 'knowledge', 'temporary'],
                'default'    => 'fact',
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'embedding_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'importance' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 5,
            ],
            'confidence' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'default'    => 1.00,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'user_input',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'access_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('type');
        $this->forge->createTable('atom_structured_memories', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_structured_memories', true);
    }
}
