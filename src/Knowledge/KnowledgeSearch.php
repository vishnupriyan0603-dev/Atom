<?php

namespace Atom\Knowledge;

use Atom\Database\Connection;

class KnowledgeSearch
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Lightweight connectivity probe for health checks: returns true when the
     * knowledge tables can be reached.
     */
    public static function probeConnection(): bool
    {
        try {
            $conn = self::buildConnection();
            if (!$conn->isConnected()) {
                return false;
            }
            $stmt = $conn->getPdo()->query("SELECT COUNT(*) FROM atom_document_chunks");
            $stmt->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function buildConnection(): Connection
    {
        $workspaceRoot = defined('ROOTPATH') ? str_replace('\\', '/', dirname(ROOTPATH)) : getcwd();
        if (!class_exists('Atom\Config\Config')) {
            require_once $workspaceRoot . '/config/config.php';
        }
        \Atom\Config\Config::load($workspaceRoot);

        return new Connection(
            \Atom\Config\Config::get('DB_HOST', 'localhost') ?: 'localhost',
            \Atom\Config\Config::get('DB_NAME', 'atom_assistant') ?: 'atom_assistant',
            \Atom\Config\Config::get('DB_USER', 'root') ?: 'root',
            \Atom\Config\Config::get('DB_PASSWORD', '') ?: '',
            \Atom\Config\Config::get('DB_PORT', '3306') ?: '3306'
        );
    }

    /**
     * Searches matching chunks inside the database and ranks them using hybrid scoring.
     */
    public function search(string $query, int $limit = 5): array
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        $pdo = $this->connection->getPdo();
        try {
            // 1. Natural Language MATCH AGAINST query
            $sql = "
                SELECT c.page_number, c.chunk_text, d.title, d.filename,
                       MATCH(c.chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE) as ft_score
                FROM atom_document_chunks c
                JOIN atom_documents d ON c.document_id = d.id
                WHERE MATCH(c.chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY ft_score DESC
                LIMIT 20
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(1, $query);
            $stmt->bindValue(2, $query);
            $stmt->execute();
            $results = $stmt->fetchAll();

            // 2. Fallback to LIKE matching if no results found
            if (empty($results)) {
                $terms = explode(' ', $query);
                $whereClause = [];
                $params = [];
                
                foreach ($terms as $term) {
                    $term = trim($term);
                    if (strlen($term) > 2) {
                        $whereClause[] = "c.chunk_text LIKE ?";
                        $params[] = "%{$term}%";
                    }
                }

                if (!empty($whereClause)) {
                    $sql = "
                        SELECT c.page_number, c.chunk_text, d.title, d.filename, 1.0 as ft_score
                        FROM atom_document_chunks c
                        JOIN atom_documents d ON c.document_id = d.id
                        WHERE " . implode(' OR ', $whereClause) . "
                        LIMIT 20
                    ";
                    
                    $stmt = $pdo->prepare($sql);
                    foreach ($params as $idx => $param) {
                        $stmt->bindValue($idx + 1, $param);
                    }
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
            }

            // 3. Compute dynamic ranking scores
            $rankedResults = [];
            $queryWords = array_unique(explode(' ', strtolower(preg_replace('/[^\w\s]/', '', $query))));

            foreach ($results as $row) {
                $chunkWords = explode(' ', strtolower(preg_replace('/[^\w\s]/', '', $row['chunk_text'])));
                $overlapCount = 0;
                foreach ($queryWords as $qw) {
                    if (in_array($qw, $chunkWords)) {
                        $overlapCount++;
                    }
                }
                
                $keywordScore = count($queryWords) > 0 ? $overlapCount / count($queryWords) : 0;
                $ftScore = (float)($row['ft_score'] ?? 0.0);
                
                // Final hybrid score: combination of full-text index score and keyword overlap
                $row['relevance_score'] = round(($ftScore * 0.7) + ($keywordScore * 0.3), 2);
                $rankedResults[] = $row;
            }

            // Sort results by computed relevance score
            usort($rankedResults, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });

            return array_slice($rankedResults, 0, $limit);

        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Queries matching Knowledge Graph triples for a given search string.
     */
    public function searchTriples(string $query, int $limit = 5): array
    {
        if (!$this->connection->isConnected()) {
            return [];
        }

        try {
            $pdo = $this->connection->getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM atom_knowledge_triples 
                WHERE subject LIKE ? OR predicate LIKE ? OR object LIKE ?
                ORDER BY confidence DESC
                LIMIT " . (int)$limit . "
            ");
            $likeQuery = "%" . trim($query) . "%";
            $stmt->bindValue(1, $likeQuery);
            $stmt->bindValue(2, $likeQuery);
            $stmt->bindValue(3, $likeQuery);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Performs hybrid RAG search returning matching document chunks and Knowledge Graph triples.
     */
    public function searchHybrid(string $query, int $limit = 5): array
    {
        $chunks = $this->search($query, $limit);
        $triples = $this->searchTriples($query, $limit);

        return [
            'query' => $query,
            'chunks' => $chunks,
            'triples' => $triples,
            'total_matches' => count($chunks) + count($triples)
        ];
    }

    /**
     * Calculates cosine similarity score between two vector arrays (0.0 to 1.0).
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($vecA), count($vecB));
        if ($count === 0) return 0.0;

        for ($i = 0; $i < $count; $i++) {
            $valA = (float)$vecA[$i];
            $valB = (float)$vecB[$i];
            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
