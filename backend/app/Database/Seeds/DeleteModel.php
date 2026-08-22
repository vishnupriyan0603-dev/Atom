<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DeleteModel extends Seeder
{
    public function run(): void
    {
        echo "Model ID to delete (see ListModels): ";
        $id = trim(fgets(STDIN) ?: '');

        if (empty($id) || !is_numeric($id)) {
            echo "Error: Valid ID required.\n";
            return;
        }

        $model = $this->db->table('ai_models')->where('id', (int) $id)->get()->getRow();
        if (!$model) {
            echo "Model ID {$id} not found.\n";
            return;
        }

        $this->db->table('ai_models')->where('id', (int) $id)->delete();
        echo "Model '{$model->name}' (ID: {$id}) deleted.\n";
    }
}
