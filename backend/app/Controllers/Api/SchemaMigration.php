<?php

namespace App\Controllers\Api;

use Atom\Database\DynamicSchemaMigrationEngine;

/**
 * SchemaMigration API Controller — Phase 98
 */
class SchemaMigration extends BaseApiController
{
    private static ?DynamicSchemaMigrationEngine $engine = null;

    private function getEngine(): DynamicSchemaMigrationEngine
    {
        if (self::$engine === null) {
            self::$engine = new DynamicSchemaMigrationEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/database/migration/plan
     */
    public function plan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $table = $json['table_name'] ?? 'orders';
        $op = $json['operation'] ?? 'add_column';
        $params = $json['params'] ?? ['column_name' => 'tracking_number', 'column_type' => 'VARCHAR(128)', 'nullable' => true];

        $engine = $this->getEngine();
        $res = $engine->planMigration($table, $op, $params);

        return $this->respondSuccess($res, 'Zero-downtime DDL migration plan created');
    }

    /**
     * POST /api/database/migration/execute
     */
    public function execute()
    {
        $json = $this->request->getJSON(true) ?? [];
        $engine = $this->getEngine();

        if (isset($json['plan'])) {
            $res = $engine->executeMigration($json['plan']);
        } else {
            $plan = $engine->planMigration($json['table_name'] ?? 'audit_logs', 'add_index', ['index_name' => 'idx_created', 'columns' => ['created_at']]);
            $res = $engine->executeMigration($plan);
        }

        return $this->respondSuccess($res, 'Schema migration applied');
    }

    /**
     * GET /api/database/migration/history
     */
    public function history()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getMigrationHistory(), 'Applied migration history');
    }
}
