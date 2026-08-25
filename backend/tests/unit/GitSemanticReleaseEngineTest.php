<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Automation\GitSemanticReleaseEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 59 — GitSemanticReleaseEngine unit tests (6 tests).
 */
class GitSemanticReleaseEngineTest extends TestCase
{
    private GitSemanticReleaseEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new GitSemanticReleaseEngine(new SecretRedactor());
    }

    public function testAnalyzeFeatBumpsMinorVersion(): void
    {
        $commits = ['feat(voice): add real-time spectral denoiser'];
        $res = $this->engine->analyzeRelease($commits, 'v2.0.0');

        $this->assertTrue($res['success']);
        $this->assertSame('MINOR', $res['bump_type']);
        $this->assertSame('v2.1.0', $res['next_version']);
        $this->assertNotEmpty($res['categories']['features']);
    }

    public function testAnalyzeFixBumpsPatchVersion(): void
    {
        $commits = ['fix(admin): resolve sidebar scrolling bug'];
        $res = $this->engine->analyzeRelease($commits, 'v2.1.0');

        $this->assertTrue($res['success']);
        $this->assertSame('PATCH', $res['bump_type']);
        $this->assertSame('v2.1.1', $res['next_version']);
        $this->assertNotEmpty($res['categories']['fixes']);
    }

    public function testAnalyzeBreakingChangeBumpsMajorVersion(): void
    {
        $commits = [
            'feat(auth): refactor token engine',
            'BREAKING CHANGE: migrated to quantum lattice signatures',
        ];
        $res = $this->engine->analyzeRelease($commits, 'v2.1.5');

        $this->assertTrue($res['success']);
        $this->assertSame('MAJOR', $res['bump_type']);
        $this->assertSame('v3.0.0', $res['next_version']);
    }

    public function testCalculateNextSemverRules(): void
    {
        $this->assertSame('v3.0.0', $this->engine->calculateNextSemver('v2.4.9', 'MAJOR'));
        $this->assertSame('v2.5.0', $this->engine->calculateNextSemver('v2.4.9', 'MINOR'));
        $this->assertSame('v2.4.10', $this->engine->calculateNextSemver('v2.4.9', 'PATCH'));
    }

    public function testSynthesizesStructuredMarkdownChangelog(): void
    {
        $commits = [
            'feat: add multi-tenant rate limiter',
            'fix: division by zero in acoustic filter',
            'perf: speed up AST index scan',
        ];
        $res = $this->engine->analyzeRelease($commits, 'v2.0.0');

        $md = $res['changelog_markdown'];
        $this->assertStringContainsString('## [v2.1.0]', $md);
        $this->assertStringContainsString('### 🚀 Features & Subsystems', $md);
        $this->assertStringContainsString('### 🐛 Bug Fixes', $md);
        $this->assertStringContainsString('### ⚡ Performance Improvements', $md);
    }

    public function testEmptyCommitsFailsGracefully(): void
    {
        $res = $this->engine->analyzeRelease([], 'v2.0.0');
        $this->assertFalse($res['success']);
        $this->assertSame('NONE', $res['bump_type']);
    }
}
