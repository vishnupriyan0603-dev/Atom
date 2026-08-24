<?php

use PHPUnit\Framework\TestCase;
use Atom\Lsp\AstRefactoringEngine;

/**
 * Phase 26 — AstRefactoringEngine unit tests (5 tests).
 */
class AstRefactoringEngineTest extends TestCase
{
    private AstRefactoringEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new AstRefactoringEngine();
    }

    public function testAddPhpDocGeneratesDocblocks(): void
    {
        $code = "public function getUser(\$id)\n{\n    return \$id;\n}";
        $res = $this->engine->refactor($code, 'add_phpdoc');

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('/**', $res['transformed_code']);
        $this->assertStringContainsString('@param mixed $id', $res['transformed_code']);
    }

    public function testAddTypeHintsInjectsTypeHints(): void
    {
        $code = "function findItem(\$query) { return []; }";
        $res = $this->engine->refactor($code, 'add_type_hints');

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('string $query', $res['transformed_code']);
        $this->assertStringContainsString('array', $res['transformed_code']);
    }

    public function testExtractMethodInsertsHelper(): void
    {
        $code = "class Service {\n    public function run() {\n        \$this->helper();\n    }\n}";
        $res = $this->engine->refactor($code, 'extract_method', ['method_name' => 'validateInput']);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('private function validateInput()', $res['transformed_code']);
    }

    public function testFormatSyntaxRemovesTrailingWhitespace(): void
    {
        $code = "\$a = 1;   \n\$b = 2; \t\n";
        $res = $this->engine->refactor($code, 'format_syntax');

        $this->assertTrue($res['success']);
        $this->assertSame("\$a = 1;\n\$b = 2;\n", $res['transformed_code']);
    }

    public function testCleanImportsCountsImports(): void
    {
        $code = "use App\\Models\\User;\nuse CodeIgniter\\Controller;\nclass Foo {}";
        $res = $this->engine->refactor($code, 'clean_imports');

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(2, $res['changes_count']);
    }
}
