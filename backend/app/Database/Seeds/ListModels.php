<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ListModels extends Seeder
{
    public function run(): void
    {
        $models = $this->db->table('ai_models')
            ->orderBy('provider', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResult();

        if (empty($models)) {
            echo "No models found. Run: php spark db:seed AiModelSeeder\n";
            return;
        }

        echo "\n";
        echo str_pad('ID', 5) . str_pad('Provider', 15) . str_pad('Model', 25) . str_pad('Status', 10) . "Endpoint\n";
        echo str_repeat('-', 90) . "\n";

        foreach ($models as $m) {
            $status = $m->is_enabled ? 'ON' : 'OFF';
            $local  = $m->is_local ? ' [LOCAL]' : '';
            echo str_pad($m->id, 5)
                . str_pad($m->provider, 15)
                . str_pad($m->name, 25)
                . str_pad($status, 10)
                . ($m->api_endpoint ?: '(no endpoint)') . $local . "\n";
        }

        echo "\nTotal: " . count($models) . " models\n\n";
    }
}
