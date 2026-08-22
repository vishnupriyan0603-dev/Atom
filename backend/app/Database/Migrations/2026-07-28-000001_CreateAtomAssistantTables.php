<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAtomAssistantTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'model'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'provider'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_pinned'  => ['type' => 'TINYINT', 'default' => 0],
            'folder_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tags'       => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_pinned');
        $this->forge->addKey('folder_id');
        $this->forge->createTable('chats');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'chat_id'    => ['type' => 'INT', 'unsigned' => true],
            'role'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'content'    => ['type' => 'LONGTEXT'],
            'tokens_in'  => ['type' => 'INT', 'null' => true],
            'tokens_out' => ['type' => 'INT', 'null' => true],
            'model'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('chat_id');
        $this->forge->addForeignKey('chat_id', 'chats', 'id', '', 'CASCADE');
        $this->forge->createTable('messages');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'     => ['type' => 'TEXT'],
            'category'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_favorite' => ['type' => 'TINYINT', 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category');
        $this->forge->createTable('prompts');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'     => ['type' => 'TEXT', 'null' => true],
            'folder'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_favorite' => ['type' => 'TINYINT', 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('folder');
        $this->forge->createTable('notes');

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'provider'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'api_endpoint'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'api_key'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_local'       => ['type' => 'TINYINT', 'default' => 0],
            'is_enabled'     => ['type' => 'TINYINT', 'default' => 1],
            'context_length' => ['type' => 'INT', 'default' => 4096],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ai_models');

        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'value' => ['type' => 'TEXT'],
            'type'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'string'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('settings');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'    => ['type' => 'LONGTEXT', 'null' => true],
            'file_path'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'file_type'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'collection' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('collection');
        $this->forge->createTable('knowledge_items');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 500],
            'size'          => ['type' => 'INT', 'default' => 0],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'chat_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('chat_id');
        $this->forge->addForeignKey('chat_id', 'chats', 'id', '', 'SET NULL');
        $this->forge->createTable('file_records');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'version'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'author'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'icon_path'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_enabled'   => ['type' => 'TINYINT', 'default' => 1],
            'installed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('plugins');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('file_records');
        $this->forge->dropTable('messages');
        $this->forge->dropTable('chats');
        $this->forge->dropTable('prompts');
        $this->forge->dropTable('notes');
        $this->forge->dropTable('ai_models');
        $this->forge->dropTable('settings');
        $this->forge->dropTable('knowledge_items');
        $this->forge->dropTable('plugins');
        $this->forge->dropTable('users');
    }
}
