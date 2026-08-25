<?php

namespace App\Controllers\Api;

use Atom\Database\SchemaDriftDetectorEngine;

/**
 * SchemaDrift API Controller — Phase 65
 */
class SchemaDrift extends BaseApiController
{
    private static ?SchemaDriftDetectorEngine $engine = null;

    private function getEngine(): SchemaDriftDetectorEngine
    {
        if (self::$engine === null) {
            self::$engine = new SchemaDriftDetectorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/database/schema/detect-drift
     */
    public function detectDrift()
    {
        $json = $this->request->getJSON(true) ?? [];

        $currentTables = $json['current'] ?? [
            'users' => ['id' => 'INT', 'email' => 'VARCHAR', 'password' => 'VARCHAR'],
            'orders' => ['id' => 'INT', 'user_id' => 'INT'],
        ];

        $expectedTables = $json['expected'] ?? [
            'users' => ['id' => 'INT', 'email' => 'VARCHAR', 'password' => 'VARCHAR', 'mfa_enabled' => 'BOOLEAN'],
            'orders' => ['id' => 'INT', 'user_id' => 'INT', 'status' => 'VARCHAR', 'amount' => 'DECIMAL'],
            'audit_logs' => ['id' => 'INT', 'tenant_id' => 'VARCHAR', 'event' => 'TEXT', 'created_at' => 'DATETIME'],
        ];

        $engine = $this->getEngine();
        $drift = $engine->detectDrift($currentTables, $expectedTables);

        return $this->respondSuccess($drift, 'Schema drift detected');
    }

    /**
     * POST /api/database/schema/generate-migration
     */
    public function generateMigration()
    {
        $json = $this->request->getJSON(true) ?? [];
        $drifts = $json['drifts'] ?? [];

        $engine = $this->getEngine();
        $migrationCode = $engine->synthesizeMigration($drifts, $json['name'] ?? 'AutoSyncSchemaDrift');

        return $this->respondSuccess([
            'migration_name' => $json['name'] ?? 'AutoSyncSchemaDrift',
            'code' => $migrationCode,
        ], 'Migration synthesized');
    }
}
