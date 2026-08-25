<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstDeadCodeEliminatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 54 — AstDeadCodeEliminatorEngine unit tests (6 tests).
 */
class AstDeadCodeEliminatorEngineTest extends TestCase
{
    private AstDeadCodeEliminatorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AstDeadCodeEliminatorEngine(new SecretRedactor());
    }

    public function testDetectUnreachableStatementAfterReturn(): void
    {
        $code = "function test() { return 42; \$dead = 100; }";
        $scan = $this->engine->scan($code);

        $this->assertTrue($scan['success']);
        $this->assertGreaterThan(0, count($scan['unreachable_blocks']));
        $this->assertSame('UNREACHABLE_STATEMENT_AFTER_RETURN', $scan['unreachable_blocks'][0]['type']);
    }

    public function testDetectUnusedPrivateClassMethod(): void
    {
        $code = "class A { private function unusedHelper() { return 1; } public function run() { return 2; } }";
        $scan = $this->engine->scan($code);

        $this->assertTrue($scan['success']);
        $this->assertGreaterThan(0, count($scan['dead_symbols']));
        $this->assertSame('UNUSED_PRIVATE_METHOD', $scan['dead_symbols'][0]['type']);
    }

    public function testDetectUnusedImportUseStatement(): void
    {
        $code = "use App\\Models\\UnusedModel;\nclass B { public function run() { return 1; } }";
        $scan = $this->engine->scan($code);

        $this->assertTrue($scan['success']);
        $this->assertGreaterThan(0, count($scan['unused_imports']));
        $this->assertSame('UNUSED_IMPORT_STATEMENT', $scan['unused_imports'][0]['type']);
    }

    public function testPruneUnusedImports(): void
    {
        $code = "use App\\Models\\UnusedModel;\nclass B { public function run() { return 1; } }";
        $pruned = $this->engine->prune($code);

        $this->assertTrue($pruned['success']);
        $this->assertStringContainsString('[PRUNED UNUSED IMPORT]', $pruned['pruned_code']);
    }

    public function testPruneUnreachableCode(): void
    {
        $code = "function test() {\n    return 42;\n    \$dead = 100;\n}";
        $pruned = $this->engine->prune($code);

        $this->assertTrue($pruned['success']);
        $this->assertStringContainsString('[PRUNED UNREACHABLE CODE]', $pruned['pruned_code']);
    }

    public function testCleanCodeHasZeroDeadItems(): void
    {
        $code = "use App\\ActiveHelper;\nclass C { public function run() { return ActiveHelper::calc(); } }";
        $scan = $this->engine->scan($code);

        $this->assertTrue($scan['success']);
        $this->assertSame(0, $scan['total_dead_items']);
        $this->assertSame(100.0, $scan['code_cleanliness_score']);
    }
}
