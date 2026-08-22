<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Atom\Knowledge\KnowledgeGraph;

final class KnowledgeGraphTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    public function testAddAndQueryTriples(): void
    {
        $kg = new KnowledgeGraph();
        $success = $kg->addTriple('ATOM', 'USES', 'PHP_CODEIGNITER_4', 0.98);
        $this->assertTrue($success);

        $results = $kg->queryTriples('ATOM', 'USES');
        $this->assertNotEmpty($results);
        $this->assertSame('ATOM', $results[0]['subject']);
        $this->assertSame('USES', $results[0]['predicate']);
        $this->assertSame('PHP_CODEIGNITER_4', $results[0]['object']);
    }

    public function testExtractTriplesFromText(): void
    {
        $kg = new KnowledgeGraph();
        $text = "SYSTEM -> DEPENDS_ON -> SQLITE_DATABASE\nMODEL [CONFIGURED_WITH] OLLAMA_PROVIDER";
        $extractedCount = $kg->extractTriplesFromText($text);
        $this->assertGreaterThanOrEqual(1, $extractedCount);
    }
}
