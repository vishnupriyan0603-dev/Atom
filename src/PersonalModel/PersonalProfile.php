<?php

namespace Atom\PersonalModel;

use Atom\Database\Connection;
use PDO;

class PersonalProfile
{
    private ?Connection $connection;
    private ?int $projectId;

    public function __construct(?Connection $connection, ?int $projectId = null)
    {
        $this->connection = $connection;
        $this->projectId = $projectId;
    }

    /**
     * Retrieves all communication preferences for the current project context and global context.
     */
    public function getPreferences(): array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                SELECT preference_key, preference_value, source, confidence 
                FROM atom_personal_profile 
                WHERE project_id = ? OR project_id IS NULL
                ORDER BY project_id DESC, id ASC
            ");
            $stmt->execute([$this->projectId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get a specific preference.
     */
    public function getPreference(string $key): ?array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return null;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                SELECT preference_value, source, confidence 
                FROM atom_personal_profile 
                WHERE (project_id = ? OR project_id IS NULL) AND preference_key = ?
                ORDER BY project_id DESC
                LIMIT 1
            ");
            $stmt->execute([$this->projectId, $key]);
            $row = $stmt->fetch();
            return $row ? $row : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Saves or updates a preference.
     */
    public function setPreference(string $key, string $value, string $source, float $confidence = 1.0): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM atom_personal_profile 
                WHERE project_id = ? AND preference_key = ?
            ");
            $stmt->execute([$this->projectId, $key]);
            $row = $stmt->fetch();

            if ($row) {
                $stmt = $pdo->prepare("
                    UPDATE atom_personal_profile 
                    SET preference_value = ?, source = ?, confidence = ?
                    WHERE id = ?
                ");
                return $stmt->execute([$value, $source, $confidence, $row['id']]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO atom_personal_profile (project_id, preference_key, preference_value, source, confidence)
                    VALUES (?, ?, ?, ?, ?)
                ");
                return $stmt->execute([$this->projectId, $key, $value, $source, $confidence]);
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Removes a preference.
     */
    public function deletePreference(string $key): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                DELETE FROM atom_personal_profile 
                WHERE project_id = ? AND preference_key = ?
            ");
            return $stmt->execute([$this->projectId, $key]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
