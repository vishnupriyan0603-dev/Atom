<?php

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\PostMortemGenerator;

/**
 * Phase 40 — PostMortemGenerator unit tests (5 tests).
 */
class PostMortemGeneratorTest extends TestCase
{
    private PostMortemGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PostMortemGenerator();
    }

    public function testGenerateStructuredPostMortem(): void
    {
        $data = [
            'incident_id'      => 'inc_test_101',
            'severity'         => 'SEV1_CRITICAL',
            'subsystem'        => 'worker_swarm',
            'root_cause'       => 'Deadlock in async worker messaging loop',
            'downtime_minutes' => 5.5,
        ];

        $res = $this->generator->generate($data);

        $this->assertSame('inc_test_101', $res['incident_id']);
        $this->assertSame('SEV1_CRITICAL', $res['severity']);
        $this->assertSame(5.5, $res['downtime_minutes']);
        $this->assertNotEmpty($res['post_mortem_md']);
    }

    public function testPostMortemContainsMarkdownSections(): void
    {
        $res = $this->generator->generate(['incident_id' => 'inc_md']);
        $md = $res['post_mortem_md'];

        $this->assertStringContainsString('# Incident Post-Mortem', $md);
        $this->assertStringContainsString('## Root Cause Analysis', $md);
        $this->assertStringContainsString('## Remediation & Recovery', $md);
        $this->assertStringContainsString('## Preventative Action Items', $md);
    }

    public function testDefaultIncidentDataFallback(): void
    {
        $res = $this->generator->generate([]);

        $this->assertNotEmpty($res['incident_id']);
        $this->assertSame('SEV2_MAJOR', $res['severity']);
    }

    public function testRootCausePreservedInOutput(): void
    {
        $res = $this->generator->generate(['root_cause' => 'Network partition between cluster leaders']);

        $this->assertSame('Network partition between cluster leaders', $res['root_cause']);
    }

    public function testSubsystemTaggingInMarkdown(): void
    {
        $res = $this->generator->generate(['subsystem' => 'billing_service']);

        $this->assertStringContainsString('billing_service', $res['post_mortem_md']);
    }
}
