<?php

namespace Atom\Memory;

use CodeIgniter\Database\BaseConnection;

class StructuredMemoryService
{
    private function getDb(): BaseConnection
    {
        return \Config\Database::connect();
    }

    /**
     * Extracts memory intent & category from raw text.
     */
    public function extractMemoryIntent(string $text): array
    {
        $textTrim = trim($text);
        if (preg_match('/^(remember\s+that|i\s+prefer|prefer)\s+(.+)$/i', $textTrim, $m)) {
            return ['type' => 'preference', 'content' => trim($m[2]), 'importance' => 8];
        }
        if (preg_match('/^(always\s+use|instruction)\s+(.+)$/i', $textTrim, $m)) {
            return ['type' => 'instruction', 'content' => trim($m[2]), 'importance' => 9];
        }
        if (preg_match('/^(project|framework)\s+(.+)$/i', $textTrim, $m)) {
            return ['type' => 'project', 'content' => trim($m[2]), 'importance' => 7];
        }
        return ['type' => 'fact', 'content' => $textTrim, 'importance' => 5];
    }

    /**
     * Checks if exact or duplicate content exists for this user.
     */
    public function isDuplicate(int $userId, string $content): bool
    {
        $db = $this->getDb();
        $count = $db->table($db->prefixTable('atom_structured_memories'), true)
                    ->where('user_id', $userId)
                    ->where('content', trim($content))
                    ->countAllResults();
        return $count > 0;
    }

    /**
     * Saves a structured memory entity with user isolation and deduplication.
     */
    public function saveMemory(StructuredMemory $memory): ?StructuredMemory
    {
        $db = $this->getDb();

        if ($this->isDuplicate($memory->userId, $memory->content)) {
            // Touch existing duplicate access count
            $db->table($db->prefixTable('atom_structured_memories'), true)
               ->where('user_id', $memory->userId)
               ->where('content', trim($memory->content))
               ->set('access_count', 'access_count + 1', false)
               ->update();
            return $this->getMemoryByContent($memory->userId, $memory->content);
        }

        $data = $memory->toArray();
        unset($data['id']);

        if ($db->table($db->prefixTable('atom_structured_memories'), true)->insert($data)) {
            $memory->id = (int)$db->insertID();
            return $memory;
        }

        return null;
    }

    /**
     * Retrieves ranked user memories filtering out expired entries.
     */
    public function retrieveRankedMemories(int $userId, ?string $type = null, int $limit = 20): array
    {
        $db = $this->getDb();
        $builder = $db->table($db->prefixTable('atom_structured_memories'), true)
                      ->where('user_id', $userId)
                      ->groupStart()
                          ->where('expires_at IS NULL')
                          ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                      ->groupEnd();

        if ($type !== null && $type !== '') {
            $builder->where('type', strtolower($type));
        }

        $rows = $builder->orderBy('importance', 'DESC')
                        ->orderBy('access_count', 'DESC')
                        ->orderBy('id', 'DESC')
                        ->limit($limit)
                        ->get()
                        ->getResultArray();

        return array_map(fn($r) => StructuredMemory::fromArray($r), $rows);
    }

    public function editMemory(int $userId, int $memoryId, string $newContent, ?int $importance = null): bool
    {
        $db = $this->getDb();
        $data = [
            'content'    => trim($newContent),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($importance !== null) {
            $data['importance'] = max(1, min(10, $importance));
        }

        return $db->table($db->prefixTable('atom_structured_memories'), true)
                  ->where('id', $memoryId)
                  ->where('user_id', $userId)
                  ->update($data);
    }

    public function deleteMemory(int $userId, int $memoryId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_structured_memories'), true)
                  ->where('id', $memoryId)
                  ->where('user_id', $userId)
                  ->delete();
    }

    public function clearUserMemories(int $userId): bool
    {
        $db = $this->getDb();
        return $db->table($db->prefixTable('atom_structured_memories'), true)
                  ->where('user_id', $userId)
                  ->delete();
    }

    private function getMemoryByContent(int $userId, string $content): ?StructuredMemory
    {
        $db = $this->getDb();
        $row = $db->table($db->prefixTable('atom_structured_memories'), true)
                  ->where('user_id', $userId)
                  ->where('content', trim($content))
                  ->get()
                  ->getRowArray();
        return $row ? StructuredMemory::fromArray($row) : null;
    }
}
