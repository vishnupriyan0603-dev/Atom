<?php

use PHPUnit\Framework\TestCase;
use Atom\Math\SymbolicEquationSolver;
use Atom\Math\MatrixEngine;
use Atom\Algorithms\AlgorithmComplexityAnalyzer;

/**
 * Phase 31 — ComputationSecurityPassTest security & safety tests (5 tests).
 */
class ComputationSecurityPassTest extends TestCase
{
    public function testSecretRedactionInEquationSolver(): void
    {
        $solver = new SymbolicEquationSolver();
        $eq = "2x + 4 = 10; api_key=sk-ant-api03-secret12345678901234567890";

        $res = $solver->solve($eq);

        $this->assertStringNotContainsString('sk-ant-api03', $res['equation']);
        $this->assertSame(3.0, $res['solutions'][0]);
    }

    public function testMatrixDimensionExplosionProtection(): void
    {
        $matrix = new MatrixEngine();
        // Construct oversized 51x51 matrix
        $huge = array_fill(0, 51, array_fill(0, 51, 1.0));

        $this->expectException(\InvalidArgumentException::class);
        $matrix->multiply($huge, $huge);
    }

    public function testZeroDivisionSafetyInEquationSolver(): void
    {
        $solver = new SymbolicEquationSolver();
        // 0x + 5 = 10 -> inconsistent (no solution, no fatal division by zero)
        $res = $solver->solve('0x + 5 = 10');

        $this->assertSame('inconsistent', $res['status']);
        $this->assertContains('No solution (contradiction)', $res['solutions']);
    }

    public function testComplexityAnalyzerRejectsEmptyCode(): void
    {
        $analyzer = new AlgorithmComplexityAnalyzer();

        $this->expectException(\InvalidArgumentException::class);
        $analyzer->analyze('   ');
    }

    public function testNoEvalOrDangerousExecutionUsed(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $solverCode = file_get_contents($rootDir . '/src/Math/SymbolicEquationSolver.php');
        $matrixCode = file_get_contents($rootDir . '/src/Math/MatrixEngine.php');

        $this->assertNotFalse($solverCode);
        $this->assertNotFalse($matrixCode);
        $this->assertStringNotContainsString('eval(', $solverCode);
        $this->assertStringNotContainsString('exec(', $solverCode);
        $this->assertStringNotContainsString('shell_exec(', $solverCode);
        $this->assertStringNotContainsString('eval(', $matrixCode);
    }
}
