<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * SqlQueryIndexOptimizerEngine — Phase 52
 * Autonomous SQL query analyzer and composite B-Tree index migration synthesizer.
 * Applies ESR (Equality, Sort, Range) indexing heuristics to eliminate full table scans.
 */
class SqlQueryIndexOptimizerEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Analyze a SQL query and compute optimal B-Tree index recommendations.
     *
     * @param string $sql Raw SQL query
     * @return array [ 'table' => string, 'recommended_indexes' => array, 'cost_reduction_pct' => float, 'sql_migration' => string ]
     */
    public function analyze(string $sql): array
    {
        if (empty(trim($sql))) {
            return [
                'success' => false,
                'error' => 'SQL query cannot be empty',
                'table' => 'unknown',
                'recommended_indexes' => [],
                'cost_reduction_pct' => 0.0,
            ];
        }

        $cleanSql = $this->redactor->redact($sql);

        // 1. Extract Target Table
        $tableName = 'records';
        if (preg_match('/FROM\s+([a-zA-Z0-9_]+)/i', $cleanSql, $m)) {
            $tableName = $m[1];
        } elseif (preg_match('/(?:UPDATE|INTO|DELETE\s+FROM)\s+([a-zA-Z0-9_]+)/i', $cleanSql, $m)) {
            $tableName = $m[1];
        }

        // 2. Extract WHERE Equality Predicates (col = val)
        $equalityCols = [];
        if (preg_match_all('/([a-zA-Z0-9_]+)\s*=\s*(?:[?:\'\"]|\d+|\$[a-zA-Z0-9_]+)/i', $cleanSql, $m)) {
            foreach ($m[1] as $col) {
                if (!in_array(strtolower($col), ['select', 'where', 'and', 'or', 'join', 'from', '1'])) {
                    $equalityCols[] = strtolower($col);
                }
            }
        }

        // 3. Extract ORDER BY Columns
        $sortCols = [];
        if (preg_match('/ORDER\s+BY\s+([a-zA-Z0-9_,\s]+)(?:ASC|DESC|LIMIT|$)/i', $cleanSql, $m)) {
            $rawSort = explode(',', $m[1]);
            foreach ($rawSort as $col) {
                $trimmed = trim(preg_replace('/\s+(?:ASC|DESC)/i', '', $col));
                if (!empty($trimmed)) {
                    $sortCols[] = strtolower($trimmed);
                }
            }
        }

        // 4. Extract Range Predicates (>, <, BETWEEN, LIKE)
        $rangeCols = [];
        if (preg_match_all('/([a-zA-Z0-9_]+)\s*(?:>=?|<=?|BETWEEN|LIKE)/i', $cleanSql, $m)) {
            foreach ($m[1] as $col) {
                $c = strtolower($col);
                if (!in_array($c, $equalityCols) && !in_array($c, ['where', 'and', 'or'])) {
                    $rangeCols[] = $c;
                }
            }
        }

        // Apply ESR Rule: Equality first, Sort second, Range third
        $orderedIndexCols = array_values(array_unique(array_merge($equalityCols, $sortCols, $rangeCols)));

        if (empty($orderedIndexCols)) {
            $orderedIndexCols = ['id'];
        }

        $indexName = 'idx_' . $tableName . '_' . implode('_', array_slice($orderedIndexCols, 0, 3));
        $colsList = implode(', ', $orderedIndexCols);

        $sqlDdl = "CREATE INDEX {$indexName} ON {$tableName} ({$colsList});";
        $ci4Migration = $this->generateCi4Migration($tableName, $indexName, $orderedIndexCols);

        // Estimate Cost Reduction (Full table scan O(N) -> B-Tree seek O(log N))
        $estimatedCostBefore = 1000.0;
        $estimatedCostAfter = max(10.0, 1000.0 / (count($orderedIndexCols) * 8.0));
        $reductionPct = round(((1000.0 - $estimatedCostAfter) / 1000.0) * 100, 1);

        return [
            'success' => true,
            'table' => $tableName,
            'equality_columns' => array_values(array_unique($equalityCols)),
            'sort_columns' => array_values(array_unique($sortCols)),
            'range_columns' => array_values(array_unique($rangeCols)),
            'recommended_index' => [
                'name' => $indexName,
                'table' => $tableName,
                'columns' => $orderedIndexCols,
                'type' => 'COMPOSITE_BTREE',
                'rule_applied' => 'ESR (Equality, Sort, Range) Optimization',
            ],
            'estimated_cost_before' => $estimatedCostBefore,
            'estimated_cost_after' => round($estimatedCostAfter, 1),
            'cost_reduction_pct' => $reductionPct,
            'sql_ddl_migration' => $sqlDdl,
            'ci4_php_migration' => $ci4Migration,
        ];
    }

    private function generateCi4Migration(string $table, string $indexName, array $columns): string
    {
        $colsExport = "['" . implode("', '", $columns) . "']";
        $className = 'Add' . ucfirst($table) . 'PerformanceIndex';

        return "<?php\n\nnamespace App\Database\Migrations;\n\nuse CodeIgniter\Database\Migration;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        \$this->db->query(\"CREATE INDEX {$indexName} ON {$table} (" . implode(', ', $columns) . ");\");\n    }\n\n    public function down()\n    {\n        \$this->db->query(\"DROP INDEX {$indexName} ON {$table};\");\n    }\n}\n";
    }
}
