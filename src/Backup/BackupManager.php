<?php

namespace Atom\Backup;

use CodeIgniter\Database\BaseConnection;

class BackupManager
{
    private string $workspaceRoot;

    public function __construct(?string $workspaceRoot = null)
    {
        $this->workspaceRoot = $workspaceRoot ?? (defined('ROOTPATH') ? str_replace('\\', '/', dirname(ROOTPATH)) : getcwd());
    }

    private function getDb(): ?BaseConnection
    {
        try {
            return \Config\Database::connect();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Creates a full system archive JSON payload under backups/ directory.
     */
    public function createBackup(?string $targetDir = null): array
    {
        $backupsDir = $targetDir ?? ($this->workspaceRoot . '/backups');
        if (!is_dir($backupsDir)) {
            @mkdir($backupsDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $archiveName = "atom_backup_{$timestamp}.json";
        $archivePath = $backupsDir . '/' . $archiveName;

        $db = $this->getDb();
        $memories = [];
        $documents = [];
        $training = [];
        $approvals = [];
        $jobs = [];

        if ($db !== null) {
            try {
                $memories = $db->table($db->prefixTable('atom_structured_memories'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
            try {
                $documents = $db->table($db->prefixTable('atom_documents'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
            try {
                $training = $db->table($db->prefixTable('atom_training_examples'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
            try {
                $approvals = $db->table($db->prefixTable('atom_approval_requests'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
            try {
                $jobs = $db->table($db->prefixTable('atom_jobs'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
        }

        $backupData = [
            'version'      => 'v1.0',
            'timestamp'    => date('Y-m-d H:i:s'),
            'memories'     => $memories,
            'documents'    => $documents,
            'training'     => $training,
            'approvals'    => $approvals,
            'jobs'         => $jobs,
            'stats'        => [
                'total_memories'  => count($memories),
                'total_documents' => count($documents),
                'total_training'  => count($training),
            ],
        ];

        $jsonPayload = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($archivePath, $jsonPayload);

        return [
            'success'      => true,
            'archive_name' => $archiveName,
            'archive_path' => $archivePath,
            'file_size'    => filesize($archivePath),
            'stats'        => $backupData['stats'],
        ];
    }

    /**
     * Exports training examples and structured memories as a JSON dataset.
     */
    public function exportDataset(?string $targetPath = null): string
    {
        $path = $targetPath ?? ($this->workspaceRoot . '/storage/dataset_export_' . date('Ymd') . '.json');
        $db = $this->getDb();

        $training = [];
        $memories = [];

        if ($db !== null) {
            try {
                $training = $db->table($db->prefixTable('atom_training_examples'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
            try {
                $memories = $db->table($db->prefixTable('atom_structured_memories'), true)->get()->getResultArray();
            } catch (\Throwable $e) {}
        }

        $export = [
            'platform' => 'ATOM AI Core',
            'exported_at' => date('Y-m-d H:i:s'),
            'training_examples' => $training,
            'memories' => $memories,
        ];

        file_put_contents($path, json_encode($export, JSON_PRETTY_PRINT));
        return $path;
    }

    /**
     * Validates backup JSON archive schema.
     */
    public function validateArchive(string $archivePath): bool
    {
        if (!file_exists($archivePath)) {
            return false;
        }

        $data = json_decode(file_get_contents($archivePath), true);
        return is_array($data) && isset($data['version']) && isset($data['timestamp']);
    }
}
