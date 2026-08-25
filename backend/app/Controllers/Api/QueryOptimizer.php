<?php

namespace App\Controllers\Api;

use Atom\Database\SqlQueryIndexOptimizerEngine;

/**
 * QueryOptimizer API Controller — Phase 52
 */
class QueryOptimizer extends BaseApiController
{
    /**
     * POST /api/database/query-optimizer/analyze
     */
    public function analyze()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sql = $json['sql'] ?? '';

        if (empty(trim($sql))) {
            return $this->respondError('SQL query is required for index optimization', 400);
        }

        $engine = new SqlQueryIndexOptimizerEngine();
        $result = $engine->analyze($sql);

        return $this->respondSuccess($result, 'SQL query index optimization completed');
    }

    /**
     * POST /api/database/query-optimizer/generate-migration
     */
    public function generateMigration()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sql = $json['sql'] ?? '';

        if (empty(trim($sql))) {
            return $this->respondError('SQL query is required for migration generation', 400);
        }

        $engine = new SqlQueryIndexOptimizerEngine();
        $result = $engine->analyze($sql);

        return $this->respondSuccess([
            'table' => $result['table'],
            'sql_ddl' => $result['sql_ddl_migration'],
            'ci4_migration' => $result['ci4_php_migration'],
        ], 'Database migration synthesized');
    }

    /**
     * GET /api/database/query-optimizer/rules
     */
    public function rules()
    {
        return $this->respondSuccess([
            'indexing_rules' => [
                ['rule' => 'ESR Optimization', 'description' => 'Equality predicates first, Sort columns second, Range predicates third'],
                ['rule' => 'Index Selectivity', 'description' => 'High cardinality columns prioritized at start of composite B-Tree'],
                ['rule' => 'Covering Indexes', 'description' => 'Includes SELECT projected fields to eliminate table page fetches'],
            ],
            'supported_dialects' => ['MySQL / MariaDB', 'SQLite3', 'PostgreSQL'],
        ], 'Index optimization rules manifest');
    }
}
