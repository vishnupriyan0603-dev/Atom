<?php

namespace Atom\Knowledge;

use Atom\Database\Connection;
use Atom\Security\WorkspaceGuard;

class DocumentImporter
{
    private Connection $connection;
    private PdfExtractor $extractor;
    private Chunker $chunker;
    private WorkspaceGuard $guard;
    private string $storagePath;

    public function __construct(
        Connection $connection,
        PdfExtractor $extractor,
        Chunker $chunker,
        WorkspaceGuard $guard,
        string $workspaceRoot
    ) {
        $this->connection = $connection;
        $this->extractor = $extractor;
        $this->chunker = $chunker;
        $this->guard = $guard;
        $this->storagePath = rtrim(str_replace('\\', '/',$workspaceRoot), '/') . '/storage/knowledge/originals';
    }

    /**
     * Imports a PDF document.
     * Returns array: ['success' => bool, 'document_id' => ?int, 'chunks_count' => int, 'error' => ?string]
     */
    public function import(string $localFilePath): array
    {
        if (!$this->connection->isConnected()) {
            return [
                'success' => false,
                'error' => 'Database connection is offline.'
            ];
        }

        try {
            // Validate source path safety
            $sourcePath = $this->guard->getSafePath($localFilePath);
            if (!is_file($sourcePath)) {
                return [
                    'success' => false,
                    'error' => 'Source PDF file not found: ' . $localFilePath
                ];
            }

            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'txt', 'md'], true)) {
                return [
                    'success' => false,
                    'error' => 'Only PDF, TXT, and MD files are supported for knowledge ingestion.'
                ];
            }

            // Create target originals directory if missing
            if (!is_dir($this->storagePath)) {
                mkdir($this->storagePath, 0755, true);
            }

            $filename = basename($sourcePath);
            $targetPath = $this->storagePath . '/' . $filename;

            // Copy file to storage originals
            if (!copy($sourcePath, $targetPath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to copy original file to knowledge storage.'
                ];
            }

            // Parse pages
            $pages = [];
            if ($ext === 'pdf') {
                $pages = $this->extractor->extract($targetPath);
            } else {
                // For TXT/MD files, read raw text content directly
                $rawText = file_get_contents($targetPath);
                if ($rawText !== false && strlen(trim($rawText)) > 0) {
                    $pages[1] = trim($rawText);
                }
            }

            if (empty($pages)) {
                return [
                    'success' => false,
                    'error' => 'No text could be extracted from the document.'
                ];
            }

            $pdo = $this->connection->getPdo();
            $fileHash = hash_file('sha256', $sourcePath);

            // Check if document already exists by path or hash
            $stmt = $pdo->prepare("SELECT id, path, file_hash FROM atom_documents WHERE path = ? OR file_hash = ?");
            $stmt->execute([$targetPath, $fileHash]);
            $row = $stmt->fetch();

            $pdo->beginTransaction();

            $title = pathinfo($filename, PATHINFO_FILENAME);
            
            if ($row) {
                if ($row['file_hash'] === $fileHash && $row['path'] !== $targetPath) {
                    $pdo->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Duplicate document content detected (already indexed at path: ' . $row['path'] . ').'
                    ];
                }
                $documentId = (int)$row['id'];
                // Delete existing chunks
                $stmt = $pdo->prepare("DELETE FROM atom_document_chunks WHERE document_id = ?");
                $stmt->execute([$documentId]);
                // Update file hash
                $stmt = $pdo->prepare("UPDATE atom_documents SET file_hash = ? WHERE id = ?");
                $stmt->execute([$fileHash, $documentId]);
            } else {
                // Insert document metadata
                $stmt = $pdo->prepare("INSERT INTO atom_documents (title, filename, file_hash, path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $filename, $fileHash, $targetPath]);
                $documentId = (int)$pdo->lastInsertId();
            }

            // Slice pages into overlapping chunks and insert
            $chunksCount = 0;
            $stmt = $pdo->prepare("INSERT INTO atom_document_chunks (document_id, page_number, chunk_text) VALUES (?, ?, ?)");

            foreach ($pages as $pageNo => $text) {
                $chunks = $this->chunker->chunk($text);
                foreach ($chunks as $chunk) {
                    $stmt->execute([$documentId, $pageNo, $chunk]);
                    $chunksCount++;
                }
            }

            $pdo->commit();

            return [
                'success' => true,
                'document_id' => $documentId,
                'chunks_count' => $chunksCount,
                'error' => null
            ];

        } catch (\Exception $e) {
            if ($this->connection->isConnected() && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
