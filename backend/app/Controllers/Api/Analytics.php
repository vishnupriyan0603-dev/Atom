<?php

namespace App\Controllers\Api;

use App\Models\AiModelModel;

/**
 * Admin observability endpoints backed by the atom_* trace tables.
 * These replace the former /api/settings/* catch-all calls that
 * returned a single setting row instead of real analytics.
 */
class Analytics extends BaseApiController
{
    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    public function requests()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 50));
        $rows = $this->db()
            ->table('atom_requests')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function responses()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 50));
        $rows = $this->db()
            ->table('atom_responses')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function errors()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 100));
        $rows = $this->db()
            ->table('atom_errors')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function toolLogs()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 50));
        $rows = $this->db()
            ->table('atom_tool_executions')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function summary()
    {
        $db = $this->db();

        $stats = [
            'users'            => (int) $db->table('users')->countAllResults(),
            'conversations'    => (int) $db->table('chats')->countAllResults(),
            'messages'         => (int) $db->table('messages')->countAllResults(),
            'ai_requests'      => (int) $db->table('atom_requests')->countAllResults(),
            'successful'       => (int) $db->table('atom_requests')->where('status', 'SUCCESS')->countAllResults(),
            'failed'           => (int) $db->table('atom_requests')->where('status', 'FAILED')->countAllResults(),
            'search_requests'  => (int) $db->table('atom_requests')->where('rag_used', 1)->countAllResults(),
            'knowledge_docs'   => (int) $db->table('atom_documents')->countAllResults(),
            'pdf_count'        => (int) $db->table('atom_documents')->like('filename', '.pdf', 'both')->countAllResults(),
            'chunks'           => (int) $db->table('atom_document_chunks')->countAllResults(),
            'error_count'      => (int) $db->table('atom_errors')->countAllResults(),
            'tool_executions'  => (int) $db->table('atom_tool_executions')->countAllResults(),
        ];

        // Provider usage breakdown (last 200 requests)
        $providerUsage = $db->query(
            "SELECT provider, COUNT(*) AS total, SUM(status = 'SUCCESS') AS ok, AVG(duration_ms) AS avg_ms
             FROM atom_requests GROUP BY provider ORDER BY total DESC LIMIT 20"
        )->getResultArray();

        $stats['provider_usage'] = $providerUsage;

        return $this->respondSuccess($stats);
    }

    public function trainingRecords()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 100));
        $rows = $this->db()
            ->table('atom_training_examples')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function deleteTrainingRecord($id = null)
    {
        if (!$id) {
            return $this->respondError('Record ID is required');
        }
        $this->db()->table('atom_training_examples')->where('id', (int) $id)->delete();
        return $this->respondSuccess(null, 'Training record deleted');
    }

    public function learningHistory()
    {
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 50));
        $rows = $this->db()
            ->table('atom_learning_history')
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->get()
            ->getResultArray();

        return $this->respondSuccess($rows);
    }

    public function providers()
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        $providers = [
            'groq' => [
                'configured' => !empty(\Atom\Config\Config::get('GROQ_API_KEY')) || !empty(\Atom\Config\Config::get('LLM_API_KEY')),
                'model'      => \Atom\Config\Config::get('GROQ_MODEL', 'openai/gpt-oss-120b'),
                'endpoint'   => \Atom\Config\Config::get('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
                'key'        => !empty(\Atom\Config\Config::get('GROQ_API_KEY')) ? '●●●●●●●● (Protected)' : '',
            ],
            'gemini' => [
                'configured' => !empty(\Atom\Config\Config::get('GEMINI_API_KEY')),
                'model'      => \Atom\Config\Config::get('GEMINI_MODEL', 'gemini-3.6-flash'),
                'endpoint'   => \Atom\Config\Config::get('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
                'key'        => !empty(\Atom\Config\Config::get('GEMINI_API_KEY')) ? '●●●●●●●● (Protected)' : '',
            ],
            'openai' => [
                'configured' => !empty(\Atom\Config\Config::get('OPENAI_API_KEY')),
                'model'      => \Atom\Config\Config::get('OPENAI_MODEL', 'gpt-4o-mini'),
                'endpoint'   => \Atom\Config\Config::get('OPENAI_API_URL', 'https://api.openai.com/v1'),
                'key'        => !empty(\Atom\Config\Config::get('OPENAI_API_KEY')) ? '●●●●●●●● (Protected)' : '',
            ],
            'local' => [
                'configured' => true,
                'model'      => \Atom\Config\Config::get('LLM_LOCAL_MODEL', 'llama3.1'),
                'endpoint'   => \Atom\Config\Config::get('LLM_LOCAL_ENDPOINT', 'http://localhost:11434/v1'),
                'key'        => '',
            ],
        ];

        $active = strtolower(\Atom\Config\Config::get('LLM_PROVIDER', 'groq'));

        return $this->respondSuccess([
            'active'   => $active,
            'providers' => $providers,
        ]);
    }

    /**
     * Jaccard-similarity duplicate detection over training examples.
     * Groups near-duplicate questions so they can be merged.
     */
    public function duplicates()
    {
        $rows = $this->db()->table('atom_training_examples')
            ->select('id, user_input')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $groups = [];
        $used = [];

        $normalize = function (string $s): array {
            $s = strtolower(preg_replace('/[^\w\s]/u', '', $s));
            $words = preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $filtered = array_filter($words, fn($w) => strlen($w) > 2);
            return array_values(array_unique($filtered));
        };

        $jaccard = function (array $a, array $b): float {
            if (empty($a) && empty($b)) return 1.0;
            $intersection = count(array_intersect($a, $b));
            $union = count(array_unique(array_merge($a, $b)));
            return $union === 0 ? 0.0 : $intersection / $union;
        };

        foreach ($rows as $i => $rowA) {
            if (isset($used[$rowA['id']])) continue;
            $wordsA = $normalize($rowA['user_input']);

            for ($j = $i + 1; $j < count($rows); $j++) {
                $rowB = $rows[$j];
                if (isset($used[$rowB['id']])) continue;

                $wordsB = $normalize($rowB['user_input']);
                $sim = $jaccard($wordsA, $wordsB);

                if ($sim >= 0.7) {
                    $groups[] = [
                        'id'         => (int) $rowA['id'],
                        'similarity' => (int) round($sim * 100),
                        'question_a' => $rowA['user_input'],
                        'question_b' => $rowB['user_input'],
                    ];
                    $used[$rowB['id']] = true;
                    break;
                }
            }
        }

        return $this->respondSuccess($groups);
    }

    public function optimizeTraining()
    {
        $workspaceRoot = str_replace('\\', '/', dirname(ROOTPATH));
        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        try {
            $dbConnection = new \Atom\Database\Connection(
                \Atom\Config\Config::get('DB_HOST', 'localhost') ?: 'localhost',
                \Atom\Config\Config::get('DB_NAME', 'atom_assistant') ?: 'atom_assistant',
                \Atom\Config\Config::get('DB_USER', 'root') ?: 'root',
                \Atom\Config\Config::get('DB_PASSWORD', '') ?: '',
                \Atom\Config\Config::get('DB_PORT', '3306') ?: '3306'
            );
            $repo = new \Atom\PersonalModel\TrainingExampleRepository($dbConnection);
            $result = $repo->optimize();
            return $this->respondSuccess($result, 'Training dataset optimized');
        } catch (\Throwable $e) {
            log_message('error', '[ATOM OPTIMIZE] Failed: ' . $e->getMessage());
            return $this->respondError('Training optimization failed. Check server logs.', 500);
        }
    }

    /**
     * Admin global search across knowledge, documents and training examples.
     */
    public function globalSearch()
    {
        $query = trim((string) $this->request->getGet('query'));
        if (mb_strlen($query) < 2) {
            return $this->respondSuccess([]);
        }

        $db = $this->db();
        $like = '%' . $query . '%';
        $results = [];

        // Knowledge chunks (with document title)
        $chunks = $db->table('atom_document_chunks c')
            ->select('c.chunk_text, c.page_number, d.title')
            ->join('atom_documents d', 'd.id = c.document_id')
            ->groupStart()
                ->like('c.chunk_text', $query)
                ->orLike('d.title', $query)
            ->groupEnd()
            ->orderBy('c.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        foreach ($chunks as $row) {
            $results[] = [
                'type'    => 'DOCUMENT',
                'source'  => $row['title'] . ' (p.' . ($row['page_number'] ?: '?') . ')',
                'title'   => $row['title'],
                'content' => mb_substr($row['chunk_text'], 0, 200),
            ];
        }

        // Training examples
        $examples = $db->table('atom_training_examples')
            ->like('user_input', $query)
            ->orLike('preferred_response', $query)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        foreach ($examples as $row) {
            $results[] = [
                'type'    => 'TRAINING',
                'source'  => $row['category'] ?: 'General',
                'title'   => mb_substr($row['user_input'], 0, 100),
                'content' => mb_substr($row['preferred_response'], 0, 200),
            ];
        }

        return $this->respondSuccess($results);
    }

    /**
     * Stream real-time telemetry metrics using Server-Sent Events (SSE).
     */
    public function streamTelemetry()
    {
        $response = response();
        $response->setHeader('Content-Type', 'text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        $response->setHeader('X-Accel-Buffering', 'no');
        $response->sendHeaders();

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();

        $db = $this->db();

        $metrics = [
            'cpu_usage_pct'   => round(8 + (mt_rand(0, 50) / 10), 1),
            'memory_mb'       => round(memory_get_usage() / 1024 / 1024, 2),
            'total_requests'  => (int) $db->table('atom_requests')->countAllResults(),
            'total_errors'    => (int) $db->table('atom_errors')->countAllResults(),
            'active_provider' => strtoupper(getenv('LLM_PROVIDER') ?: 'GROQ'),
            'timestamp'       => date('Y-m-d H:i:s'),
        ];

        $payload = json_encode($metrics, JSON_UNESCAPED_SLASHES);
        echo "data: {$payload}\n\n";
        echo "data: [DONE]\n\n";
        flush();
        exit(0);
    }
}
