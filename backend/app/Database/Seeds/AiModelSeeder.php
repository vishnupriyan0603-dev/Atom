<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->table('ai_models')->truncate();

        $models = [
            [
                'name'           => 'llama3.1',
                'provider'       => 'Ollama',
                'api_endpoint'   => 'http://localhost:11434/api/chat',
                'api_key'        => '',
                'is_local'       => 1,
                'is_enabled'     => 1,
                'context_length' => 131072,
                'created_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'name'           => 'llama3.2',
                'provider'       => 'Ollama',
                'api_endpoint'   => 'http://localhost:11434/api/chat',
                'api_key'        => '',
                'is_local'       => 1,
                'is_enabled'     => 1,
                'context_length' => 131072,
                'created_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'name'           => 'mistral',
                'provider'       => 'Ollama',
                'api_endpoint'   => 'http://localhost:11434/api/chat',
                'api_key'        => '',
                'is_local'       => 1,
                'is_enabled'     => 1,
                'context_length' => 32768,
                'created_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'name'           => 'Local Server',
                'provider'       => 'LM Studio',
                'api_endpoint'   => 'http://localhost:1234/v1/chat/completions',
                'api_key'        => '',
                'is_local'       => 1,
                'is_enabled'     => 1,
                'context_length' => 4096,
                'created_at'     => date('Y-m-d H:i:s'),
            ],
        ];

        $builder = $this->db->table('ai_models');
        foreach ($models as $model) {
            $builder->insert($model);
        }

        echo "Seeded " . count($models) . " local models.\n";
    }
}
