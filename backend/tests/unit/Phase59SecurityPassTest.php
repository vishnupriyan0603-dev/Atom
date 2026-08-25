<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Automation\GitSemanticReleaseEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 59 — Phase59SecurityPassTest security & safety tests (5 tests).
 */
class Phase59SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInCommitMessages(): void
    {
        $engine = new GitSemanticReleaseEngine($this->redactor);
        $commits = ['feat(auth): rotated master secret sk-1122334455667788990011223344'];

        $res = $engine->analyzeRelease($commits, 'v2.0.0');
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['changelog_markdown']);
    }

    public function testTagInjectionSanitization(): void
    {
        $engine = new GitSemanticReleaseEngine($this->redactor);
        $res = $engine->calculateNextSemver('v2.0.0; rm -rf /', 'PATCH');

        $this->assertMatchesRegularExpression('/^v[0-9]+\.[0-9]+\.[0-9]+$/', $res);
    }

    public function testLargeCommitHistoryResilience(): void
    {
        $engine = new GitSemanticReleaseEngine($this->redactor);
        $largeCommitList = [];
        for ($i = 0; $i < 500; $i++) {
            $largeCommitList[] = "feat(subsystem_{$i}): implement automated unit module";
        }

        $startTime = microtime(true);
        $res = $engine->analyzeRelease($largeCommitList, 'v2.0.0');
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testSemVerBumpsAlwaysMonotonicallyIncrease(): void
    {
        $engine = new GitSemanticReleaseEngine($this->redactor);
        $current = 'v2.1.0';

        $major = $engine->calculateNextSemver($current, 'MAJOR');
        $minor = $engine->calculateNextSemver($current, 'MINOR');
        $patch = $engine->calculateNextSemver($current, 'PATCH');

        $this->assertSame('v3.0.0', $major);
        $this->assertSame('v2.2.0', $minor);
        $this->assertSame('v2.1.1', $patch);
    }

    public function testNoDangerousEvalOrShellExecutionInAutomationSubsystem(): void
    {
        $files = [
            'src/Automation/GitSemanticReleaseEngine.php',
            'src/Automation/DistributedCronSchedulerEngine.php',
            'src/Automation/DistributedJobStore.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
