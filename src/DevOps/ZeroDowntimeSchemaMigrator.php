<?php

namespace Atom\DevOps;

class ZeroDowntimeSchemaMigrator
{
    private array $migrationLog = [];

    /**
     * Plan a zero-downtime schema migration using shadow ghost tables.
     */
    public function planMigration(string $tableName, array $newColumns, array $indexes = []): array
    {
        $ghostTable = "_{$tableName}_ghost";
        $triggerInsert = "trg_{$tableName}_insert";
        $triggerUpdate = "trg_{$tableName}_update";

        $steps = [
            "1. CREATE TABLE `{$ghostTable}` LIKE `{$tableName}`",
            "2. ALTER TABLE `{$ghostTable}` " . $this->buildAlterClauses($newColumns, $indexes),
            "3. CREATE TRIGGER `{$triggerInsert}` AFTER INSERT ON `{$tableName}` FOR EACH ROW INSERT INTO `{$ghostTable}` VALUES (NEW.*)",
            "4. COPY BACKLOG ROWS from `{$tableName}` to `{$ghostTable}` with chunked batching",
            "5. ATOMIC RENAME: RENAME TABLE `{$tableName}` TO `_{$tableName}_old`, `{$ghostTable}` TO `{$tableName}`",
            "6. DROP TRIGGERS and cleanup `_{$tableName}_old` after verification",
        ];

        $plan = [
            'table' => $tableName,
            'ghost_table' => $ghostTable,
            'new_columns' => $newColumns,
            'indexes' => $indexes,
            'strategy' => 'Online Ghost Table Swap (Zero-Downtime)',
            'steps' => $steps,
            'reversible' => true,
            'estimated_lock_ms' => 0.5, // Atomic metadata rename lock time
        ];

        $this->migrationLog[] = $plan;
        return $plan;
    }

    private function buildAlterClauses(array $columns, array $indexes): string
    {
        $clauses = [];
        foreach ($columns as $col => $type) {
            $clauses[] = "ADD COLUMN `{$col}` {$type}";
        }
        foreach ($indexes as $idx => $cols) {
            $clauses[] = "ADD INDEX `{$idx}` (" . implode(', ', $cols) . ")";
        }
        return implode(', ', $clauses);
    }

    public function getMigrationLog(): array
    {
        return $this->migrationLog;
    }
}