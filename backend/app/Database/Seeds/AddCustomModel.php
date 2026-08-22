<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AddCustomModel extends Seeder
{
    public function run(): void
    {
        echo "Model name: ";
        $name = trim(fgets(STDIN) ?: '');

        echo "Provider (Ollama/LM Studio/Local): ";
        $provider = trim(fgets(STDIN) ?: '');

        echo "API endpoint (leave blank for default): ";
        $endpoint = trim(fgets(STDIN) ?: '');

        echo "Context length (default 4096): ";
        $ctx = trim(fgets(STDIN) ?: '');

        if (empty($name) || empty($provider)) {
            echo "Error: Name and provider are required.\n";
            return;
        }

        if (empty($endpoint)) {
            $endpoint = 'http://localhost:11434/api/chat';
        }
        if (empty($ctx)) {
            $ctx = 4096;
        }

        $existing = $this->db->table('ai_models')
            ->where('name', $name)
            ->where('provider', $provider)
            ->get()
            ->getRow();

        if ($existing) {
            echo "Model '{$name}' already exists.\n";
            return;
        }

        $this->db->table('ai_models')->insert([
            'name'           => $name,
            'provider'       => $provider,
            'api_endpoint'   => $endpoint,
            'api_key'        => '',
            'is_local'       => 1,
            'is_enabled'     => 1,
            'context_length' => (int) $ctx,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        echo "Model '{$name}' added successfully!\n";
    }
}
