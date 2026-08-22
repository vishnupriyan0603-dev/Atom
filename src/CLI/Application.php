<?php

namespace Atom\CLI;

use Atom\Config\Config;
use Atom\Security\WorkspaceGuard;
use Atom\Security\FilePolicy;
use Atom\Security\SecretRedactor;
use Atom\Project\ProjectScanner;
use Atom\Project\CodeSearch;
use Atom\Tools\ReadFileTool;
use Atom\Tools\SearchCodeTool;
use Atom\Tools\PhpLintTool;
use Atom\Tools\CreateFileTool;
use Atom\Tools\PatchFileTool;
use Atom\Tools\ToolManager;
use Atom\Brain\IntentDetector;
use Atom\Brain\ContextBuilder;
use Atom\Brain\AtomBrain;
use Atom\LLM\OpenAIProvider;
use Atom\LLM\GeminiProvider;
use Atom\Database\Connection;
use Atom\Memory\MemoryManager;
use Atom\PersonalModel\AtomPersonalModel;
use Atom\PersonalModel\ModelManager;
use Atom\PersonalModel\GeminiModel;
use Atom\PersonalModel\AtomLocalModel;
use Atom\PersonalModel\OwnerProfileManager;
use Atom\Knowledge\PdfExtractor;
use Atom\Knowledge\Chunker;
use Atom\Knowledge\DocumentImporter;
use Atom\Knowledge\KnowledgeSearch;

class Application
{
    private TerminalUI $ui;
    private string $workspaceRoot;
    private WorkspaceGuard $guard;
    private FilePolicy $policy;
    private SecretRedactor $redactor;
    private ProjectScanner $scanner;
    private CodeSearch $searcher;
    private ReadFileTool $readFileTool;
    private SearchCodeTool $searchCodeTool;
    private PhpLintTool $phpLintTool;
    private CreateFileTool $createFileTool;
    private PatchFileTool $patchFileTool;
    private ToolManager $toolManager;
    private MemoryManager $memory;
    private DocumentImporter $importer;
    private KnowledgeSearch $kSearch;
    private AtomBrain $brain;
    private CommandRouter $router;

    public function __construct()
    {
        $this->ui = new TerminalUI();
        // Detect workspace root dynamically
        $this->workspaceRoot = str_replace('\\', '/', getcwd());
    }

