<?php

namespace Atom\Memory;

use Atom\Database\Connection;

class MemoryManager
{
    private ?Connection $connection;
    private string $workspaceRoot;
    private ?int $projectId = null;
    private ?int $sessionId = null;
    private string $sessionUuid;

    public function __construct(?Connection $connection, string $workspaceRoot)
    {
        $this->connection = $connection;
        $this->workspaceRoot = $workspaceRoot;
        $this->sessionUuid = uniqid('atom_session_', true);
        
        $this->initializeProject();
    }

    /**
     * Initializes project row in MySQL if database is connected.
     */
    private function initializeProject(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return;
        }

        $pdo = $this->connection->getPdo();
        try {
            // Find existing project by path
            $stmt = $pdo->prepare("SELECT id FROM atom_projects WHERE path = ?");
            $stmt->execute([$this->workspaceRoot]);
            $row = $stmt->fetch();

            if ($row) {
                $this->projectId = (int)$row['id'];
            } else {
                // Insert new project
                $name = basename($this->workspaceRoot);
                $stmt = $pdo->prepare("INSERT INTO atom_projects (name, path) VALUES (?, ?)");
                $stmt->execute([$name, $this->workspaceRoot]);
                $this->projectId = (int)$pdo->lastInsertId();
            }

            // Create active conversation session row
            $stmt = $pdo->prepare("INSERT INTO atom_sessions (project_id, session_uuid) VALUES (?, ?)");
            $stmt->execute([$this->projectId, $this->sessionUuid]);
            $this->sessionId = (int)$pdo->lastInsertId();

        } catch (\PDOException $e) {
            // Log or degrade gracefully
            $this->projectId = null;
            $this->sessionId = null;
        }
    }

    /**
     * Appends message to database history.
     */
    public function logMessage(string $role, string $content): void
    {
        if ($this->sessionId === null) {
            return;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO atom_messages (session_id, role, content) VALUES (?, ?, ?)");
            $stmt->execute([$this->sessionId, $role, $content]);
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    /**
     * Fetches previous session histories from database.
     */
    public function getHistory(int $limit = 50): array
    {
        if ($this->projectId === null) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                SELECT m.role, m.content, m.created_at 
                FROM atom_messages m
                JOIN atom_sessions s ON m.session_id = s.id
                WHERE s.project_id = ?
                ORDER BY m.id ASC
                LIMIT ?
            ");
            // Set parameter binding
            $stmt->bindValue(1, $this->projectId, \PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Saves user preferences or project decisions.
     */
    public function rememberMemory(string $key, string $value, string $type = 'preference'): bool
    {
        if ($this->projectId === null) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO atom_memories (project_id, type, memory_key, memory_value) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$this->projectId, $type, $key, $value]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Lists recorded memories.
     */
    public function getMemories(): array
    {
        if ($this->projectId === null) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT id, type, memory_key, memory_value, created_at FROM atom_memories WHERE project_id = ? ORDER BY id ASC");
            $stmt->execute([$this->projectId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Deletes target memory by ID.
     */
    public function forgetMemory(int $id): bool
    {
        if ($this->projectId === null) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("DELETE FROM atom_memories WHERE id = ? AND project_id = ?");
            return $stmt->execute([$id, $this->projectId]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Saves technical solutions to the solution library.
     */
    public function rememberSolution(string $summary, string $rootCause, string $solutionText, string $technology = 'php'): bool
    {
        if ($this->projectId === null) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO atom_solutions (project_id, problem_summary, root_cause, solution_text, technology) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$this->projectId, $summary, $rootCause, $solutionText, $technology]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Checks database connectivity.
     */
    public function isDbConnected(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }

    /**
     * Returns connection error details if failed.
     */
    public function getDbError(): ?string
    {
        return $this->connection ? $this->connection->getError() : "No Connection object";
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    private array $workingMemory = [];
    private array $sessionMemory = [
        'opened_files' => [],
        'recent_commands' => [],
        'temporary_decisions' => [],
        'current_task' => null
    ];

    public function setWorkingMemory(string $key, $value): void
    {
        $this->workingMemory[$key] = $value;
    }

    public function getWorkingMemory(?string $key = null)
    {
        if ($key === null) return $this->workingMemory;
        return $this->workingMemory[$key] ?? null;
    }

    public function clearWorkingMemory(): void
    {
        $this->workingMemory = [];
    }

    public function setSessionMemory(string $key, $value): void
    {
        $this->sessionMemory[$key] = $value;
    }

    public function getSessionMemory(?string $key = null)
    {
        if ($key === null) return $this->sessionMemory;
        return $this->sessionMemory[$key] ?? null;
    }

    public function addOpenedFile(string $filePath): void
    {
        if (!in_array($filePath, $this->sessionMemory['opened_files'])) {
            $this->sessionMemory['opened_files'][] = $filePath;
            if (count($this->sessionMemory['opened_files']) > 5) {
                array_shift($this->sessionMemory['opened_files']);
            }
        }
    }

    public function addRecentCommand(string $command): void
    {
        $this->sessionMemory['recent_commands'][] = $command;
        if (count($this->sessionMemory['recent_commands']) > 10) {
            array_shift($this->sessionMemory['recent_commands']);
        }
    }

    public function getPersonalProfile(): array
    {
        $path = $this->workspaceRoot . '/storage/profile/personal.json';
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        return [];
    }
}
