<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiSummaryToDocuments extends Migration
{
    public function up()
    {
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
        $this->forge->dropColumn('atom_documents', ['ai_summary', 'trained_at']);
    }
}