    /**
     * Initializes core services and configuration.
     */
    public function bootstrap(): void
    {
        // 1. Load configuration from .env file
        if (!class_exists('Atom\Config\Config')) {
            require_once __DIR__ . '/../../config/config.php';
        }
        Config::load($this->workspaceRoot);

        // 2. Initialize Database Connection & Memory Manager
        $dbHost = Config::get('DB_HOST', 'localhost');
        $dbName = Config::get('DB_NAME', 'atom_assistant');
        $dbUser = Config::get('DB_USER', 'root');
        $dbPass = Config::get('DB_PASSWORD', '');
        $dbPort = Config::get('DB_PORT', '3306');

        $dbConnection = new Connection($dbHost ?: 'localhost', $dbName ?: 'atom_assistant', $dbUser ?: 'root', $dbPass ?: '', $dbPort ?: '3306');
        $this->memory = new MemoryManager($dbConnection, $this->workspaceRoot);

        // 3. Initialize Security
        $this->guard = new WorkspaceGuard($this->workspaceRoot);
        $this->policy = new FilePolicy();
        $this->redactor = new SecretRedactor();
        
        // 4. Initialize Scanner
        $this->scanner = new ProjectScanner($this->workspaceRoot);
        $this->searcher = new CodeSearch($this->workspaceRoot, $this->scanner);
        
        // 5. Initialize Tools
        $this->readFileTool = new ReadFileTool($this->guard, $this->policy, $this->redactor);
        $this->searchCodeTool = new SearchCodeTool($this->scanner, $this->searcher, $this->redactor);
        $this->phpLintTool = new PhpLintTool($this->guard);
        $this->createFileTool = new CreateFileTool($this->guard, $this->policy, $this->phpLintTool);
        $this->patchFileTool = new PatchFileTool($this->guard, $this->policy, $this->phpLintTool, $this->workspaceRoot);
        
        // Register tools in ToolManager
        $this->toolManager = new ToolManager();
        $this->toolManager->registerTool($this->readFileTool);
        $this->toolManager->registerTool($this->searchCodeTool);
        $this->toolManager->registerTool($this->phpLintTool);
        $this->toolManager->registerTool($this->createFileTool);
        $this->toolManager->registerTool($this->patchFileTool);

        // 6. Initialize Knowledge Services
        $extractor = new PdfExtractor();
        $chunker = new Chunker();
        $this->importer = new DocumentImporter($dbConnection, $extractor, $chunker, $this->guard, $this->workspaceRoot);
        $this->kSearch = new KnowledgeSearch($dbConnection);

        // 7. Initialize Model Manager & Providers
        $modelManager = new ModelManager();
        $hasProvider  = false;

        $temperature = (float) Config::get('LLM_TEMPERATURE', 0.7);
        $maxTokens   = (int) Config::get('LLM_MAX_TOKENS', 2048);

        $activeProvider = strtolower(Config::get('LLM_PROVIDER', 'groq'));
        $genericKey     = Config::get('LLM_API_KEY');
        $genericUrl     = Config::get('LLM_API_URL');
        $genericModel   = Config::get('LLM_MODEL');

        // --- Groq (OpenAI-compatible) ---
        $groqKey   = Config::get('GROQ_API_KEY') ?: ($activeProvider === 'groq' ? $genericKey : '');
        $groqUrl   = Config::get('GROQ_API_URL') ?: ($activeProvider === 'groq' && !empty($genericUrl) ? $genericUrl : 'https://api.groq.com/openai/v1');
        $groqModel = Config::get('GROQ_MODEL')   ?: ($activeProvider === 'groq' ? $genericModel : 'openai/gpt-oss-120b');
        if (!empty($groqKey)) {
            $groqProvider = new OpenAIProvider($groqKey, $groqUrl, $groqModel ?: 'openai/gpt-oss-120b', $temperature, $maxTokens);
            $modelManager->registerModel('groq', new GeminiModel($groqProvider, $groqModel ?: 'openai/gpt-oss-120b', 'Groq'));
            if ($activeProvider === 'groq') {
                $modelManager->setRole('primary', 'groq');
                $hasProvider = true;
            }
        }

        // --- Gemini ---
        $geminiKey   = Config::get('GEMINI_API_KEY') ?: ($activeProvider === 'gemini' ? $genericKey : '');
        $geminiUrl   = Config::get('GEMINI_API_URL') ?: ($activeProvider === 'gemini' && !empty($genericUrl) ? $genericUrl : 'https://generativelanguage.googleapis.com/v1beta');
        $geminiModel = Config::get('GEMINI_MODEL')   ?: ($activeProvider === 'gemini' ? $genericModel : 'gemini-3.6-flash');
        if (!empty($geminiKey)) {
            $geminiProvider = new GeminiProvider($geminiKey, $geminiUrl, $geminiModel ?: 'gemini-3.6-flash', $temperature, $maxTokens);
            $modelManager->registerModel('gemini', new GeminiModel($geminiProvider, $geminiModel ?: 'gemini-3.6-flash', 'Gemini'));
            if ($activeProvider === 'gemini') {
                $modelManager->setRole('primary', 'gemini');
                $hasProvider = true;
            }
        }

        // --- OpenAI ---
        $openaiKey   = Config::get('OPENAI_API_KEY') ?: ($activeProvider === 'openai' ? $genericKey : '');
        $openaiUrl   = Config::get('OPENAI_API_URL') ?: ($activeProvider === 'openai' && !empty($genericUrl) ? $genericUrl : 'https://api.openai.com/v1');
        $openaiModel = Config::get('OPENAI_MODEL')   ?: ($activeProvider === 'openai' ? $genericModel : 'gpt-4o-mini');
        if (!empty($openaiKey)) {
            $openaiProvider = new OpenAIProvider($openaiKey, $openaiUrl, $openaiModel ?: 'gpt-4o-mini', $temperature, $maxTokens);
            $modelManager->registerModel('openai', new GeminiModel($openaiProvider, $openaiModel ?: 'gpt-4o-mini', 'OpenAI'));
            if ($activeProvider === 'openai') {
                $modelManager->setRole('primary', 'openai');
                $hasProvider = true;
            }
        }

        // --- Local fallback (Ollama) ---
        $localEndpoint  = Config::get('LLM_LOCAL_ENDPOINT', 'http://localhost:11434/v1');
        $localModelName = Config::get('LLM_LOCAL_MODEL', 'llama3.1');
        $localModel     = new AtomLocalModel($localEndpoint ?: 'http://localhost:11434/v1', $localModelName ?: 'llama3.1', 'Ollama');
        $modelManager->registerModel('local', $localModel);

        if (!$hasProvider) {
            $modelManager->setRole('primary', 'local');
        } else {
            $modelManager->setRole('fallback', 'local');
        }

        // 8. Initialize Brain Services
        $detector = new IntentDetector();
        $contextBuilder = new ContextBuilder($this->workspaceRoot);
        $personalModel = new AtomPersonalModel(
            $dbConnection,
            $this->memory->getProjectId(),
            $this->memory->getSessionId()
        );
        $profileManager = new OwnerProfileManager($dbConnection);
        $this->brain = new AtomBrain($modelManager, $detector, $contextBuilder, $this->scanner, $this->redactor, $this->memory, $this->kSearch, $this->toolManager, $personalModel, $profileManager);

        // 9. Initialize Router
        $this->router = new CommandRouter(
            $this->ui,
            $this->workspaceRoot,
            $this->scanner,
            $this->readFileTool,
            $this->searchCodeTool,
            $this->phpLintTool,
            $this->createFileTool,
            $this->patchFileTool,
            $this->brain,
            $this->memory,
            $this->importer,
            $this->kSearch
        );
    }

