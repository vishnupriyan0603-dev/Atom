<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiSummaryToDocuments extends Migration
{
    public function up()
    {
        // Idempotent: columns may already exist from schema.sql/manual migration.
        if (!$this->db->tableExists('atom_documents') || $this->db->fieldExists('ai_summary', 'atom_documents')) {
            return;
        }
        $this->forge->addColumn('atom_documents', [
            'ai_summary'  => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'path',
            ],
            'trained_at'  => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'ai_summary',
            ],
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('atom_documents') || !$this->db->fieldExists('ai_summary', 'atom_documents')) {
            return;
        }
        $this->forge->dropColumn('atom_documents', ['ai_summary', 'trained_at']);
    }
}
