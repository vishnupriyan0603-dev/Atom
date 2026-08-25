<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstCodeModernizerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 47 — AstCodeModernizerEngine unit tests (6 tests).
 */
class AstCodeModernizerEngineTest extends TestCase
{
    private AstCodeModernizerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AstCodeModernizerEngine(new SecretRedactor());
    }

    public function testUpgradeSwitchToMatchExpression(): void
    {
        $code = "switch (\$status) {\n    case 'A': return 'ALPHA';\n    case 'B': return 'BETA';\n    default: return 'OTHER';\n}";
        $result = $this->engine->modernize($code);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('match ($status)', $result['modernized_code']);
        $this->assertStringContainsString("'A' => 'ALPHA'", $result['modernized_code']);
        $this->assertStringContainsString("default => 'OTHER'", $result['modernized_code']);
    }

    public function testUpgradeStringFunctionsStrpos(): void
    {
        $code = "if (strpos(\$text, 'prefix') !== false) { return true; }\nif (strpos(\$text, 'suffix') === false) { return false; }\nif (strpos(\$text, 'start') === 0) { return 1; }";
        $result = $this->engine->modernize($code);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString("str_contains(\$text, 'prefix')", $result['modernized_code']);
        $this->assertStringContainsString("!str_contains(\$text, 'suffix')", $result['modernized_code']);
        $this->assertStringContainsString("str_starts_with(\$text, 'start')", $result['modernized_code']);
    }

    public function testUpgradeTernaryNullCheckToNullsafeOperator(): void
    {
        $code = "\$email = \$user !== null ? \$user->getEmail() : null;";
        $result = $this->engine->modernize($code);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('$user?->getEmail()', $result['modernized_code']);
    }

    public function testTransformationCountAndMetrics(): void
    {
        $code = "if (strpos(\$action, 'auth') !== false) { \$p = \$user !== null ? \$user->getProfile() : null; }";
        $result = $this->engine->modernize($code);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(2, $result['transformation_count']);
        $this->assertSame('PHP 8.3', $result['target_php_version']);
    }

    public function testEmptyInputFailsGracefully(): void
    {
        $result = $this->engine->modernize("   \n\t   ");
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testPreservesUnchangedModernCode(): void
    {
        $modernCode = "\$res = str_contains(\$val, 'item');\n\$user?->save();";
        $result = $this->engine->modernize($modernCode);

        $this->assertTrue($result['success']);
        $this->assertSame($modernCode, $result['modernized_code']);
    }
}
