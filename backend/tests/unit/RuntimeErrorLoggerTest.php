<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Logging\RuntimeErrorLogger;
use Atom\Security\SecretRedactor;
use Atom\Testing\SelfCorrectionEngine;

class RuntimeErrorLoggerTest extends TestCase
{
    private string $tempLogFile;
    private RuntimeErrorLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLogFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_runtime_errors_' . uniqid() . '.json';
        $this->logger = new RuntimeErrorLogger($this->tempLogFile, new SecretRedactor(), new SelfCorrectionEngine());
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            @unlink($this->tempLogFile);
        }
        parent::tearDown();
    }

    public function testLogErrorCreatesStructuredRecord(): void
    {
        $entry = $this->logger->logError([
            'message' => 'TypeError: Return value must be of type bool, null returned',
            'file' => 'src/Auth/AuthService.php',
            'line' => 45,
            'source' => 'server',
            'user_action' => 'User login attempt',
            'level' => 'critical',
            'stack_trace' => '#0 src/Auth/AuthService.php(45): authenticate()',
            'context' => ['token' => 'sk-live-secret-key-12345'],
        ]);

        $this->assertNotEmpty($entry['id']);
        $this->assertStringStartsWith('err_', $entry['id']);
        $this->assertSame('server', $entry['source']);
        $this->assertSame('critical', $entry['level']);
        $this->assertSame('unresolved', $entry['status']);
        $this->assertArrayHasKey('diagnosis', $entry);
        $this->assertArrayHasKey('fix_suggestion', $entry);
        $this->assertStringContainsString('TypeError', $entry['diagnosis']['error_type']);

        // Assert secret redaction
        $contextJson = json_encode($entry['context']);
        $this->assertStringNotContainsString('sk-live-secret-key-12345', $contextJson);
    }

    public function testGetErrorsAndStatusFiltering(): void
    {
        $e1 = $this->logger->logError(['message' => 'Error 1', 'file' => 'app.js', 'line' => 10]);
        $e2 = $this->logger->logError(['message' => 'Error 2', 'file' => 'index.php', 'line' => 20]);

        $allErrors = $this->logger->getErrors();
        $this->assertCount(2, $allErrors);

        // Resolve e1
        $this->logger->resolveError($e1['id'], 'Fixed null check');

        $unresolved = $this->logger->getErrors('unresolved');
        $this->assertCount(1, $unresolved);
        $this->assertSame($e2['id'], $unresolved[0]['id']);

        $resolved = $this->logger->getErrors('resolved');
        $this->assertCount(1, $resolved);
        $this->assertSame($e1['id'], $resolved[0]['id']);
        $this->assertSame('Fixed null check', $resolved[0]['resolution_notes']);
    }

    public function testAutoFixSynthesizesPatch(): void
    {
        $entry = $this->logger->logError([
            'message' => 'TypeError: function validate() return type mismatch',
            'file' => 'src/Validator.php',
            'line' => 12,
        ]);

        $fixResult = $this->logger->autoFix($entry['id'], "function validate(\$input) {\n    return true;\n}");

        $this->assertTrue($fixResult['success']);
        $this->assertArrayHasKey('patch', $fixResult);
        $this->assertNotEmpty($fixResult['patch']['patched_code']);

        // Check error status became auto_fixed
        $updated = $this->logger->getErrorById($entry['id']);
        $this->assertSame('resolved', $updated['status']);
        $this->assertStringContainsString('Auto-fix patch generated', $updated['resolution_notes']);
    }

    public function testClearErrorsEmptiesLog(): void
    {
        $this->logger->logError(['message' => 'Temporary test error']);
        $this->assertCount(1, $this->logger->getErrors());

        $this->logger->clearErrors();
        $this->assertCount(0, $this->logger->getErrors());
    }

    public function testFixSuggestionForJsonParsingError(): void
    {
        $entry = $this->logger->logError([
            'message' => "SyntaxError: Unexpected token '<', '<!DOCTYPE...' is not valid JSON",
            'file' => 'frontend/web/admin/daemon.php',
            'line' => 151,
            'source' => 'client',
        ]);

        $this->assertSame('API / JSON Response Handling', $entry['fix_suggestion']['category']);
        $this->assertNotEmpty($entry['fix_suggestion']['steps']);
        $this->assertStringContainsString('safeJsonFetch', $entry['fix_suggestion']['suggested_code']);
    }
}
