<?php

namespace Atom\Knowledge;

use Atom\Database\Connection;
use CodeIgniter\Database\BaseConnection;

class KnowledgeGraph
{
    private ?Connection $connection;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection;
    }

    private function getDb(): BaseConnection
    {
        return \Config\Database::connect();
    }

    /**
     * Store a Subject-Predicate-Object triple in the knowledge graph.
     */
    public function addTriple(string $subject, string $predicate, string $object, float $confidence = 0.95, ?int $sourceItemId = null): bool
    {
        $subject = trim(strtoupper($subject));
        $predicate = trim(strtoupper($predicate));
        $object = trim($object);

        if (empty($subject) || empty($predicate) || empty($object)) {
            return false;
        }

        $db = $this->getDb();
        $builder = $db->table($db->prefixTable('atom_knowledge_triples'), true);
        
        $existing = $builder->where([
            'subject' => $subject,
            'predicate' => $predicate,
            'object' => $object
        ])->get()->getRowArray();

        if ($existing) {
            return $builder->where('id', $existing['id'])->update([
                'confidence' => $confidence,
                'source_item_id' => $sourceItemId
            ]);
        }

        return $builder->insert([
            'subject' => $subject,
            'predicate' => $predicate,
            'object' => $object,
            'confidence' => $confidence,
            'source_item_id' => $sourceItemId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Query graph triples matching subject, predicate, or object patterns.
     */
    public function queryTriples(?string $subject = null, ?string $predicate = null, ?string $object = null): array
    {
        $db = $this->getDb();
        $builder = $db->table($db->prefixTable('atom_knowledge_triples'), true);

        if (!empty($subject)) {
            $builder->like('subject', trim(strtoupper($subject)));
        }
        if (!empty($predicate)) {
            $builder->like('predicate', trim(strtoupper($predicate)));
        }
        if (!empty($object)) {
            $builder->like('object', trim($object));
        }

        return $builder->orderBy('confidence', 'DESC')->limit(50)->get()->getResultArray();
    }

    /**
     * Extract triples from raw structured text (Simple heuristic parser).
     */
    public function extractTriplesFromText(string $text, ?int $sourceItemId = null): int
    {
        $lines = explode("\n", $text);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^([^\[\-\>]+)\s*(?:->|\[([^\]]+)\])\s*(.+)$/i', $line, $matches)) {
                $subj = trim($matches[1]);
                $pred = !empty($matches[2]) ? trim($matches[2]) : 'RELATED_TO';
                $obj  = trim($matches[3]);

                if ($this->addTriple($subj, $pred, $obj, 0.90, $sourceItemId)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
