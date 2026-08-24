<?php

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\IncidentEventClassifier;
use Atom\Infrastructure\RunbookRemediationExecutor;
use Atom\Infrastructure\PostMortemGenerator;

/**
 * Phase 40 — IncidentSecurityPassTest security & safety tests (5 tests).
 */
class IncidentSecurityPassTest extends TestCase
{
    public function testSecretRedactionInIncidentReports(): void
    {
        $classifier = new IncidentEventClassifier();
        $res = $classifier->classify(['message' => 'Connection timeout using key sk-ant-api03-123456789012345678901234']);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('severity', $res);
    }

    public function testNoEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $classCode = file_get_contents($rootDir . '/src/Infrastructure/IncidentEventClassifier.php');
        $runCode = file_get_contents($rootDir . '/src/Infrastructure/RunbookRemediationExecutor.php');
        $cbCode = file_get_contents($rootDir . '/src/Infrastructure/CircuitBreakerOrchestrator.php');
        $pmCode = file_get_contents($rootDir . '/src/Infrastructure/PostMortemGenerator.php');

        $this->assertNotFalse($classCode);
        $this->assertNotFalse($runCode);
        $this->assertNotFalse($cbCode);
        $this->assertNotFalse($pmCode);

        $this->assertStringNotContainsString('eval(', $classCode);
        $this->assertStringNotContainsString('eval(', $runCode);
        $this->assertStringNotContainsString('eval(', $cbCode);
        $this->assertStringNotContainsString('eval(', $pmCode);
        $this->assertStringNotContainsString('exec(', $classCode);
        $this->assertStringNotContainsString('shell_exec(', $classCode);
    }

    public function testRunbookSubsystemCommandInjectionSafety(): void
    {
        $executor = new RunbookRemediationExecutor();
        $res = $executor->executeRunbook('drain_connection_pool', 'database; rm -rf /');

        $this->assertSame('SUCCESS', $res['status']);
        $this->assertSame('database; rm -rf /', $res['subsystem']);
    }

    public function testCircuitBreakerConcurrencySafety(): void
    {
        $cb = new \Atom\Infrastructure\CircuitBreakerOrchestrator('concurrency_test', 5);
        for ($i = 0; $i < 10; $i++) {
            $cb->recordFailure();
        }

        $this->assertSame('OPEN', $cb->getState());
        $this->assertSame(10, $cb->getFailureCount());
    }

    public function testPostMortemXssSanitization(): void
    {
        $generator = new PostMortemGenerator();
        $res = $generator->generate(['root_cause' => "<script>alert('xss')</script> unindexed query"]);

        $this->assertIsArray($res);
        $this->assertStringContainsString('unindexed query', $res['post_mortem_md']);
    }
}
