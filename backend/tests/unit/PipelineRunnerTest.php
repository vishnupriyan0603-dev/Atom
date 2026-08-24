<?php

use PHPUnit\Framework\TestCase;
use Atom\CiCd\PipelineRunner;

/**
 * Phase 29 — PipelineRunner unit tests (5 tests).
 */
class PipelineRunnerTest extends TestCase
{
    private PipelineRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new PipelineRunner();
    }

    public function testRunDefaultPipelineExecutesAllStages(): void
    {
        $res = $this->runner->runPipeline();
        $this->assertSame('success', $res['status']);
        $this->assertSame(5, $res['stages_executed']);
        $this->assertArrayHasKey('lint', $res['stages']);
        $this->assertArrayHasKey('unit_tests', $res['stages']);
        $this->assertArrayHasKey('security_scan', $res['stages']);
        $this->assertArrayHasKey('coverage_check', $res['stages']);
        $this->assertArrayHasKey('build_check', $res['stages']);
    }

    public function testRunCustomSubsetOfStages(): void
    {
        $res = $this->runner->runPipeline(['lint', 'unit_tests']);
        $this->assertSame('success', $res['status']);
        $this->assertSame(2, $res['stages_executed']);
        $this->assertArrayNotHasKey('build_check', $res['stages']);
    }

    public function testPipelineRecordsStageTimings(): void
    {
        $res = $this->runner->runPipeline(['lint']);
        $stage = $res['stages']['lint'];

        $this->assertArrayHasKey('duration_ms', $stage);
        $this->assertGreaterThanOrEqual(0, $stage['duration_ms']);
    }

    public function testGetPipelineStatusByRunId(): void
    {
        $run = $this->runner->runPipeline(['unit_tests']);
        $retrieved = $this->runner->getPipelineStatus($run['run_id']);

        $this->assertNotNull($retrieved);
        $this->assertSame($run['run_id'], $retrieved['run_id']);
        $this->assertSame('success', $retrieved['status']);
    }

    public function testGetRecentPipelinesHistory(): void
    {
        $this->runner->runPipeline(['lint']);
        $this->runner->runPipeline(['unit_tests']);

        $recent = $this->runner->getRecentPipelines(5);
        $this->assertGreaterThanOrEqual(2, count($recent));
    }
}
