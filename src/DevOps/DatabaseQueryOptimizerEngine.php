<?php

namespace Atom\DevOps;

class DatabaseQueryOptimizerEngine
{
    /**
     * Analyze SQL query syntax and recommend performance optimizations.
     */
    public function analyzeAndOptimize(string $sql): array
    {
        $sql = trim($sql);
        $recommendations = [];
        $costScore = 'LOW';
        $suggestedIndexes = [];

        // Check 1: SELECT * full column expansion
        if (preg_match('/SELECT\s+\*\s+FROM\s+([a-zA-Z0-9_]+)/i', $sql, $m)) {
            $table = $m[1];
            $recommendations[] = "Avoid 'SELECT *'; specify only required columns from table `{$table}` to reduce I/O buffer footprint.";
            $costScore = 'MEDIUM';
        }

        // Check 2: WHERE clause missing index candidate
        if (preg_match('/FROM\s+([a-zA-Z0-9_]+)\s+WHERE\s+([a-zA-Z0-9_\s\=\<\>\!\'\"]+)/i', $sql, $m)) {
            $table = $m[1];
            $whereClause = $m[2];

            // Extract column names in where clause
            if (preg_match_all('/([a-zA-Z0-9_]+)\s*[\=\>\<]/', $whereClause, $colMatches)) {
                $cols = array_unique($colMatches[1]);
                $idxName = "idx_" . implode('_', $cols);
                $suggestedIndexes[] = "CREATE INDEX `{$idxName}` ON `{$table}` (" . implode(', ', $cols) . ");";
                $recommendations[] = "Add index candidate `{$idxName}` on columns (" . implode(', ', $cols) . ") for table `{$table}`.";
            }
        }

        // Check 3: Leading wildcard LIKE '%keyword'
        if (preg_match('/LIKE\s+[\'"]\%[a-zA-Z0-9_\s]+\%/i', $sql)) {
            $recommendations[] = "Leading wildcard LIKE query triggers full table scan. Consider MySQL Full-Text Search (MATCH...AGAINST) or inverted n-gram index.";
            $costScore = 'HIGH';
        }

        // Check 4: Unbounded LIMIT
        if (stripos($sql, 'SELECT') !== false && stripos($sql, 'LIMIT') === false) {
            $recommendations[] = "Query lacks LIMIT clause. Append 'LIMIT 100' or appropriate pagination constraint to prevent OOM on large datasets.";
        }

        return [
            'original_sql' => $sql,
            'cost_score' => $costScore,
            'recommendations_count' => count($recommendations),
            'recommendations' => $recommendations,
            'suggested_indexes' => $suggestedIndexes,
            'is_optimal' => empty($recommendations),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}