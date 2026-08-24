<?php

namespace Atom\Math;

/**
 * Matrix Engine — Phase 31
 *
 * Provides linear algebra matrix computations: addition, multiplication,
 * determinants, inversion, Gaussian elimination, and affine transformations.
 */
class MatrixEngine
{
    public const MAX_DIMENSION = 50;

    /**
     * Multiplies two matrices A (m x k) and B (k x n) -> C (m x n).
     */
    public function multiply(array $a, array $b): array
    {
        $this->validateMatrix($a);
        $this->validateMatrix($b);

        $rowsA = count($a);
        $colsA = count($a[0]);
        $rowsB = count($b);
        $colsB = count($b[0]);

        if ($colsA !== $rowsB) {
            throw new \InvalidArgumentException("Matrix dimension mismatch for multiplication: {$rowsA}x{$colsA} and {$rowsB}x{$colsB}");
        }

        $result = [];
        for ($i = 0; $i < $rowsA; $i++) {
            $result[$i] = [];
            for ($j = 0; $j < $colsB; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $colsA; $k++) {
                    $sum += ($a[$i][$k] * $b[$k][$j]);
                }
                $result[$i][$j] = round($sum, 8);
            }
        }

        return $result;
    }

    /**
     * Calculates the determinant of a square matrix.
     */
    public function determinant(array $matrix): float
    {
        $this->validateSquareMatrix($matrix);
        $n = count($matrix);

        if ($n === 1) {
            return (float)$matrix[0][0];
        }
        if ($n === 2) {
            return round(($matrix[0][0] * $matrix[1][1]) - ($matrix[0][1] * $matrix[1][0]), 8);
        }
        if ($n === 3) {
            $det = $matrix[0][0] * ($matrix[1][1] * $matrix[2][2] - $matrix[1][2] * $matrix[2][1])
                 - $matrix[0][1] * ($matrix[1][0] * $matrix[2][2] - $matrix[1][2] * $matrix[2][0])
                 + $matrix[0][2] * ($matrix[1][0] * $matrix[2][1] - $matrix[1][1] * $matrix[2][0]);
            return round($det, 8);
        }

        // Laplace expansion for N > 3
        $det = 0.0;
        for ($col = 0; $col < $n; $col++) {
            $minor = $this->getMinor($matrix, 0, $col);
            $sign = ($col % 2 === 0) ? 1.0 : -1.0;
            $det += $sign * $matrix[0][$col] * $this->determinant($minor);
        }

        return round($det, 8);
    }

    /**
     * Inverts a square non-singular matrix using Gauss-Jordan elimination.
     */
    public function invert(array $matrix): array
    {
        $this->validateSquareMatrix($matrix);
        $n = count($matrix);
        $det = $this->determinant($matrix);

        if (abs($det) < 1e-12) {
            throw new \InvalidArgumentException('Matrix is singular (determinant is zero) and cannot be inverted');
        }

        // Augmented matrix [A | I]
        $aug = [];
        for ($i = 0; $i < $n; $i++) {
            $aug[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $aug[$i][$j] = (float)$matrix[$i][$j];
            }
            for ($j = 0; $j < $n; $j++) {
                $aug[$i][$n + $j] = ($i === $j) ? 1.0 : 0.0;
            }
        }

        // Gauss-Jordan elimination
        for ($i = 0; $i < $n; $i++) {
            // Find pivot
            $pivotRow = $i;
            for ($k = $i + 1; $k < $n; $k++) {
                if (abs($aug[$k][$i]) > abs($aug[$pivotRow][$i])) {
                    $pivotRow = $k;
                }
            }
            if (abs($aug[$pivotRow][$i]) < 1e-12) {
                throw new \InvalidArgumentException('Matrix is singular during elimination');
            }

            // Swap rows
            if ($pivotRow !== $i) {
                $temp = $aug[$i];
                $aug[$i] = $aug[$pivotRow];
                $aug[$pivotRow] = $temp;
            }

            // Scale pivot row
            $pivotVal = $aug[$i][$i];
            for ($j = 0; $j < 2 * $n; $j++) {
                $aug[$i][$j] /= $pivotVal;
            }

            // Eliminate column
            for ($r = 0; $r < $n; $r++) {
                if ($r !== $i) {
                    $factor = $aug[$r][$i];
                    for ($c = 0; $c < 2 * $n; $c++) {
                        $aug[$r][$c] -= $factor * $aug[$i][$c];
                    }
                }
            }
        }

        // Extract inverted matrix
        $inverse = [];
        for ($i = 0; $i < $n; $i++) {
            $inverse[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $inverse[$i][$j] = round($aug[$i][$n + $j], 8);
            }
        }

        return $inverse;
    }

    /**
     * Solves linear system Ax = b using Gaussian elimination.
     */
    public function solveSystem(array $a, array $b): array
    {
        $this->validateSquareMatrix($a);
        $n = count($a);
        if (count($b) !== $n) {
            throw new \InvalidArgumentException("Vector b length must match matrix dimension {$n}");
        }

        $invA = $this->invert($a);
        $colB = [];
        foreach ($b as $val) {
            $colB[] = [(float)$val];
        }

        $res = $this->multiply($invA, $colB);
        $flat = [];
        foreach ($res as $row) {
            $flat[] = $row[0];
        }
        return $flat;
    }

    /**
     * Generates a 2D affine transformation matrix for rotation, scaling, and translation.
     */
    public function create2dTransform(float $angleDegrees = 0.0, float $scaleX = 1.0, float $scaleY = 1.0, float $transX = 0.0, float $transY = 0.0): array
    {
        $rad = deg2rad($angleDegrees);
        $cos = cos($rad);
        $sin = sin($rad);

        return [
            [round($scaleX * $cos, 6), round(-$scaleY * $sin, 6), round($transX, 6)],
            [round($scaleX * $sin, 6), round($scaleY * $cos, 6), round($transY, 6)],
            [0.0, 0.0, 1.0],
        ];
    }

    /**
     * Transposes a matrix (swaps rows and columns).
     */
    public function transpose(array $matrix): array
    {
        $this->validateMatrix($matrix);
        $rows = count($matrix);
        $cols = count($matrix[0]);

        $transposed = [];
        for ($j = 0; $j < $cols; $j++) {
            $transposed[$j] = [];
            for ($i = 0; $i < $rows; $i++) {
                $transposed[$j][$i] = $matrix[$i][$j];
            }
        }
        return $transposed;
    }

    private function getMinor(array $matrix, int $rowToRemove, int $colToRemove): array
    {
        $minor = [];
        $n = count($matrix);
        for ($i = 0; $i < $n; $i++) {
            if ($i === $rowToRemove) continue;
            $row = [];
            for ($j = 0; $j < $n; $j++) {
                if ($j === $colToRemove) continue;
                $row[] = $matrix[$i][$j];
            }
            $minor[] = $row;
        }
        return $minor;
    }

    private function validateMatrix(array $matrix): void
    {
        if (empty($matrix) || !is_array($matrix[0])) {
            throw new \InvalidArgumentException('Invalid matrix structure: must be 2D non-empty array');
        }
        $rows = count($matrix);
        $cols = count($matrix[0]);

        if ($rows > self::MAX_DIMENSION || $cols > self::MAX_DIMENSION) {
            throw new \InvalidArgumentException('Matrix dimensions exceed maximum allowed limit of ' . self::MAX_DIMENSION);
        }

        for ($i = 0; $i < $rows; $i++) {
            if (!is_array($matrix[$i]) || count($matrix[$i]) !== $cols) {
                throw new \InvalidArgumentException('Inconsistent matrix row lengths');
            }
        }
    }

    private function validateSquareMatrix(array $matrix): void
    {
        $this->validateMatrix($matrix);
        if (count($matrix) !== count($matrix[0])) {
            throw new \InvalidArgumentException('Matrix must be square for determinant/inversion');
        }
    }
}
