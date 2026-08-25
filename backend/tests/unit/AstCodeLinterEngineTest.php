<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstCodeLinterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 63 — AstCodeLinterEngine unit tests (6 tests).
 */
class AstCodeLinterEngineTest extends TestCase
{
    private AstCodeLinterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AstCodeLinterEngine(new SecretRedactor());
    }

    public function testDetectsMissingStrictTypesViolation(): void
    {
        $code = "<?php\nclass Foo\n{\n}";
        $scan = $this->engine->scanCode($code);

        $this->assertTrue($scan['success']);
        $this->assertFalse($scan['is_fully_compliant']);
        $this->assertGreaterThan(0, $scan['violations_count']);
    }

    public function testDetectsOpeningBraceSameLineViolation(): void
    {
        $code = "<?php\ndeclare(strict_types=1);\nclass Foo { }";
        $scan = $this->engine->scanCode($code);

        $this->assertTrue($scan['success']);
        $this->assertFalse($scan['is_fully_compliant']);
    }

    public function testAutoFixInjectsStrictTypesAndBraceFormat(): void
    {
        $code = "<?php\nclass SampleController {\n    public function index() {\n        return 1;\n    }\n}\n?>";
        $fix = $this->engine->fixCode($code);

        $this->assertTrue($fix['success']);
        $this->assertGreaterThan(0, $fix['fixes_applied']);
        $this->assertStringContainsString('declare(strict_types=1);', $fix['fixed_code']);
        $this->assertStringNotContainsString('?>', $fix['fixed_code']);
        $this->assertStringContainsString("class SampleController\n{", $fix['fixed_code']);
    }

    public function testCleanCodeScores100PercentCompliance(): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\nclass PerfectCode\n{\n    public function run(): void\n    {\n    }\n}";
        $scan = $this->engine->scanCode($code);

        $this->assertTrue($scan['success']);
        $this->assertSame(100, $scan['compliance_score']);
        $this->assertSame(0, $scan['violations_count']);
        $this->assertTrue($scan['is_fully_compliant']);
    }

    public function testEmptySourceCodeFailsGracefully(): void
    {
        $scan = $this->engine->scanCode('');
        $this->assertFalse($scan['success']);

        $fix = $this->engine->fixCode('');
        $this->assertFalse($fix['success']);
    }

    public function testTrailingWhitespaceRemovalInFixer(): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n   \nclass Foo\n{\n}";
        $fix = $this->engine->fixCode($code);

        $this->assertTrue($fix['success']);
        $this->assertDoesNotMatchRegularExpression('/[ \t]+$/m', $fix['fixed_code']);
    }
}
