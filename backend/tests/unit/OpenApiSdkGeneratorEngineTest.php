<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\OpenApiSdkGeneratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 57 — OpenApiSdkGeneratorEngine unit tests (6 tests).
 */
class OpenApiSdkGeneratorEngineTest extends TestCase
{
    private OpenApiSdkGeneratorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new OpenApiSdkGeneratorEngine(new SecretRedactor());
    }

    public function testGenerateOpenApiSpecStructure(): void
    {
        $spec = $this->engine->generateOpenApiSpec();

        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('/command-center/platform-status', $spec['paths']);
        $this->assertArrayHasKey('/voice/synthesize', $spec['paths']);
    }

    public function testGenerateTypeScriptSdk(): void
    {
        $sdk = $this->engine->generateSdk('typescript');

        $this->assertTrue($sdk['success']);
        $this->assertSame('typescript', $sdk['language']);
        $this->assertStringContainsString('export class AtomClient', $sdk['code']);
        $this->assertStringContainsString('public async getPlatformStatus', $sdk['code']);
    }

    public function testGeneratePythonSdk(): void
    {
        $sdk = $this->engine->generateSdk('python');

        $this->assertTrue($sdk['success']);
        $this->assertSame('python', $sdk['language']);
        $this->assertStringContainsString('class AtomClient:', $sdk['code']);
        $this->assertStringContainsString('def get_platform_status(self):', $sdk['code']);
    }

    public function testGenerateCSharpSdk(): void
    {
        $sdk = $this->engine->generateSdk('csharp');

        $this->assertTrue($sdk['success']);
        $this->assertSame('csharp', $sdk['language']);
        $this->assertStringContainsString('public class AtomClient', $sdk['code']);
        $this->assertStringContainsString('Task<string> GetPlatformStatusAsync()', $sdk['code']);
    }

    public function testGeneratePhpSdk(): void
    {
        $sdk = $this->engine->generateSdk('php');

        $this->assertTrue($sdk['success']);
        $this->assertSame('php', $sdk['language']);
        $this->assertStringContainsString('class AtomClient', $sdk['code']);
    }

    public function testFallbackToTypeScriptOnUnknownLanguage(): void
    {
        $sdk = $this->engine->generateSdk('unknown_ruby');

        $this->assertTrue($sdk['success']);
        $this->assertSame('typescript', $sdk['language']);
    }
}
