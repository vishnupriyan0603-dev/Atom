<?php

use PHPUnit\Framework\TestCase;
use Atom\Planning\PlanVerifierBacktracker;

/**
 * Phase 30 — PlanVerifierBacktracker unit tests (5 tests).
 */
class PlanVerifierBacktrackerTest extends TestCase
{
    private PlanVerifierBacktracker $verifier;

    protected function setUp(): void
    {
        PlanVerifierBacktracker::reset();
        $this->verifier = new PlanVerifierBacktracker();
    }

    public function testVerifyStepPassesValidOutput(): void
    {
        $node = ['id' => 'node_1', 'type' => 'tool_call'];
        $output = ['status' => 'success', 'result' => 'Compiled cleanly without warnings'];

        $res = $this->verifier->verifyStep($node, $output);

        $this->assertTrue($res['verified']);
        $this->assertNull($res['flaw']);
        $this->assertSame('node_1', $res['node_id']);
    }

    public function testVerifyStepDetectsErrorPattern(): void
    {
        $node = ['id' => 'node_2', 'type' => 'tool_call'];
        $output = "Fatal error: Uncaught TypeError in Handler.php on line 42";

        $res = $this->verifier->verifyStep($node, $output);

        $this->assertFalse($res['verified']);
        $this->assertNotNull($res['flaw']);
        $this->assertLessThan(0.5, $res['confidence']);
    }

    public function testVerifyStepEnforcesRequiredSchemaKeys(): void
    {
        $node = ['id' => 'node_3'];
        $output = ['data' => 'content'];
        $criteria = ['required_keys' => ['data', 'checksum', 'signature']];

        $res = $this->verifier->verifyStep($node, $output, $criteria);

        $this->assertFalse($res['verified']);
        $this->assertStringContainsString('checksum', $res['flaw']);
    }

    public function testBacktrackFindsViableAncestorAndSelectsAlternateBranch(): void
    {
        $tree = [
            'root_id' => 'root',
            'nodes'   => [
                'root' => [
                    'id'        => 'root',
                    'parent_id' => null,
                    'status'    => 'selected',
                    'children'  => ['branch_a', 'branch_b'],
                ],
                'branch_a' => [
                    'id'        => 'branch_a',
                    'parent_id' => 'root',
                    'status'    => 'failed',
                    'children'  => [],
                ],
                'branch_b' => [
                    'id'        => 'branch_b',
                    'parent_id' => 'root',
                    'status'    => 'evaluated',
                    'children'  => [],
                ],
            ],
        ];

        $res = $this->verifier->backtrack($tree, 'branch_a');

        $this->assertTrue($res['backtracked']);
        $this->assertSame('root', $res['ancestor_id']);
        $this->assertSame('branch_b', $res['next_branch_id']);
        $this->assertSame('selected', $tree['nodes']['branch_b']['status']);
    }

    public function testSaveAndRestoreSnapshot(): void
    {
        $state = ['step' => 1, 'context' => 'Initialized DB'];
        $this->verifier->saveSnapshot('tree_123', 'node_1', $state);

        $snap = $this->verifier->getSnapshot('tree_123', 'node_1');
        $this->assertNotNull($snap);
        $this->assertSame($state, $snap['state']);
    }
}
