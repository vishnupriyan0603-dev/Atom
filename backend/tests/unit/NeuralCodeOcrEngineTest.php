<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\NeuralCodeOcrEngine;
use Atom\Vision\MultiModalPayload;
use Atom\Security\SecretRedactor;

/**
 * Phase 42 — NeuralCodeOcrEngine unit tests (6 tests).
 */
class NeuralCodeOcrEngineTest extends TestCase
{
    private NeuralCodeOcrEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new NeuralCodeOcrEngine(new SecretRedactor());
    }

    public function testExtractCodeFromRawText(): void
    {
        $rawCode = "class UserController {\n    public function index(): array {\n        return ['status' => 'active'];\n    }\n}";
        $result = $this->engine->extractCode($rawCode);

        $this->assertTrue($result['success']);
        $this->assertSame('php', $result['language']);
        $this->assertStringContainsString('class UserController', $result['code']);
        $this->assertNotEmpty($result['symbols']['classes']);
        $this->assertSame('UserController', $result['symbols']['classes'][0]);
    }

    public function testExtractCodeFromMultiModalPayload(): void
    {
        $payload = new MultiModalPayload('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'image/png', 'sample_code.png');
        $result = $this->engine->extractCode($payload);

        $this->assertTrue($result['success']);
        $this->assertSame('sample_code.png', $result['file_name']);
        $this->assertNotEmpty($result['code']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testLanguageDetectionHeuristics(): void
    {
        $this->assertSame('python', $this->engine->detectLanguage("def calculate_metrics(items):\n    return len(items)"));
        $this->assertSame('sql', $this->engine->detectLanguage("SELECT id, username FROM users WHERE active = 1;"));
        $this->assertSame('html', $this->engine->detectLanguage("<!DOCTYPE html><html><body><h1>Title</h1></body></html>"));
        $this->assertSame('csharp', $this->engine->detectLanguage("using System;\nnamespace Core {\n    class App {\n        void Run() {}\n    }\n}"));
    }

    public function testNormalizeVisualOcrArtifacts(): void
    {
        $corrupted = "const【token】=‘sk-12345’（）; \nif(a==b) { return false; } ";
        $normalized = $this->engine->normalizeVisualOcrArtifacts($corrupted);

        $this->assertStringNotContainsString('【', $normalized);
        $this->assertStringNotContainsString('】', $normalized);
        $this->assertStringNotContainsString('（', $normalized);
        $this->assertStringContainsString('[', $normalized);
        $this->assertStringContainsString(']', $normalized);
    }

    public function testExtractSymbolsClassesAndMethods(): void
    {
        $code = "class InvoiceProcessor {\n    public function processInvoice(int \$id): bool {\n        return true;\n    }\n}";
        $symbols = $this->engine->extractSymbols($code, 'php');

        $this->assertContains('InvoiceProcessor', $symbols['classes']);
        $this->assertContains('processInvoice', $symbols['functions']);
    }

    public function testEmptyInputReturnsGracefulFailure(): void
    {
        $result = $this->engine->extractCode("   \n   \t  ");
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No visual text', $result['error']);
    }
}
