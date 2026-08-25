<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\OwaspAutoPatcherEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 47 — OwaspAutoPatcherEngine unit tests (6 tests).
 */
class OwaspAutoPatcherEngineTest extends TestCase
{
    private OwaspAutoPatcherEngine $patcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patcher = new OwaspAutoPatcherEngine(new SecretRedactor());
    }

    public function testDetectSqlInjectionVulnerability(): void
    {
        $code = "\$user = \$db->query(\"SELECT * FROM users WHERE id = \" . \$id);";
        $scan = $this->patcher->scan($code);

        $this->assertSame(1, $scan['vulnerability_count']);
        $this->assertSame('CRITICAL', $scan['highest_severity']);
        $this->assertSame('CWE-89', $scan['vulnerabilities'][0]['cwe']);
    }

    public function testDetectCrossSiteScriptingVulnerability(): void
    {
        $code = "echo \$_GET['search'];";
        $scan = $this->patcher->scan($code);

        $this->assertSame(1, $scan['vulnerability_count']);
        $this->assertSame('HIGH', $scan['highest_severity']);
        $this->assertSame('CWE-79', $scan['vulnerabilities'][0]['cwe']);
    }

    public function testDetectPathTraversalVulnerability(): void
    {
        $code = "\$data = file_get_contents(\$_GET['file_path']);";
        $scan = $this->patcher->scan($code);

        $this->assertSame(1, $scan['vulnerability_count']);
        $this->assertSame('CWE-22', $scan['vulnerabilities'][0]['cwe']);
    }

    public function testAutoPatchSqlInjectionToPreparedStatement(): void
    {
        $code = "\$user = \$db->query(\"SELECT * FROM users WHERE id = \" . \$id);";
        $patchResult = $this->patcher->autoPatch($code);

        $this->assertTrue($patchResult['success']);
        $this->assertSame(1, $patchResult['patches_applied_count']);
        $this->assertStringContainsString('SELECT * FROM users WHERE id = ?', $patchResult['patched_code']);
        $this->assertStringContainsString('[$id]', $patchResult['patched_code']);
        $this->assertSame(0, $patchResult['remaining_vulnerabilities']);
    }

    public function testAutoPatchXssToHtmlspecialchars(): void
    {
        $code = "echo \$_GET['query'];";
        $patchResult = $this->patcher->autoPatch($code);

        $this->assertTrue($patchResult['success']);
        $this->assertStringContainsString("htmlspecialchars(\$_GET['query'], ENT_QUOTES, 'UTF-8')", $patchResult['patched_code']);
    }

    public function testAutoPatchPathTraversalToBasename(): void
    {
        $code = "\$content = file_get_contents(\$_GET['path']);";
        $patchResult = $this->patcher->autoPatch($code);

        $this->assertTrue($patchResult['success']);
        $this->assertStringContainsString("file_get_contents(basename(\$_GET['path']))", $patchResult['patched_code']);
    }
}
