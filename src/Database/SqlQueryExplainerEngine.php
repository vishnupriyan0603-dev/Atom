<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * SqlQueryExplainerEngine — Phase 72
 * Real-time dynamic SQL query execution plan explainer, cost analyzer, and composite index suggestion synthesizer.
 */
class SqlQueryExplainerEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Analyze a SQL query and synthesize execution plan and efficiency score.
     */
    public function explainQuery(string $sqlQuery): array
    {
        $cleanSql = trim($this->redactor->redact($sqlQuery));
        if (empty($cleanSql)) {
            return [
                'success' => false,
                'error' => 'SQL query cannot be empty',
                'efficiency_score' => 0,
            ];
        }

        $table = $this->extractTableName($cleanSql);
        $whereColumns = $this->extractWhereColumns($cleanSql);
        $hasJoin = (bool) preg_match('/\b(join|inner join|left join|right join)\b/i', $cleanSql);
        $hasOrderBy = (bool) preg_match('/\border\s+by\b/i', $cleanSql);
        $hasWildcardSelect = (bool) preg_match('/\bselect\s+\*\b/i', $cleanSql);

        // Calculate efficiency score (100 is optimal)
        $score = 100;
        $warnings = [];

        if (empty($whereColumns)) {
            $score -= 40;
            $warnings[] = 'Full table scan (ALL): Missing WHERE clause filtering';
        }

        if ($hasWildcardSelect) {
            $score -= 15;
            $warnings[] = 'SELECT * used: Retrieves unnecessary columns over the network';
        }

        if ($hasOrderBy && empty($whereColumns)) {
            $score -= 20;
            $warnings[] = 'Using filesort: Sorting without indexed filter';
        }

        $accessType = empty($whereColumns) ? 'ALL' : (count($whereColumns) > 1 ? 'range' : 'ref');
        $suggestedIndexes = $this->synthesizeIndexSuggestions($table, $whereColumns);

        return [
            'success' => true,
            'sql' => $cleanSql,
            'table' => $table,
            'access_type' => $accessType,
            'efficiency_score' => max(10, $score),
            'has_join' => $hasJoin,
            'has_order_by' => $hasOrderBy,
            'has_wildcard_select' => $hasWildcardSelect,
            'filtered_columns' => $whereColumns,
            'warnings' => $warnings,
            'suggested_indexes' => $suggestedIndexes,
        ];
    }

    /**
     * Synthesize composite index suggestions for filtered columns.
     */
    public function synthesizeIndexSuggestions(string $table, array $columns): array
    {
        if (empty($columns)) {
            return [];
        }

        $indexName = 'idx_' . $table . '_' . implode('_', $columns);
        $colsList = implode(', ', $columns);
        $ddl = "CREATE INDEX {$indexName} ON {$table} ({$colsList});";

        return [
            [
                'index_name' => $indexName,
                'table' => $table,
                'columns' => $columns,
                'sql_ddl' => $ddl,
                'estimated_scan_reduction' => '95% to 99%',
            ]
        ];
    }

    private function extractTableName(string $sql): string
    {
        if (preg_match('/\bfrom\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\bupdate\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\binto\s+[`]?([a-zA-Z0-9_]+)[`]?/i', $sql, $matches)) {
            return $matches[1];
        }
        return 'unknown_table';
    }

    private function extractWhereColumns(string $sql): array
    {
        $cols = [];
        if (preg_match_all('/\b([a-zA-Z0-9_]+)\s*(=|!=|>=|<=|>|<|\bin\b|\blike\b|\bbetween\b)/i', $sql, $matches)) {
            $reserved = ['where', 'and', 'or', 'select', 'from', 'join', 'on', 'limit', 'offset', 'set'];
            foreach ($matches[1] as $col) {
                $lower = strtolower($col);
                if (!in_array($lower, $reserved, true) && !is_numeric($col)) {
                    $cols[] = $lower;
                }
            }
        }
        return array_values(array_unique($cols));
    }
}
