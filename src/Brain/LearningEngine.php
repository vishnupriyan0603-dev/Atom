<?php

namespace Atom\Brain;

use Atom\Database\Connection;
use PDO;

class LearningEngine
{
    private ?Connection $connection;
    private string $workspaceRoot;

    public const LEVELS = [
        0 => 'EMPTY',
        1 => 'BEGINNER',
        2 => 'LEARNING',
        3 => 'FAMILIAR',
        4 => 'PROFICIENT',
        5 => 'ADVANCED',
        6 => 'EXPERT KNOWLEDGE'
    ];

    public function __construct(?Connection $connection, string $workspaceRoot)
    {
        $this->connection = $connection;
        $this->workspaceRoot = $workspaceRoot;
    }

    /**
     * Determine level string based on score (0-100).
     */
    public static function getLevelFromScore(int $score): string
    {
        if ($score <= 5)   return 'LEVEL 0 — EMPTY';
        if ($score <= 25)  return 'LEVEL 1 — BEGINNER';
        if ($score <= 45)  return 'LEVEL 2 — LEARNING';
        if ($score <= 65)  return 'LEVEL 3 — FAMILIAR';
        if ($score <= 80)  return 'LEVEL 4 — PROFICIENT';
        if ($score <= 90)  return 'LEVEL 5 — ADVANCED';
        return 'LEVEL 6 — EXPERT KNOWLEDGE';
    }

    /**
     * Retrieve all learning topics from DB.
     */
    public function getTopics(): array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->query("SELECT * FROM atom_learning_topics ORDER BY score DESC, topic ASC");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieve details for a specific topic.
     */
    public function getTopic(string $topicName): ?array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return null;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM atom_learning_topics WHERE topic = ?");
            $stmt->execute([$topicName]);
            $row = $stmt->fetch();
            if ($row) {
                // Calculate dynamic metrics based on RAG & Workspace files
                $row['workspace_files'] = $this->countWorkspaceReferences($topicName);
                $row['pdf_references'] = $this->countPDFReferences($topicName);
                return $row;
            }
            return null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Log a learning activity to history.
     */
    public function logHistory(string $topic, string $actionText, string $source, string $confidence = 'MODERATE'): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO atom_learning_history (topic, action_text, source, confidence) VALUES (?, ?, ?, ?)");
            $stmt->execute([$topic, $actionText, $source, $confidence]);
        } catch (\PDOException $e) {
            // Silently ignore
        }
    }

    /**
     * Get learning history events.
     */
    public function getHistory(int $limit = 20): array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM atom_learning_history ORDER BY id DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Identify weak areas / knowledge gaps.
     */
    public function getGaps(): array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->query("SELECT topic, score FROM atom_learning_topics WHERE score < 60 ORDER BY score ASC LIMIT 5");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Recalculates and updates scores dynamically based on stats.
     */
    public function updateTopicMetrics(string $topic, bool $success = true, bool $isGemini = false): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM atom_learning_topics WHERE topic = ?");
            $stmt->execute([$topic]);
            $row = $stmt->fetch();

            if (!$row) {
                return;
            }

            $successUses = (int)$row['successful_uses'] + ($success ? 1 : 0);
            $failedUses = (int)$row['failed_uses'] + ($success ? 0 : 1);
            $geminiCalls = (int)$row['gemini_consultations'] + ($isGemini ? 1 : 0);

            // Fetch dynamic references counts
            $workspaceRefs = $this->countWorkspaceReferences($topic);
            $pdfRefs = $this->countPDFReferences($topic);
            $sourceCount = $workspaceRefs + $pdfRefs;

            // Algorithm for dynamic scoring
            // Base score comes from source coverage (max 40 points)
            $coverageScore = min(40, ($sourceCount * 5));

            // Successful usages add points (max 40 points)
            $usageScore = min(40, ($successUses * 4));

            // Penalize failed usages
            $penalty = $failedUses * 8;

            // Base seed score
            $baseScore = 20;

            $totalScore = max(0, min(100, ($baseScore + $coverageScore + $usageScore - $penalty)));
            $level = self::getLevelFromScore($totalScore);

            // Determine confidence tag
            $confidence = 'MODERATE';
            if ($totalScore >= 80) $confidence = 'HIGH';
            if ($totalScore >= 90) $confidence = 'VERY HIGH';
            if ($totalScore < 45)  $confidence = 'LOW';
            if ($totalScore < 25)  $confidence = 'VERY LOW';

            $updateStmt = $pdo->prepare("
                UPDATE atom_learning_topics SET
                    score = ?,
                    level = ?,
                    confidence = ?,
                    source_count = ?,
                    successful_uses = ?,
                    failed_uses = ?,
                    gemini_consultations = ?,
                    last_used_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([
                $totalScore,
                explode(' — ', $level)[1] ?? 'BEGINNER',
                $confidence,
                $sourceCount,
                $successUses,
                $failedUses,
                $geminiCalls,
                $row['id']
            ]);

        } catch (\PDOException $e) {
            // Silently ignore
        }
    }

    private function countWorkspaceReferences(string $topic): int
    {
        // Simple search for topic keyword in filenames or project structure
        $topic = strtolower($topic);
        $count = 0;
        try {
            $filesPath = $this->workspaceRoot;
            // Scan directory contents for filenames containing topic (max 100)
            $dir = new \RecursiveDirectoryIterator($filesPath, \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dir);
            foreach ($iterator as $file) {
                if (stripos($file->getFilename(), $topic) !== false) {
                    $count++;
                    if ($count > 50) break;
                }
            }
        } catch (\Exception $e) {}
        return $count;
    }

    private function countPDFReferences(string $topic): int
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return 0;
        }
        $pdo = $this->connection->getPdo();
        try {
            // Count matching chunks from knowledge base
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM atom_document_chunks WHERE MATCH(chunk_text) AGAINST(?)");
            $stmt->execute([$topic]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
