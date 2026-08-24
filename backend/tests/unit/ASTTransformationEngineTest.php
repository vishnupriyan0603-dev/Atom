<?php

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\ASTTransformationEngine;

/**
 * Phase 35 — ASTTransformationEngine unit tests (5 tests).
 */
class ASTTransformationEngineTest extends TestCase
{
    private ASTTransformationEngine $ast;

    protected function setUp(): void
    {
        $this->ast = new ASTTransformationEngine();
    }

    public function testExtractMethodTransformation(): void
    {
        $source = "class Handler {\n    public function run(\$a) {\n        \$val = \$a * 2;\n        return \$val;\n    }\n}";
        $options = [
            'target_block'    => '$val = $a * 2;',
            'new_method_name' => 'calculateDouble',
            'params'          => ['a'],
        ];

        $res = $this->ast->transform('extract_method', $source, $options);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('$this->calculateDouble($a);', $res['code']);
        $this->assertStringContainsString('private function calculateDouble($a)', $res['code']);
    }

    public function testDecomposeConditionalTransformation(): void
    {
        $source = "if (!\$user->isLoggedIn()) { return false; } else { echo 'welcome'; }";
        $res = $this->ast->transform('decompose_conditional', $source);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('if (!$user->isLoggedIn())', $res['code']);
    }

    public function testSimplifyBooleanTransformation(): void
    {
        $source = 'if ($order->isValid() === true) { return $flag === false; }';
        $res = $this->ast->transform('simplify_boolean', $source);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('if ($order->isValid())', $res['code']);
        $this->assertStringContainsString('return !$flag;', $res['code']);
    }

    public function testRenameSymbolSafely(): void
    {
        $source = '$oldVar = 10; $result = $oldVar + 5;';
        $res = $this->ast->transform('rename_symbol', $source, [
            'old_name' => '$oldVar',
            'new_name' => '$newVar',
        ]);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('$newVar = 10;', $res['code']);
        $this->assertStringContainsString('$result = $newVar + 5;', $res['code']);
    }

    public function testUnsupportedTransformationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->ast->transform('invalid_transform_type', 'code');
    }
}
