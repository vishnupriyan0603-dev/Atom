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

    public function testHybridSearchIntegration(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $pdo->exec("CREATE TABLE atom_knowledge_triples (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subject TEXT, predicate TEXT, object TEXT, confidence REAL
        )");
        $pdo->exec("INSERT INTO atom_knowledge_triples (subject, predicate, object, confidence) VALUES ('USER_PREFERENCE', 'PREFERS', 'DARK_MODE', 0.99)");
        $pdo->exec("CREATE TABLE atom_document_chunks (id INTEGER PRIMARY KEY, chunk_text TEXT)");
        $pdo->exec("CREATE TABLE atom_documents (id INTEGER PRIMARY KEY, title TEXT, filename TEXT)");

        $dbConn = \Atom\Database\Connection::fromPdo($pdo);
        $search = new \Atom\Knowledge\KnowledgeSearch($dbConn);

        $hybridResults = $search->searchHybrid('DARK_MODE');
        $this->assertIsArray($hybridResults);
        $this->assertArrayHasKey('chunks', $hybridResults);
        $this->assertArrayHasKey('triples', $hybridResults);
        $this->assertNotEmpty($hybridResults['triples']);
        $this->assertSame('USER_PREFERENCE', $hybridResults['triples'][0]['subject']);
    }
}