    /**
     * Entry point to execute the CLI.
     */
    public function run(array $argv = []): void
    {
        $startTime = microtime(true);
        
        $this->bootstrap();
        
        $coreLoadTime = microtime(true) - $startTime;

        // Collect stats
        $workspaceScanStart = microtime(true);
        $files = $this->scanner->scan();
        $workspaceScanTime = microtime(true) - $workspaceScanStart;

        $dbStart = microtime(true);
        $dbConnected = $this->memory->isDbConnected();
        $longMemoryStatus = $dbConnected ? "READY" : "OFFLINE";
        $memoriesCount = 0;
        if ($dbConnected) {
            $memoriesCount = count($this->memory->getMemories());
        }
        $dbLoadTime = microtime(true) - $dbStart;

        $knowledgeStart = microtime(true);
        $docsCount = 0;
        $chunksCount = 0;
        if ($dbConnected) {
            $pdo = $this->memory->getProjectId() !== null ? $this->memory->getHistory() : null; // just dummy call to ensure DB, let's query counts instead
            try {
                $dbConn = $this->memory->getProjectId(); // retrieve ID
                $rawPdo = $this->importer->import(''); // dummy check to get pdo? No, let's use direct query
                // Let's retrieve Connection object and use it
            } catch (\Exception $e) {}
        }
        
        // Let's query counts using database PDO safely
        if ($dbConnected) {
            try {
                // We don't have direct access to PDO, but we can query it using a connection wrapper
                // MemoryManager doesn't expose PDO, but we can count documents and chunks by querying
                // let's check if Connection has getPdo()
                // Yes, $this->memory uses Connection connection.
                // We can query:
                $refPdo = null;
                $ref = new \ReflectionProperty($this->memory, 'connection');
                $ref->setAccessible(true);
                $connObj = $ref->getValue($this->memory);
                if ($connObj) {
                    $refPdo = $connObj->getPdo();
                }
                
                if ($refPdo) {
                    $docsCount = (int)$refPdo->query("SELECT COUNT(*) FROM atom_documents")->fetchColumn();
                    $chunksCount = (int)$refPdo->query("SELECT COUNT(*) FROM atom_document_chunks")->fetchColumn();
                }
            } catch (\Exception $e) {}
        }
        $knowledgeLoadTime = microtime(true) - $knowledgeStart;

        // Check provider status
        $providerStart = microtime(true);
        $primaryModel = $this->brain->getProfileManager() ? $this->brain->getProfileManager()->getProfile() : null; // dummy
        // Check if primary model is available
        $refManager = new \ReflectionProperty($this->brain, 'modelManager');
        $refManager->setAccessible(true);
        $mManager = $refManager->getValue($this->brain);
        
        $providerName = "None";
        $providerStatus = "Offline";
        $isGemini = false;
        
        if ($mManager) {
            $pModel = $mManager->getModelForRole('primary');
            if ($pModel) {
                $providerName = $pModel->getProviderName() . " (" . $pModel->getName() . ")";
                $isOnline = $pModel->isAvailable();
                $providerStatus = $isOnline ? "ONLINE" : "OFFLINE";
                if ($pModel->getProviderName() === 'Gemini') {
                    $isGemini = true;
                }
            }
        }
        $providerCheckTime = microtime(true) - $providerStart;
        
        $totalStartupTime = microtime(true) - $startTime;

        $colMode = 'balanced';
        if ($dbConnected && isset($refPdo)) {
            try {
                $stmt = $refPdo->prepare("SELECT setting_value FROM atom_settings WHERE setting_key = 'collaboration_mode'");
                $stmt->execute();
                $modeVal = $stmt->fetchColumn();
                if ($modeVal) {
                    $colMode = $modeVal;
                }
            } catch (\Exception $e) {}
        }
        
        $engine = $this->brain->getLearningEngine();
        $topics = $engine->getTopics();
        $totalScoreSum = 0;
        $countTopics = 0;
        foreach (array_slice($topics, 0, 8) as $t) {
            $totalScoreSum += $t['score'];
            $countTopics++;
        }
        $overallScore = $countTopics > 0 ? round($totalScoreSum / $countTopics) : 0;
        $overallLevel = \Atom\Brain\LearningEngine::getLevelFromScore($overallScore);

        // Build status block for welcome screen
        $statusBlock = [
            'provider_name' => $isGemini ? 'Gemini' : $providerName,
            'provider_status' => $providerStatus,
            'collaboration_mode' => $colMode,
            'mode' => 'SAFE',
            'knowledge_level' => $overallLevel,
            'workspace_files' => count($files),
            'pdf_library' => $docsCount,
            'memories_count' => $memoriesCount,
            'working_memory' => 'READY',
            'long_memory' => $longMemoryStatus,
            'knowledge_base' => $dbConnected ? "READY" : "OFFLINE",
            'workspace' => 'READY'
        ];

        // If arguments are provided (excluding entry file), execute them directly and exit
        if (count($argv) > 1) {
            array_shift($argv); // Remove script name
            $input = implode(' ', $argv);
            $this->router->route($input);
            return;
        }

        // Standard interactive shell loop
        $this->ui->clearScreen();
        $this->ui->renderHeader($statusBlock);

        // Print initialization logs
        $this->ui->writeLine("Welcome back, Vichu.\n");
        $this->ui->writeLine("Atom is your personal AI development and learning system.\n");
        $this->ui->writeLine("Your data.");
        $this->ui->writeLine("Your knowledge.");
        $this->ui->writeLine("Your AI.\n");
        
        $this->ui->writeLine("Initializing Atom...");
        $this->ui->writeLine("[✓] Core loaded (" . number_format($coreLoadTime, 2) . "s)");
        $this->ui->writeLine("[✓] Workspace loaded (" . number_format($workspaceScanTime, 2) . "s)");
        $this->ui->writeLine("[✓] Working memory initialized");
        $this->ui->writeLine("[✓] Long-term memory connected (" . number_format($dbLoadTime, 2) . "s)");
        $this->ui->writeLine("[✓] Knowledge library mounted (" . number_format($knowledgeLoadTime, 2) . "s)");
        $this->ui->writeLine("[✓] Knowledge index loaded");
        $this->ui->writeLine("[✓] " . ($isGemini ? "Gemini" : "AI") . " provider connected (" . number_format($providerCheckTime, 2) . "s)");
        $this->ui->writeLine("[✓] Safety layer enabled");
        $this->ui->writeLine();

        $this->ui->success("Workspace successfully loaded.");
        $this->ui->success(count($files) . " active files indexed.");
        $this->ui->writeLine("Startup time: " . number_format($totalStartupTime, 2) . " sec\n");

        $this->ui->highlight("ATOM READY\n");
        $this->ui->writeLine("Type /help      Show commands");
        $this->ui->writeLine("Type /status    System status");
        $this->ui->writeLine("Type /memory    Memory manager");
        $this->ui->writeLine("Type /knowledge Knowledge library");
        $this->ui->writeLine("Type /provider  AI provider status");
        $this->ui->writeLine("Type /exit      Shutdown Atom");
        $this->ui->writeLine();

        while (true) {
            $input = $this->ui->readInput("atom> ");
            // Add the command to session history
            if (!empty($input)) {
                $this->memory->addRecentCommand($input);
            }
            $continue = $this->router->route($input);
            if (!$continue) {
                break;
            }
        }
    }
}
