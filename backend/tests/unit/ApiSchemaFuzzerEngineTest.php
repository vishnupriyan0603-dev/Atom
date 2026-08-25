<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Testing\ApiSchemaFuzzerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 74 — ApiSchemaFuzzerEngine unit tests (6 tests).
 */
class ApiSchemaFuzzerEngineTest extends TestCase
{
    private ApiSchemaFuzzerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ApiSchemaFuzzerEngine(new SecretRedactor());
    }

    public function testFuzzEndpointExecutesAllMutationCategories(): void
    {
        $res = $this->engine->fuzzEndpoint('/api/users/profile', ['user_id' => 'int', 'filter' => 'string']);

        $this->assertTrue($res['success']);
        $this->assertSame('/api/users/profile', $res['endpoint']);
        $this->assertGreaterThan(10, $res['total_mutations_tested']);
        $this->assertSame(100, $res['robustness_score']);
        $this->assertSame('ENDPOINT_ROBUST', $res['status']);
    }

    public function testDetectVulnerabilityInUnescapedRawSqlParam(): void
    {
        $res = $this->engine->fuzzEndpoint('/api/vulnerable/query', ['custom_sql' => 'raw']);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['vulnerabilities_found']);
        $this->assertLessThan(100, $res['robustness_score']);
        $this->assertSame('VULNERABILITIES_DETECTED', $res['status']);
    }

    public function testDetectReflectedXssInUnescapedHtmlParam(): void
    {
        $res = $this->engine->fuzzEndpoint('/api/vulnerable/render', ['template_html' => 'html_unescaped']);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['vulnerabilities_found']);
        $this->assertContains('xss', array_column($res['vulnerabilities'], 'category'));
    }

    public function testEmptyEndpointPathFailsGracefully(): void
    {
        $res = $this->engine->fuzzEndpoint('');
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['robustness_score']);
    }

    public function testGetFuzzPayloadsIncludesAllCoreCategories(): void
    {
        $payloads = $this->engine->getFuzzPayloads();

        $this->assertArrayHasKey('sqli', $payloads);
        $this->assertArrayHasKey('xss', $payloads);
        $this->assertArrayHasKey('type_juggling', $payloads);
        $this->assertArrayHasKey('boundary_overflow', $payloads);
    }

    public function testSimulateFuzzExecutionReportsSeverity(): void
    {
        $out = $this->engine->simulateFuzzExecution('search', 'string', "<script>alert(1)</script>", 'xss');

        $this->assertSame('search', $out['param']);
        $this->assertFalse($out['is_vulnerable']);
        $this->assertSame('LOW', $out['severity']);
    }
}
