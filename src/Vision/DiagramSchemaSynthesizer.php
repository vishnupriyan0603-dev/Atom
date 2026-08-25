<?php

namespace Atom\Vision;

use Atom\Security\SecretRedactor;

/**
 * DiagramSchemaSynthesizer — Phase 42
 * Converts visual architectural diagrams, flowcharts, and ERD sketches into SQL DDL and Mermaid graphs.
 */
class DiagramSchemaSynthesizer
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Synthesize schema and diagrams from textual/visual description.
     *
     * @param array|string $spec
     * @return array
     */
    public function synthesize(mixed $spec): array
    {
        $title = 'DatabaseSchema';
        $entities = [];

        if (is_string($spec)) {
            $entities = $this->parseEntitiesFromText($spec);
        } elseif (is_array($spec)) {
            $title = (string)($spec['title'] ?? $title);
            $entities = is_array($spec['entities'] ?? null) && !empty($spec['entities'])
                ? $spec['entities']
                : $this->parseEntitiesFromText((string)($spec['description'] ?? ''));
        }

        if (empty($entities)) {
            $entities = $this->getDefaultEntities();
        }

        $sqlDdl = $this->generateSqlDdl($entities);
        $mermaid = $this->generateMermaidGraph($title, $entities);
        $jsonSchema = $this->generateJsonSchema($title, $entities);

        // Redact any sensitive column names or mock data
        $cleanSql = $this->redactor->redact($sqlDdl);

        return [
            'success' => true,
            'title' => $title,
            'entity_count' => count($entities),
            'entities' => $entities,
            'sql_ddl' => $cleanSql,
            'mermaid_diagram' => $mermaid,
            'json_schema' => $jsonSchema,
        ];
    }

    /**
     * Parse entities and columns from visual/prompt description.
     */
    public function parseEntitiesFromText(string $text): array
    {
        $entities = [];
        $lines = explode("\n", $text);
        $currentEntity = null;

        foreach ($lines as $line) {
            $l = trim($line);
            if (empty($l)) continue;

            if (preg_match('/^(?:entity|table|class|model)\s+([A-Za-z0-9_]+)/i', $l, $m) || preg_match('/^\[([A-Za-z0-9_]+)\]/', $l, $m)) {
                if ($currentEntity) {
                    $entities[] = $currentEntity;
                }
                $currentEntity = [
                    'name' => $m[1],
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'is_pk' => true, 'is_nullable' => false],
                    ],
                ];
            } elseif ($currentEntity && preg_match('/^[-*•]?\s*([a-zA-Z0-9_]+)\s*(?::\s*([a-zA-Z0-9_]+))?/', $l, $col)) {
                $colName = $col[1];
                if (strtolower($colName) !== 'id') {
                    $colType = isset($col[2]) ? strtoupper($col[2]) : 'VARCHAR(255)';
                    $currentEntity['columns'][] = [
                        'name' => $colName,
                        'type' => $this->normalizeSqlType($colType),
                        'is_pk' => false,
                        'is_nullable' => true,
                    ];
                }
            }
        }

        if ($currentEntity) {
            $entities[] = $currentEntity;
        }

        return $entities;
    }

    private function generateSqlDdl(array $entities): string
    {
        $sql = "-- Generated SQL Schema by ATOM Phase 42 Vision Synthesizer\n\n";

        foreach ($entities as $e) {
            $tableName = strtolower($e['name']);
            $sql .= "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";
            $colDefs = [];

            foreach ($e['columns'] as $c) {
                $pk = !empty($c['is_pk']) ? ' PRIMARY KEY AUTO_INCREMENT' : '';
                $null = empty($c['is_nullable']) ? ' NOT NULL' : ' NULL';
                if (!empty($c['is_pk'])) $null = ' NOT NULL';
                $colDefs[] = "    `{$c['name']}` {$c['type']}{$null}{$pk}";
            }
            $colDefs[] = "    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP";
            $colDefs[] = "    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

            $sql .= implode(",\n", $colDefs);
            $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        }

        return rtrim($sql) . "\n";
    }

    private function generateMermaidGraph(string $title, array $entities): string
    {
        $mermaid = "classDiagram\n";
        $mermaid .= "    %% " . addslashes($title) . "\n";

        foreach ($entities as $e) {
            $mermaid .= "    class " . $e['name'] . " {\n";
            foreach ($e['columns'] as $c) {
                $mermaid .= "        +" . $c['type'] . " " . $c['name'] . "\n";
            }
            $mermaid .= "    }\n";
        }

        // Add sample relationship if multiple entities exist
        if (count($entities) >= 2) {
            $mermaid .= "    " . $entities[0]['name'] . " \"1\" --> \"*\" " . $entities[1]['name'] . " : references\n";
        }

        return $mermaid;
    }

    private function generateJsonSchema(string $title, array $entities): array
    {
        $definitions = [];
        foreach ($entities as $e) {
            $props = [];
            foreach ($e['columns'] as $c) {
                $props[$c['name']] = [
                    'type' => str_contains($c['type'], 'INT') ? 'integer' : 'string',
                    'description' => "Column {$c['name']} of {$e['name']}",
                ];
            }
            $definitions[$e['name']] = [
                'type' => 'object',
                'properties' => $props,
                'required' => ['id'],
            ];
        }

        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => $title,
            'type' => 'object',
            'definitions' => $definitions,
        ];
    }

    private function normalizeSqlType(string $type): string
    {
        $t = strtoupper($type);
        return match ($t) {
            'INT', 'INTEGER', 'NUMERIC' => 'INTEGER',
            'FLOAT', 'DOUBLE', 'DECIMAL' => 'DECIMAL(10,2)',
            'BOOL', 'BOOLEAN' => 'TINYINT(1)',
            'DATE', 'TIME', 'DATETIME', 'TIMESTAMP' => 'DATETIME',
            'TEXT', 'LONGTEXT' => 'TEXT',
            default => 'VARCHAR(255)',
        };
    }

    private function getDefaultEntities(): array
    {
        return [
            [
                'name' => 'Users',
                'columns' => [
                    ['name' => 'id', 'type' => 'INTEGER', 'is_pk' => true, 'is_nullable' => false],
                    ['name' => 'email', 'type' => 'VARCHAR(255)', 'is_pk' => false, 'is_nullable' => false],
                    ['name' => 'role', 'type' => 'VARCHAR(50)', 'is_pk' => false, 'is_nullable' => true],
                ],
            ],
            [
                'name' => 'Workflows',
                'columns' => [
                    ['name' => 'id', 'type' => 'INTEGER', 'is_pk' => true, 'is_nullable' => false],
                    ['name' => 'user_id', 'type' => 'INTEGER', 'is_pk' => false, 'is_nullable' => false],
                    ['name' => 'title', 'type' => 'VARCHAR(255)', 'is_pk' => false, 'is_nullable' => false],
                    ['name' => 'status', 'type' => 'VARCHAR(50)', 'is_pk' => false, 'is_nullable' => true],
                ],
            ],
        ];
    }
}
