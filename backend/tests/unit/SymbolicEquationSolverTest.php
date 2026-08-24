<?php

use PHPUnit\Framework\TestCase;
use Atom\Math\SymbolicEquationSolver;

/**
 * Phase 31 — SymbolicEquationSolver unit tests (5 tests).
 */
class SymbolicEquationSolverTest extends TestCase
{
    private SymbolicEquationSolver $solver;

    protected function setUp(): void
    {
        $this->solver = new SymbolicEquationSolver();
    }

    public function testSolveLinearEquation(): void
    {
        $res = $this->solver->solve('3x + 9 = 24');

        $this->assertSame('linear', $res['type']);
        $this->assertSame('solved', $res['status']);
        $this->assertCount(1, $res['solutions']);
        $this->assertEqualsWithDelta(5.0, $res['solutions'][0], 0.001);
        $this->assertNotEmpty($res['steps']);
    }

    public function testSolveQuadraticDistinctRoots(): void
    {
        $res = $this->solver->solve('x^2 - 5x + 6 = 0');

        $this->assertSame('quadratic', $res['type']);
        $this->assertSame('solved', $res['status']);
        $this->assertCount(2, $res['solutions']);
        $this->assertEqualsWithDelta(3.0, $res['solutions'][0], 0.001);
        $this->assertEqualsWithDelta(2.0, $res['solutions'][1], 0.001);
    }

    public function testSolveQuadraticRepeatedRoot(): void
    {
        $res = $this->solver->solve('x^2 - 4x + 4 = 0');

        $this->assertSame('quadratic', $res['type']);
        $this->assertSame('solved', $res['status']);
        $this->assertCount(1, $res['solutions']);
        $this->assertEqualsWithDelta(2.0, $res['solutions'][0], 0.001);
    }

    public function testSolveQuadraticComplexConjugateRoots(): void
    {
        $res = $this->solver->solve('x^2 + 1 = 0');

        $this->assertSame('quadratic', $res['type']);
        $this->assertSame('complex_roots', $res['status']);
        $this->assertCount(2, $res['solutions']);
        $this->assertStringContainsString('i', $res['solutions'][0]);
    }

    public function testSimplifyExpression(): void
    {
        $simplified = $this->solver->simplify('3x + 2x^2 - x + 7 - 2');
        $this->assertStringContainsString('2x^2', $simplified);
        $this->assertStringContainsString('2x', $simplified);
        $this->assertStringContainsString('5', $simplified);
    }
}
