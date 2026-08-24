<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBackgroundJobsTable extends Migration
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
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['queued', 'running', 'completed', 'failed', 'cancelled'],
                'default'    => 'queued',
            ],
            'attempts' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'max_attempts' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 3,
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('type');
        $this->forge->createTable('atom_jobs', true);
    }

    public function down()
    {
        $this->forge->dropTable('atom_jobs', true);
    }
}
