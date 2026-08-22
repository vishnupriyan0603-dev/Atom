<?php

namespace App\Controllers\Api;

class Health extends BaseApiController
{
    /**
     * Loads the Atom configuration (root .env) the same way AiChatService does.
     */
    private function loadAtomConfig(): string
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);
        return $workspaceRoot;
    }

    public function index()
    {
        $workspaceRoot = $this->loadAtomConfig();

        // Database
        $dbConnected = false;
        try {
            $db = \Config\Database::connect();
            $dbConnected = ($db->getConnection() !== false);
        } catch (\Throwable $e) {
            log_message('error', '[ATOM HEALTH] Database check failed: ' . $e->getMessage());
        }

        // Knowledge store (atom_documents + chunks tables exist)
        $knowledgeOk = false;
        if ($dbConnected) {
            try {
                $db->query("SELECT COUNT(*) AS c FROM atom_document_chunks");
                $knowledgeOk = true;
            } catch (\Throwable $e) {
                log_message('error', '[ATOM HEALTH] Knowledge check failed: ' . $e->getMessage());
            }
        }

        // AI provider keys (from Atom config, root .env)
        $providers = [
            'groq'   => !empty(\Atom\Config\Config::get('GROQ_API_KEY')) || !empty(\Atom\Config\Config::get('LLM_API_KEY')),
            'gemini' => !empty(\Atom\Config\Config::get('GEMINI_API_KEY')),
            'openai' => !empty(\Atom\Config\Config::get('OPENAI_API_KEY')),
            'local'  => true, // Ollama assumed reachable until a live probe is implemented
        ];

        return $this->respondSuccess([
            'status'   => $dbConnected ? 'ok' : 'degraded',
            'services' => [
                'database'  => $dbConnected,
                'knowledge' => $knowledgeOk,
                'providers' => $providers,
            ],
            'version' => '1.0.0',
        ], 'Health check complete');
    }
}
