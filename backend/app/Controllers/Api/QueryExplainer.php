<?php

namespace App\Controllers\Api;

use Atom\Database\SqlQueryExplainerEngine;

/**
 * QueryExplainer API Controller — Phase 72
 */
class QueryExplainer extends BaseApiController
{
    private static ?SqlQueryExplainerEngine $engine = null;

    private function getEngine(): SqlQueryExplainerEngine
    {
        if (self::$engine === null) {
            self::$engine = new SqlQueryExplainerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/database/explainer/analyze
     */
    public function analyze()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sql = $json['query'] ?? 'SELECT * FROM users WHERE email = "alex@atom.ai" AND tenant_id = "tenant_1"';

        $engine = $this->getEngine();
        $res = $engine->explainQuery($sql);

        return $this->respondSuccess($res, 'Query execution plan explained');
    }

    /**
     * POST /api/database/explainer/suggest-indexes
     */
    public function suggestIndexes()
    {
        $json = $this->request->getJSON(true) ?? [];
        $table = $json['table'] ?? 'orders';
        $columns = $json['columns'] ?? ['user_id', 'created_at'];

        $engine = $this->getEngine();
        $suggestions = $engine->synthesizeIndexSuggestions($table, $columns);

        return $this->respondSuccess($suggestions, 'Composite indexes synthesized');
    }
}
