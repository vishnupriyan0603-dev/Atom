<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CleanModels extends Seeder
{
    public function run(): void
    {
        // Remove all cloud/remote models
        $this->db->table('ai_models')
            ->where('is_local', 0)
            ->delete();

        $remaining = $this->db->table('ai_models')->countAllResults();
        echo "Removed cloud models. {$remaining} local models remaining.\n";
    }
}
