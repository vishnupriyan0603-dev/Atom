<?php

namespace App\Controllers\Api;

use App\Services\KnowledgeService;

class Knowledge extends BaseApiController
{
    private KnowledgeService $knowledgeService;

    public function __construct()
    {
        $this->knowledgeService = new KnowledgeService();
    }

    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?? 50);
        return $this->respondSuccess($this->knowledgeService->getAll($perPage));
    }

    public function show($id = null)
    {
        $item = $this->knowledgeService->getById((int) $id);
        if (!$item) {
            return $this->respondError('Knowledge item not found', 404);
        }
        return $this->respondSuccess($item);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || empty($data['title'])) {
            return $this->respondError('Title is required');
        }
        $id = $this->knowledgeService->create($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $updated = $this->knowledgeService->update((int) $id, $data ?? []);
        if (!$updated) {
            return $this->respondError('Knowledge item not found', 404);
        }
        return $this->respondSuccess(null, 'Updated successfully');
    }

    public function delete($id = null)
    {
        $deleted = $this->knowledgeService->delete((int) $id);
        if (!$deleted) {
            return $this->respondError('Knowledge item not found', 404);
        }
        return $this->respondNoContent();
    }
    /**
     * Retrieves all documents from atom_documents table.
     */
    public function documents()
    {
        $dbConnection = $this->getDbConnection();
        if (!$dbConnection->isConnected()) {
            return $this->respondError('Database is offline');
        }

        try {
            $stmt = $dbConnection->getPdo()->query("SELECT * FROM atom_documents ORDER BY id DESC");
            $docs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $this->respondSuccess($docs);
        } catch (\Exception $e) {
            return $this->respondError('Failed to fetch documents: ' . $e->getMessage());
        }
    }

    /**
     * Handles multipart document uploads, runs PDF extraction, splits content,
     * and saves chunks in database.
     */
    public function upload()
    {
        $file = $this->request->getFile('doc_file');
        if (!$file || !$file->isValid()) {
            $err = $file ? $file->getErrorString() . ' (' . $file->getError() . ')' : 'No file object';
            log_message('error', '[ATOM UPLOAD] File validation failed: ' . $err);
            return $this->respondError('No valid document file uploaded: ' . $err);
        }

        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        log_message('info', '[ATOM UPLOAD] Processing uploaded file: ' . $file->getClientName());

        try {
            // Ensure temp directory exists
            $tempDir = $workspaceRoot . '/storage/temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFilename = $file->getRandomName();
            if (!$file->move($tempDir, $tempFilename)) {
                log_message('error', '[ATOM UPLOAD] Failed to move file to temp folder.');
                return $this->respondError('Failed to save uploaded file locally.');
            }

            $uploadedFilePath = $tempDir . '/' . $tempFilename;

            // Rename/copy safely to maintain original filename extension for DocumentImporter
            $originalExt = pathinfo($file->getClientName(), PATHINFO_EXTENSION);
            $finalTempPath = $tempDir . '/' . pathinfo($tempFilename, PATHINFO_FILENAME) . '.' . $originalExt;
            rename($uploadedFilePath, $finalTempPath);

            // Run DocumentImporter pipeline
            $dbConnection = $this->getDbConnection();
            $extractor    = new \Atom\Knowledge\PdfExtractor();
            $chunker      = new \Atom\Knowledge\Chunker();
            $guard        = new \Atom\Security\WorkspaceGuard($workspaceRoot);

            $importer = new \Atom\Knowledge\DocumentImporter(
                $dbConnection,
                $extractor,
                $chunker,
                $guard,
                $workspaceRoot
            );

            $result = $importer->import($finalTempPath);

            // Clean up temporary file
            if (file_exists($finalTempPath)) {
                unlink($finalTempPath);
            }

            if (!$result['success']) {
                log_message('error', '[ATOM UPLOAD] Importer failed: ' . ($result['error'] ?? 'Unknown error'));
                return $this->respondError('Import process failed: ' . ($result['error'] ?? 'Unknown error'), 500);
            }

            log_message('info', '[ATOM UPLOAD] Successfully indexed: ' . $file->getClientName());

            return $this->respondSuccess([
                'document_id'  => $result['document_id'],
                'chunks_count' => $result['chunks_count'],
            ], 'Document processed and indexed successfully');

        } catch (\Throwable $e) {
            log_message('error', '[ATOM UPLOAD] Exception thrown: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->respondError('System Exception during import: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Deletes a document from atom_documents (and its chunks cascadingly).
     */
    public function deleteDocument($id = null)
    {
        if (!$id) {
            return $this->respondError('Document ID is required');
        }

        $dbConnection = $this->getDbConnection();
        if (!$dbConnection->isConnected()) {
            return $this->respondError('Database is offline');
        }

        $pdo = $dbConnection->getPdo();
        try {
            // Retrieve path to clean up the stored file
            $stmt = $pdo->prepare("SELECT path FROM atom_documents WHERE id = ?");
            $stmt->execute([(int)$id]);
            $path = $stmt->fetchColumn();

            if (!$path) {
                return $this->respondError('Document not found', 404);
            }

            $pdo->beginTransaction();

            // Chunks table has foreign key constraint, but let's delete manually to be safe
            $delChunks = $pdo->prepare("DELETE FROM atom_document_chunks WHERE document_id = ?");
            $delChunks->execute([(int)$id]);

            $delDoc = $pdo->prepare("DELETE FROM atom_documents WHERE id = ?");
            $delDoc->execute([(int)$id]);

            $pdo->commit();

            // Clean up original file from storage
            if ($path && file_exists($path)) {
                unlink($path);
            }

            return $this->respondSuccess(null, 'Document deleted successfully');

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $this->respondError('Failed to delete document: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reads a document's text page-by-page, sends each page to the configured
     * AI provider for understanding, synthesizes the results, and stores the
     * final structured summary in ai_summary.
     */
    public function trainDocument($id = null)
    {
        // Page-by-page training can take several minutes on large PDFs
        @set_time_limit(600);
        ini_set('max_execution_time', '600');

        if (!$id) {
            return $this->respondError('Document ID is required');
        }

        $dbConnection = $this->getDbConnection();
        if (!$dbConnection->isConnected()) {
            return $this->respondError('Database is offline');
        }

        $pdo = $dbConnection->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM atom_documents WHERE id = ?");
        $stmt->execute([(int)$id]);
        $doc = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$doc || empty($doc['path'])) {
            return $this->respondError('Document not found', 404);
        }

        $path = $doc['path'];
        if (!file_exists($path)) {
            return $this->respondError('Source file missing on disk: ' . $path, 500);
        }

        // 1. Extract pages (page_number => page_text)
        try {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $extractor = new \Atom\Knowledge\PdfExtractor();
                $pages = $extractor->extract($path);
            } else {
                $raw = (string) @file_get_contents($path);
                $pages = (trim($raw) !== '') ? [1 => trim($raw)] : [];
            }
        } catch (\Throwable $e) {
            return $this->respondError('Text extraction failed: ' . $e->getMessage(), 500);
        }

        if (empty($pages)) {
            return $this->respondError('No readable text could be extracted from this document.', 500);
        }

        // Cap the number of pages processed to keep training time reasonable.
        $maxPages = 50;
        if (count($pages) > $maxPages) {
            $pages = array_slice($pages, 0, $maxPages, true);
        }
        $pageCount = count($pages);

        // 2. Read page-by-page with the AI provider for understanding.
        try {
            if ($pageCount === 1) {
                $pageNum = array_key_first($pages);
                $understanding = $this->understandPage($doc, (int)$pageNum, $pages[$pageNum], true);
            } else {
                $pageNotes = [];
                foreach ($pages as $pageNum => $pageText) {
                    $note = $this->understandPage($doc, (int)$pageNum, $pageText, false);
                    $pageNotes[] = "--- PAGE {$pageNum} ---\n" . $note;
                }
                $understanding = $this->synthesizeUnderstanding($doc, $pageNotes, $pageCount);
            }
        } catch (\Throwable $e) {
            return $this->respondError('AI understanding failed: ' . $e->getMessage(), 500);
        }

        // 3. Persist summary
        $up = $pdo->prepare("UPDATE atom_documents SET ai_summary = ?, trained_at = NOW() WHERE id = ?");
        $up->execute([$understanding, (int)$id]);

        return $this->respondSuccess([
            'document_id'   => (int)$id,
            'ai_summary'    => $understanding,
            'trained_at'    => date('Y-m-d H:i:s'),
            'pages_read'    => $pageCount,
        ], "Document trained successfully. Atom read {$pageCount} page(s) and now understands its content.");
    }

    /**
     * Sends a single page's text to the AI provider and returns that page's understanding.
     */
    private function understandPage(array $doc, int $pageNumber, string $pageText, bool $singlePage): string
    {
        $context = $singlePage
            ? 'This is the entire document. Analyze it and produce the full structured "understanding".'
            : 'This is one page of a larger document. Produce a concise understanding of ONLY this page: key topics and important facts on this page. Keep it tight (max ~250 words).';

        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are ATOM, a personal AI assistant. A user uploaded a document titled "' . $doc['title'] . '". '
                    . $context . ' '
                    . 'Return ONLY plain text. Do not wrap it in quotes or markdown fences.',
            ],
            [
                'role'    => 'user',
                'content' => "Page {$pageNumber} content:\n\n" . mb_substr($pageText, 0, 12000),
            ],
        ];

        $res = $this->chatWithRetry($this->buildProvider(), $messages);

        if (empty($res['success'])) {
            throw new \RuntimeException('Page ' . $pageNumber . ' failed: ' . ($res['error'] ?? 'Unknown AI error'));
        }

        return trim($res['content']);
    }

    /**
     * Sends all per-page notes to the AI provider to build the final structured summary.
     */
    private function synthesizeUnderstanding(array $doc, array $pageNotes, int $pageCount): string
    {
        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are ATOM, a personal AI assistant. A document titled "' . $doc['title'] . '" ('
                    . $pageCount . ' pages) was read page-by-page. Below are per-page understanding notes. '
                    . 'Combine them into the final structured "understanding" so ATOM can answer questions about the whole document later. '
                    . 'Return ONLY plain text with these sections:\n'
                    . '## Summary\n2-4 sentences describing what the document is about.\n'
                    . '## Key Topics\nBullet list of the main topics covered.\n'
                    . '## Important Facts\nBullet list of key facts, numbers, definitions, or instructions.\n'
                    . '## How To Use\nA short note for ATOM on how to apply this knowledge when answering user questions.',
            ],
            [
                'role'    => 'user',
                'content' => "Per-page notes:\n\n" . implode("\n\n", $pageNotes),
            ],
        ];

        $res = $this->chatWithRetry($this->buildProvider(), $messages);

        if (empty($res['success'])) {
            throw new \RuntimeException($res['error'] ?? 'Unknown AI error');
        }

        return trim($res['content']);
    }

    /**
     * Calls provider->chat() with automatic retry on 429 (rate limit) responses.
     */
    private function chatWithRetry(\Atom\LLM\LLMInterface $provider, array $messages, int $maxRetries = 3): array
    {
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $res = $provider->chat($messages);

            if (!empty($res['success'])) {
                return $res;
            }

            // Retry only on HTTP 429 / rate-limit errors
            $err = $res['error'] ?? '';
            if (stripos($err, '429') === false && stripos($err, 'rate limit') === false) {
                return $res;
            }

            // Extract suggested wait time (e.g. "try again in 9.405s")
            $wait = 2;
            if (preg_match('/again in ([\d.]+)s/i', $err, $m)) {
                $wait = (float)$m[1];
            }
            $wait = max(1, min($wait + 1, 20));
            usleep((int)($wait * 1000000));
        }

        return $res;
    }

    /**
     * Builds the active LLM provider from environment config.
     */
    private function buildProvider(): \Atom\LLM\LLMInterface
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));

        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        $activeProvider = strtolower(\Atom\Config\Config::get('LLM_PROVIDER', 'groq'));
        $temperature = (float)\Atom\Config\Config::get('LLM_TEMPERATURE', 0.7);
        $maxTokens = (int)\Atom\Config\Config::get('LLM_MAX_TOKENS', 4096);
        // Long timeout (120s) since training requests process large page content
        $timeout = (int)\Atom\Config\Config::get('LLM_TIMEOUT', 120);

        if ($activeProvider === 'gemini') {
            $key = \Atom\Config\Config::get('GEMINI_API_KEY');
            $url = \Atom\Config\Config::get('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta');
            $model = \Atom\Config\Config::get('GEMINI_MODEL', 'gemini-3.6-flash');
            return new \Atom\LLM\GeminiProvider($key ?: '', $url, $model, $temperature, $maxTokens, $timeout);
        }

        if ($activeProvider === 'openai') {
            $key = \Atom\Config\Config::get('OPENAI_API_KEY');
            $url = \Atom\Config\Config::get('OPENAI_API_URL', 'https://api.openai.com/v1');
            $model = \Atom\Config\Config::get('OPENAI_MODEL', 'gpt-4o-mini');
            return new \Atom\LLM\OpenAIProvider($key ?: '', $url, $model, $temperature, $maxTokens, $timeout);
        }

        // Default to Groq
        $key = \Atom\Config\Config::get('GROQ_API_KEY');
        $url = \Atom\Config\Config::get('GROQ_API_URL', 'https://api.groq.com/openai/v1');
        $model = \Atom\Config\Config::get('GROQ_MODEL', 'openai/gpt-oss-120b');
        return new \Atom\LLM\OpenAIProvider($key ?: '', $url, $model, $temperature, $maxTokens, $timeout);
    }

    private function getDbConnection(): \Atom\Database\Connection
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));

        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        $dbHost = \Atom\Config\Config::get('DB_HOST', 'localhost');
        $dbName = \Atom\Config\Config::get('DB_NAME', 'atom_assistant');
        $dbUser = \Atom\Config\Config::get('DB_USER', 'root');
        $dbPass = \Atom\Config\Config::get('DB_PASSWORD', '');
        $dbPort = \Atom\Config\Config::get('DB_PORT', '3306');

        return new \Atom\Database\Connection(
            $dbHost ?: 'localhost',
            $dbName ?: 'atom_assistant',
            $dbUser ?: 'root',
            $dbPass ?: '',
            $dbPort ?: '3306'
        );
    }
}
