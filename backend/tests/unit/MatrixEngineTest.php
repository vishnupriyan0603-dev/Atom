<?php

use PHPUnit\Framework\TestCase;
use Atom\Math\MatrixEngine;

/**
 * Phase 31 — MatrixEngine unit tests (5 tests).
 */
class MatrixEngineTest extends TestCase
{
    private MatrixEngine $matrix;

    protected function setUp(): void
    {
        $this->matrix = new MatrixEngine();
    }

    public function testMatrixMultiplication(): void
    {
        $a = [[1, 2], [3, 4]];
        $b = [[2, 0], [1, 2]];

        $res = $this->matrix->multiply($a, $b);

        $this->assertSame([[4.0, 4.0], [10.0, 8.0]], $res);
    }

    public function testDeterminant2x2And3x3(): void
    {
        $m2 = [[4, 6], [3, 8]];
        $this->assertEqualsWithDelta(14.0, $this->matrix->determinant($m2), 0.001);

        $m3 = [
            [6, 1, 1],
            [4, -2, 5],
            [2, 8, 7]
        ];
        $this->assertEqualsWithDelta(-306.0, $this->matrix->determinant($m3), 0.001);
    }

    public function testMatrixInversion(): void
    {
        $m = [[4, 7], [2, 6]];
        $inv = $this->matrix->invert($m);

        $this->assertEqualsWithDelta(0.6, $inv[0][0], 0.001);
        $this->assertEqualsWithDelta(-0.7, $inv[0][1], 0.001);
        $this->assertEqualsWithDelta(-0.2, $inv[1][0], 0.001);
        $this->assertEqualsWithDelta(0.4, $inv[1][1], 0.001);
    }

    public function testSolveLinearSystemGaussianElimination(): void
    {
        // 2x + y = 5, x + 3y = 5 -> solution: x = 2, y = 1
        $a = [[2, 1], [1, 3]];
        $b = [5, 5];

        $sol = $this->matrix->solveSystem($a, $b);

        $this->assertEqualsWithDelta(2.0, $sol[0], 0.001);
        $this->assertEqualsWithDelta(1.0, $sol[1], 0.001);
    }

    public function testSingularMatrixInversionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $singular = [[2, 4], [1, 2]]; // Det = 0
        $this->matrix->invert($singular);
    }
}
