<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\DiagramSchemaSynthesizer;
use Atom\Security\SecretRedactor;

/**
 * Phase 42 — DiagramSchemaSynthesizer unit tests (5 tests).
 */
class DiagramSchemaSynthesizerTest extends TestCase
{
    private DiagramSchemaSynthesizer $synthesizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synthesizer = new DiagramSchemaSynthesizer(new SecretRedactor());
    }

    public function testSynthesizeSqlSchemaFromDescription(): void
    {
        $spec = "[Accounts]\n- id: integer\n- username: varchar\n- balance: decimal\n\n[Transactions]\n- id: integer\n- account_id: integer\n- amount: decimal";
        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `accounts`', $result['sql_ddl']);
        $this->assertStringContainsString('`username` VARCHAR(255)', $result['sql_ddl']);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `transactions`', $result['sql_ddl']);
    }

    public function testSynthesizeMermaidClassDiagram(): void
    {
        $spec = [
            'title' => 'ClusterNodes',
            'entities' => [
                ['name' => 'Cluster', 'columns' => [['name' => 'id', 'type' => 'INTEGER'], ['name' => 'region', 'type' => 'VARCHAR(50)']]],
                ['name' => 'Node', 'columns' => [['name' => 'id', 'type' => 'INTEGER'], ['name' => 'ip_address', 'type' => 'VARCHAR(45)']]],
            ],
        ];

        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('classDiagram', $result['mermaid_diagram']);
        $this->assertStringContainsString('class Cluster', $result['mermaid_diagram']);
        $this->assertStringContainsString('class Node', $result['mermaid_diagram']);
        $this->assertStringContainsString('Cluster "1" --> "*" Node : references', $result['mermaid_diagram']);
    }

    public function testSynthesizeJsonSchema(): void
    {
        $result = $this->synthesizer->synthesize([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('json_schema', $result);
        $this->assertSame('http://json-schema.org/draft-07/schema#', $result['json_schema']['$schema']);
        $this->assertNotEmpty($result['json_schema']['definitions']);
    }

    public function testParseEntitiesFromTextTokens(): void
    {
        $text = "Table Products\n- id: int\n- title: varchar\n- price: decimal\n\nTable Categories\n- id: int\n- name: varchar";
        $entities = $this->synthesizer->parseEntitiesFromText($text);

        $this->assertCount(2, $entities);
        $this->assertSame('Products', $entities[0]['name']);
        $this->assertSame('Categories', $entities[1]['name']);
    }

    public function testDefaultEntitiesFallback(): void
    {
        $result = $this->synthesizer->synthesize("   ");
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(2, $result['entity_count']);
    }
}
