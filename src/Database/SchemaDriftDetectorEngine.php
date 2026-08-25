<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * SchemaDriftDetectorEngine — Phase 65
 * Autonomous database schema drift detector and CodeIgniter 4 migration synthesizer.
 */
class SchemaDriftDetectorEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Compare live database table columns against expected schema definition.
     *
     * @param array $currentTables Key-value map of table => [ column => type ]
     * @param array $expectedTables Key-value map of table => [ column => type ]
     * @return array [ 'drift_detected' => bool, 'drifts' => array, 'drift_count' => int ]
     */
    public function detectDrift(array $currentTables, array $expectedTables): array
    {
        if (empty($expectedTables)) {
            return [
                'success' => false,
                'error' => 'Expected schema definition cannot be empty',
                'drift_detected' => false,
                'drift_count' => 0,
                'drifts' => [],
            ];
        }

        $drifts = [];

        foreach ($expectedTables as $table => $expectedCols) {
            if (!isset($currentTables[$table])) {
                $drifts[] = [
                    'type' => 'MISSING_TABLE',
                    'table' => $table,
                    'details' => "Table '{$table}' is completely missing from active database.",
                    'columns' => $expectedCols,
                ];
                continue;
            }

            $currentCols = $currentTables[$table];
            $missingCols = [];
            $typeMismatches = [];

            foreach ($expectedCols as $col => $expType) {
                if (!isset($currentCols[$col])) {
                    $missingCols[$col] = $expType;
                } elseif (strtolower($currentCols[$col]) !== strtolower($expType)) {
                    $typeMismatches[$col] = [
                        'current' => $currentCols[$col],
                        'expected' => $expType,
                    ];
                }
            }

            if (!empty($missingCols) || !empty($typeMismatches)) {
                $drifts[] = [
                    'type' => 'COLUMN_DRIFT',
                    'table' => $table,
                    'missing_columns' => $missingCols,
                    'type_mismatches' => $typeMismatches,
                ];
            }
        }

        $driftCount = count($drifts);

        return [
            'success' => true,
            'drift_detected' => $driftCount > 0,
            'drift_count' => $driftCount,
            'status' => $driftCount > 0 ? 'SCHEMA_DRIFT_FOUND' : 'SCHEMA_IN_SYNC',
            'drifts' => $drifts,
        ];
    }

    /**
     * Synthesize a CodeIgniter 4 migration class to automatically resolve all detected schema drifts.
     */
    public function synthesizeMigration(array $drifts, string $migrationName = 'AutoSyncSchemaDrift'): string
    {
        $className = preg_replace('/[^a-zA-Z0-9_]/', '', $migrationName);
        $date = date('Y-m-d_His');

        $upCode = "";
        $downCode = "";

        foreach ($drifts as $drift) {
            $table = $drift['table'];

            if ($drift['type'] === 'MISSING_TABLE') {
                $upCode .= "        // Create table {$table}\n";
                $upCode .= "        \$this->forge->addField([\n";
                foreach ($drift['columns'] as $col => $type) {
                    $upCode .= "            '{$col}' => ['type' => '{$type}'],\n";
                }
                $upCode .= "        ]);\n";
                $upCode .= "        \$this->forge->createTable('{$table}', true);\n\n";
                $downCode .= "        \$this->forge->dropTable('{$table}', true);\n";
            } elseif ($drift['type'] === 'COLUMN_DRIFT') {
                if (!empty($drift['missing_columns'])) {
                    $upCode .= "        // Add missing columns to {$table}\n";
                    $upCode .= "        \$this->forge->addColumn('{$table}', [\n";
                    foreach ($drift['missing_columns'] as $col => $type) {
                        $upCode .= "            '{$col}' => ['type' => '{$type}', 'null' => true],\n";
                        $downCode .= "        \$this->forge->dropColumn('{$table}', '{$col}');\n";
                    }
                    $upCode .= "        ]);\n\n";
                }
            }
        }

        return <<<PHP
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Auto-generated migration by Phase 65 SchemaDriftDetectorEngine
 * Timestamp: {$date}
 */
class {$className} extends Migration
{
    public function up(): void
    {
{$upCode}    }

    public function down(): void
    {
{$downCode}    }
}
PHP;
    }
}
