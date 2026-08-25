<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\DependencyGraphEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 43 — DependencyGraphEngine unit tests (6 tests).
 */
class DependencyGraphEngineTest extends TestCase
{
    private DependencyGraphEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DependencyGraphEngine(new SecretRedactor());
    }

    public function testAnalyzeDirectDependencyGraph(): void
    {
        $graph = [
            'Atom\\Auth\\AuthService' => ['Atom\\Database\\Connection', 'Atom\\Security\\SecretRedactor'],
            'Atom\\Database\\Connection' => ['Atom\\Logging\\RuntimeErrorLogger'],
            'Atom\\Security\\SecretRedactor' => [],
            'Atom\\Logging\\RuntimeErrorLogger' => [],
        ];

        $result = $this->engine->analyzeGraph($graph);

        $this->assertTrue($result['success']);
        $this->assertSame(4, $result['total_nodes']);
        $this->assertFalse($result['has_cycles']);
        $this->assertArrayHasKey('Atom\\Auth\\AuthService', $result['nodes']);
        $this->assertSame(2, $result['nodes']['Atom\\Auth\\AuthService']['efferent_coupling']);
    }

    public function testDetectCircularDependencyCycle(): void
    {
        $circularGraph = [
            'OrderService' => ['PaymentService'],
            'PaymentService' => ['InventoryService'],
            'InventoryService' => ['OrderService'], // Cycle!
        ];

        $result = $this->engine->analyzeGraph($circularGraph);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_cycles']);
        $this->assertNotEmpty($result['circular_cycles']);
        $cycle = $result['circular_cycles'][0];
        $this->assertContains('OrderService', $cycle);
        $this->assertContains('PaymentService', $cycle);
        $this->assertContains('InventoryService', $cycle);
    }

    public function testParseDependenciesFromSourceCode(): void
    {
        $code = "<?php\nnamespace Atom\\Payment;\n\nuse Atom\\Database\\Connection;\nuse Atom\\Security\\SecretRedactor;\n\nclass PaymentProcessor {\n    public function __construct() {\n        \$redactor = new SecretRedactor();\n    }\n}";
        $parsed = $this->engine->parseDependenciesFromCode($code);

        $this->assertSame('Atom\\Payment\\PaymentProcessor', $parsed['class_name']);
        $this->assertSame('Atom\\Payment', $parsed['namespace']);
        $this->assertContains('Atom\\Database\\Connection', $parsed['dependencies']);
        $this->assertContains('Atom\\Security\\SecretRedactor', $parsed['dependencies']);
    }

    public function testComputeTopologicalOrder(): void
    {
        $dag = [
            'Level1_App' => ['Level2_Service'],
            'Level2_Service' => ['Level3_Repository'],
            'Level3_Repository' => ['Level4_Database'],
            'Level4_Database' => [],
        ];

        $order = $this->engine->computeTopologicalOrder($dag);

        $this->assertNotEmpty($order);
        $idxApp = array_search('Level1_App', $order, true);
        $idxDb = array_search('Level4_Database', $order, true);
        $this->assertLessThan($idxDb, $idxApp);
    }

    public function testMartinMetricInstabilityAndDistanceCalculation(): void
    {
        $graph = [
            'HubClass' => ['LeafA', 'LeafB', 'LeafC'],
            'LeafA' => [],
            'LeafB' => [],
            'LeafC' => [],
        ];

        $result = $this->engine->analyzeGraph($graph);

        $hub = $result['nodes']['HubClass'];
        $this->assertSame(1.0, $hub['instability_index']); // Efferent 3, Afferent 0 => 3/3 = 1.0 (volatile)
        $this->assertSame('VOLATILE', $hub['stability_class']);
    }

    public function testDefaultArchitectureGraphFallback(): void
    {
        $result = $this->engine->analyzeGraph([]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(4, $result['total_nodes']);
        $this->assertNotEmpty($result['mermaid_diagram']);
        $this->assertStringContainsString('graph TD', $result['mermaid_diagram']);
    }
}
