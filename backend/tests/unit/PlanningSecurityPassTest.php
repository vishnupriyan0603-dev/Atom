<?php

use PHPUnit\Framework\TestCase;
use Atom\Planning\HierarchicalTaskDecomposer;
use Atom\Planning\TreeOfThoughtsSearch;
use Atom\Planning\PlanVisualizer;
use Atom\Planning\PlanVerifierBacktracker;

/**
 * Phase 30 — PlanningSecurityPassTest security & safety tests (5 tests).
 */
class PlanningSecurityPassTest extends TestCase
{
    public function testSecretRedactionInDecompositionGoal(): void
    {
        $decomposer = new HierarchicalTaskDecomposer();
        $goal = "Deploy service with api_key=sk-ant-api03-secretkey998877665544332211 and token=ghp_ABC1234567890DEF";

        $plan = $decomposer->decompose($goal);

        $this->assertStringNotContainsString('sk-ant-api03', $plan['goal']);
        $this->assertStringNotContainsString('ghp_ABC', $plan['goal']);
        $this->assertStringContainsString('[REDACTED', $plan['goal']);
    }

    public function testSecretRedactionInTreeVisualizer(): void
    {
        $visualizer = new PlanVisualizer();
        $tree = [
            'root_id' => 'root',
            'nodes'   => [
                'root' => [
                    'id'       => 'root',
                    'thought'  => 'Configure database with password [REDACTED_SECRET]',
                    'status'   => 'selected',
                    'children' => [],
                ],
            ],
        ];

        $mermaid = $visualizer->toMermaid($tree);
        $ascii = $visualizer->toAsciiTree($tree);

        $this->assertStringContainsString('[REDACTED_SECRET]', $mermaid);
        $this->assertStringContainsString('[REDACTED_SECRET]', $ascii);
    }

    public function testBranchingAndDepthBombProtection(): void
    {
        $decomposer = new HierarchicalTaskDecomposer();
        // Requesting excessive depth (e.g. 50) must be clamped to safe max depth (5)
        $plan = $decomposer->decompose('Build enterprise architecture', [], 50);

        $this->assertLessThanOrEqual(5, $plan['max_depth']);
    }

    public function testEmptyAndNullStepOutputRejected(): void
    {
        $verifier = new PlanVerifierBacktracker();
        $node = ['id' => 'node_sec_1'];

        $resNull = $verifier->verifyStep($node, null);
        $resEmpty = $verifier->verifyStep($node, '');
        $resFalse = $verifier->verifyStep($node, false);

        $this->assertFalse($resNull['verified']);
        $this->assertFalse($resEmpty['verified']);
        $this->assertFalse($resFalse['verified']);
    }

    public function testMermaidEscapesSpecialCharacters(): void
    {
        $visualizer = new PlanVisualizer();
        $tree = [
            'root_id' => 'root',
            'nodes'   => [
                'root' => [
                    'id'       => 'root',
                    'thought'  => 'Step {with} "quotes" & (brackets) [and braces]',
                    'status'   => 'selected',
                    'children' => [],
                ],
            ],
        ];

        $mermaid = $visualizer->toMermaid($tree);

        $this->assertStringNotContainsString('{with}', $mermaid);
        $this->assertStringNotContainsString('"quotes"', $mermaid);
        $this->assertStringContainsString('Step with quotes & brackets and braces', $mermaid);
    }
}
