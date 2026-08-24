<?php

namespace App\Services;

use App\Models\AiModelModel;
use App\Models\ChatModel;
use App\Models\MessageModel;
use App\Models\SettingModel;

class AiChatService
{
    private ChatModel $chatModel;
    private MessageModel $messageModel;
    private AiModelModel $aiModelModel;

    public function __construct()
    {
        $this->chatModel     = new ChatModel();
        $this->messageModel  = new MessageModel();
        $this->aiModelModel  = new AiModelModel();
    }

    /**
     * Helper to instantiate the unified AtomBrain component.
     */
    private function getAtomBrain(): \Atom\Brain\AtomBrain
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));

        // Load config
        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        // DB Connection
        $dbHost = \Atom\Config\Config::get('DB_HOST', 'localhost');
        $dbName = \Atom\Config\Config::get('DB_NAME', 'atom_assistant');
        $dbUser = \Atom\Config\Config::get('DB_USER', 'root');
        $dbPass = \Atom\Config\Config::get('DB_PASSWORD', '');
        $dbPort = \Atom\Config\Config::get('DB_PORT', '3306');

        $dbConnection = new \Atom\Database\Connection(
            $dbHost ?: 'localhost',
            $dbName ?: 'atom_assistant',
            $dbUser ?: 'root',
            $dbPass ?: '',
            $dbPort ?: '3306'
        );

        // Memory Manager
        $memory = new \Atom\Memory\MemoryManager($dbConnection, $workspaceRoot);

        // Security & Scanner
        $guard = new \Atom\Security\WorkspaceGuard($workspaceRoot);
        $policy = new \Atom\Security\FilePolicy();
        $redactor = new \Atom\Security\SecretRedactor();
        $scanner = new \Atom\Project\ProjectScanner($workspaceRoot);
        $searcher = new \Atom\Project\CodeSearch($workspaceRoot, $scanner);

        // Tools
        $readFileTool = new \Atom\Tools\ReadFileTool($guard, $policy, $redactor);
        $searchCodeTool = new \Atom\Tools\SearchCodeTool($scanner, $searcher, $redactor);
        $phpLintTool = new \Atom\Tools\PhpLintTool($guard);
        $createFileTool = new \Atom\Tools\CreateFileTool($guard, $policy, $phpLintTool);
        $patchFileTool = new \Atom\Tools\PatchFileTool($guard, $policy, $phpLintTool, $workspaceRoot);

        $toolManager = new \Atom\Tools\ToolManager();
        $toolManager->registerTool($readFileTool);
        $toolManager->registerTool($searchCodeTool);
        $toolManager->registerTool($phpLintTool);
        $toolManager->registerTool($createFileTool);
        $toolManager->registerTool($patchFileTool);

        // Knowledge Search
        $kSearch = new \Atom\Knowledge\KnowledgeSearch($dbConnection);

        // Model Manager & Providers
        $modelManager = new \Atom\PersonalModel\ModelManager();

        $temperature = (float)\Atom\Config\Config::get('LLM_TEMPERATURE', 0.7);
        $maxTokens = (int)\Atom\Config\Config::get('LLM_MAX_TOKENS', 2048);

        $activeProvider = strtolower(\Atom\Config\Config::get('LLM_PROVIDER', 'groq'));
        $genericKey = \Atom\Config\Config::get('LLM_API_KEY');
        $genericUrl = \Atom\Config\Config::get('LLM_API_URL');
        $genericModel = \Atom\Config\Config::get('LLM_MODEL');

        $hasProvider = false;

        // --- Groq (OpenAI-compatible) ---
        $groqKey = \Atom\Config\Config::get('GROQ_API_KEY') ?: ($activeProvider === 'groq' ? $genericKey : '');
        $groqUrl = \Atom\Config\Config::get('GROQ_API_URL') ?: ($activeProvider === 'groq' && !empty($genericUrl) ? $genericUrl : 'https://api.groq.com/openai/v1');
        $groqModel = \Atom\Config\Config::get('GROQ_MODEL') ?: ($activeProvider === 'groq' ? $genericModel : 'openai/gpt-oss-120b');
        if (!empty($groqKey)) {
            $groqProvider = new \Atom\LLM\OpenAIProvider($groqKey, $groqUrl, $groqModel, $temperature, $maxTokens);
            $modelManager->registerModel('groq', new \Atom\PersonalModel\GeminiModel($groqProvider, $groqModel, 'Groq'));
            if ($activeProvider === 'groq') {
                $modelManager->setRole('primary', 'groq');
                $hasProvider = true;
            }
        }

        // --- Gemini ---
        $geminiKey = \Atom\Config\Config::get('GEMINI_API_KEY') ?: ($activeProvider === 'gemini' ? $genericKey : '');
        $geminiUrl = \Atom\Config\Config::get('GEMINI_API_URL') ?: ($activeProvider === 'gemini' && !empty($genericUrl) ? $genericUrl : 'https://generativelanguage.googleapis.com/v1beta');
        $geminiModel = \Atom\Config\Config::get('GEMINI_MODEL') ?: ($activeProvider === 'gemini' ? $genericModel : 'gemini-3.6-flash');
        if (!empty($geminiKey)) {
            $geminiProvider = new \Atom\LLM\GeminiProvider($geminiKey, $geminiUrl, $geminiModel, $temperature, $maxTokens);
            $modelManager->registerModel('gemini', new \Atom\PersonalModel\GeminiModel($geminiProvider, $geminiModel, 'Gemini'));
            if ($activeProvider === 'gemini') {
                $modelManager->setRole('primary', 'gemini');
                $hasProvider = true;
            }
        }

        // --- OpenAI ---
        $openaiKey = \Atom\Config\Config::get('OPENAI_API_KEY') ?: ($activeProvider === 'openai' ? $genericKey : '');
        $openaiUrl = \Atom\Config\Config::get('OPENAI_API_URL') ?: ($activeProvider === 'openai' && !empty($genericUrl) ? $genericUrl : 'https://api.openai.com/v1');
        $openaiModel = \Atom\Config\Config::get('OPENAI_MODEL') ?: ($activeProvider === 'openai' ? $genericModel : 'gpt-4o-mini');
        if (!empty($openaiKey)) {
            $openaiProvider = new \Atom\LLM\OpenAIProvider($openaiKey, $openaiUrl, $openaiModel, $temperature, $maxTokens);
            $modelManager->registerModel('openai', new \Atom\PersonalModel\GeminiModel($openaiProvider, $openaiModel, 'OpenAI'));
            if ($activeProvider === 'openai') {
                $modelManager->setRole('primary', 'openai');
                $hasProvider = true;
            }
        }

        // --- Anthropic ---
        $anthropicKey = \Atom\Config\Config::get('ANTHROPIC_API_KEY') ?: ($activeProvider === 'anthropic' ? $genericKey : '');
        $anthropicUrl = \Atom\Config\Config::get('ANTHROPIC_API_URL') ?: ($activeProvider === 'anthropic' && !empty($genericUrl) ? $genericUrl : 'https://api.anthropic.com/v1');
        $anthropicModel = \Atom\Config\Config::get('ANTHROPIC_MODEL') ?: ($activeProvider === 'anthropic' ? $genericModel : 'claude-3-5-sonnet-20241022');
        if (!empty($anthropicKey)) {
            $anthropicProvider = new \Atom\LLM\OpenAIProvider($anthropicKey, $anthropicUrl, $anthropicModel, $temperature, $maxTokens);
            $modelManager->registerModel('anthropic', new \Atom\PersonalModel\GeminiModel($anthropicProvider, $anthropicModel, 'Anthropic'));
            if ($activeProvider === 'anthropic') {
                $modelManager->setRole('primary', 'anthropic');
                $hasProvider = true;
            }
        }

        // Register local fallback model (Ollama) — used when primary provider fails or is unconfigured
        $localEndpoint = \Atom\Config\Config::get('LLM_LOCAL_ENDPOINT', 'http://localhost:11434/v1');
        $localModelName = \Atom\Config\Config::get('LLM_LOCAL_MODEL', 'llama3.1');
        $localModel = new \Atom\PersonalModel\AtomLocalModel($localEndpoint, $localModelName, 'Ollama');
        $modelManager->registerModel('local', $localModel);

        if (!$hasProvider) {
            $modelManager->setRole('primary', 'local');
        } else {
            $modelManager->setRole('fallback', 'local');
        }

        // Personal Model
        $personalModel = new \Atom\PersonalModel\AtomPersonalModel(
            $dbConnection,
            $memory->getProjectId(),
            $memory->getSessionId()
        );

        $profileManager = new \Atom\PersonalModel\OwnerProfileManager($dbConnection);

        // Brain Services
        $detector = new \Atom\Brain\IntentDetector();
        $contextBuilder = new \Atom\Brain\ContextBuilder($workspaceRoot);

        return new \Atom\Brain\AtomBrain(
            $modelManager,
            $detector,
            $contextBuilder,
            $scanner,
            $redactor,
            $memory,
            $kSearch,
            $toolManager,
            $personalModel,
            $profileManager
        );
    }

    public function directComplete(string $model, string $provider, string $message): array
    {
        $brain = $this->getAtomBrain();
        $history = [];
        $responseContent = $brain->process($message, $history, $provider, $model);
        return [
            'success' => true,
            'data'    => [
                'content' => $responseContent
            ]
        ];
    }

    public function process(int $chatId, string $message, ?int $userId = null): array
    {
        $chat = $this->chatModel->find($chatId);
        if (!$chat) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        // Data isolation: a chat may only be answered by its owner.
        if ($userId !== null && (int) $chat->user_id !== $userId) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        $this->messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'user',
            'content' => $message,
        ]);

        $dbHistory = $this->messageModel
            ->where('chat_id', $chatId)
            ->where('id !=', $this->messageModel->getInsertID())
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Limit history sent to the model to the last 30 messages (15 turns)
        // to avoid token bloat and context drift on long conversations.
        $dbHistory = array_slice($dbHistory, -30);

        $history = [];
        foreach ($dbHistory as $msg) {
            $history[] = [
                'role'    => $msg->role === 'assistant' ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        $brain = $this->getAtomBrain();
        $responseContent = $brain->process($message, $history, $chat->provider, $chat->model, $chatId);

        $this->messageModel->insert([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role'    => 'assistant',
            'content' => $responseContent,
            'model'   => $chat->model,
        ]);

        return [
            'success' => true,
            'data'    => [
                'role'    => 'assistant',
                'content' => $responseContent,
            ],
        ];
    }

    public function processPreview(int $chatId, string $message, ?int $userId = null): array
    {
        return $this->process($chatId, $message, $userId);
    }
}
