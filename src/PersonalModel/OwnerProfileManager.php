<?php

namespace Atom\PersonalModel;

use Atom\Database\Connection;
use PDO;

class OwnerProfileManager
{
    private ?Connection $connection;

    public function __construct(?Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Retrieves the owner profile. Auto-initializes with default values if empty.
     */
    public function getProfile(): array
    {
        $default = [
            'full_name' => 'Vishnupriyan R',
            'preferred_name' => 'Vishnupriyan',
            'atom_display_name' => 'ATOM',
            'profile_image' => '',
            'preferred_language' => 'English',
            'response_style' => 'concise',
            'explanation_level' => 'intermediate',
            'main_technologies' => 'PHP, Javascript, MySQL',
            'main_use_cases' => 'Software development',
            'timezone' => 'Asia/Kolkata'
        ];

        if ($this->connection === null || !$this->connection->isConnected()) {
            return $default;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->query("SELECT * FROM atom_owner_profile LIMIT 1");
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }

            // Create default profile
            $stmt = $pdo->prepare("
                INSERT INTO atom_owner_profile 
                (full_name, preferred_name, atom_display_name, profile_image, preferred_language, response_style, explanation_level, main_technologies, main_use_cases, timezone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $default['full_name'],
                $default['preferred_name'],
                $default['atom_display_name'],
                $default['profile_image'],
                $default['preferred_language'],
                $default['response_style'],
                $default['explanation_level'],
                $default['main_technologies'],
                $default['main_use_cases'],
                $default['timezone']
            ]);

            $default['id'] = $pdo->lastInsertId();
            return $default;
        } catch (\PDOException $e) {
            return $default;
        }
    }

    /**
     * Updates the owner profile fields.
     */
    public function updateProfile(array $data): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        $current = $this->getProfile();
        $id = $current['id'] ?? null;
        if (!$id) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                UPDATE atom_owner_profile SET
                    full_name = ?,
                    preferred_name = ?,
                    atom_display_name = ?,
                    profile_image = ?,
                    preferred_language = ?,
                    response_style = ?,
                    explanation_level = ?,
                    main_technologies = ?,
                    main_use_cases = ?,
                    timezone = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['full_name'] ?? $current['full_name'],
                $data['preferred_name'] ?? $current['preferred_name'],
                $data['atom_display_name'] ?? $current['atom_display_name'],
                $data['profile_image'] ?? $current['profile_image'],
                $data['preferred_language'] ?? $current['preferred_language'],
                $data['response_style'] ?? $current['response_style'],
                $data['explanation_level'] ?? $current['explanation_level'],
                $data['main_technologies'] ?? $current['main_technologies'],
                $data['main_use_cases'] ?? $current['main_use_cases'],
                $data['timezone'] ?? $current['timezone'],
                $id
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Retrieves biometric/face settings.
     */
    public function getBiometricSettings(): array
    {
        $default = [
            'face_data_enabled' => 0,
            'face_photo_path' => ''
        ];

        if ($this->connection === null || !$this->connection->isConnected()) {
            return $default;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->query("SELECT * FROM atom_biometric_settings LIMIT 1");
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }

            // Create default disabled record
            $stmt = $pdo->prepare("INSERT INTO atom_biometric_settings (face_data_enabled, face_photo_path) VALUES (0, '')");
            $stmt->execute();
            $default['id'] = $pdo->lastInsertId();
            return $default;
        } catch (\PDOException $e) {
            return $default;
        }
    }

    /**
     * Updates biometric/face settings.
     */
    public function updateBiometricSettings(int $enabled, string $path): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        $current = $this->getBiometricSettings();
        $id = $current['id'] ?? null;
        if (!$id) {
            return false;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("UPDATE atom_biometric_settings SET face_data_enabled = ?, face_photo_path = ? WHERE id = ?");
            return $stmt->execute([$enabled, $path, $id]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Exports all user-owned data as a structured JSON array.
     */
    public function exportUserData(): array
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        $export = [];

        try {
            $export['owner_profile'] = $this->getProfile();
            $export['biometric_settings'] = $this->getBiometricSettings();
            
            // Fetch memories
            $stmt = $pdo->query("SELECT * FROM atom_memories");
            $export['memories'] = $stmt->fetchAll();

            // Fetch solutions
            $stmt = $pdo->query("SELECT * FROM atom_solutions");
            $export['solutions'] = $stmt->fetchAll();

            // Fetch personal profile preferences
            $stmt = $pdo->query("SELECT * FROM atom_personal_profile");
            $export['personal_preferences'] = $stmt->fetchAll();

            // Fetch feedback
            $stmt = $pdo->query("SELECT * FROM atom_feedback");
            $export['feedback_logs'] = $stmt->fetchAll();

            // Fetch training examples
            $stmt = $pdo->query("SELECT * FROM atom_training_examples");
            $export['training_examples'] = $stmt->fetchAll();

            // Fetch documents & progress
            $stmt = $pdo->query("SELECT * FROM atom_documents");
            $export['documents'] = $stmt->fetchAll();

            $stmt = $pdo->query("SELECT * FROM atom_learning_progress");
            $export['study_progress'] = $stmt->fetchAll();

            // Fetch conversation history (messages)
            $stmt = $pdo->query("SELECT * FROM atom_messages");
            $export['chat_history'] = $stmt->fetchAll();

        } catch (\PDOException $e) {
            $export['error'] = 'Database export query error: ' . $e->getMessage();
        }

        return $export;
    }

    /**
     * Wipes conversation history.
     */
    public function deleteConversations(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }
        try {
            $pdo = $this->connection->getPdo();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE atom_messages;");
            $pdo->exec("TRUNCATE TABLE atom_sessions;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Wipes memories and preferences.
     */
    public function deleteMemories(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }
        try {
            $pdo = $this->connection->getPdo();
            $pdo->exec("TRUNCATE TABLE atom_memories;");
            $pdo->exec("TRUNCATE TABLE atom_solutions;");
            $pdo->exec("TRUNCATE TABLE atom_personal_profile;");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Wipes training data.
     */
    public function deleteTrainingData(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }
        try {
            $pdo = $this->connection->getPdo();
            $pdo->exec("TRUNCATE TABLE atom_training_examples;");
            $pdo->exec("TRUNCATE TABLE atom_feedback;");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Wipes knowledge document indices.
     */
    public function deleteKnowledgeData(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }
        try {
            $pdo = $this->connection->getPdo();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE atom_document_chunks;");
            $pdo->exec("TRUNCATE TABLE atom_documents;");
            $pdo->exec("TRUNCATE TABLE atom_learning_progress;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Wipes biometric/face data.
     */
    public function deleteFaceData(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }
        try {
            $pdo = $this->connection->getPdo();
            $pdo->exec("TRUNCATE TABLE atom_biometric_settings;");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Full personalization reset.
     */
    public function resetPersonalization(): bool
    {
        return $this->deleteConversations() &&
               $this->deleteMemories() &&
               $this->deleteTrainingData() &&
               $this->deleteFaceData();
    }
}
