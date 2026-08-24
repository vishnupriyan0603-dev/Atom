<?php

namespace Atom\Math;

use Atom\Security\SecretRedactor;

/**
 * Symbolic Equation Solver — Phase 31
 *
 * Provides exact algebraic equation parsing, simplification,
 * linear/quadratic solving, and step-by-step mathematical derivation breakdown.
 */
class SymbolicEquationSolver
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Solves an algebraic equation (linear or quadratic).
     *
     * @param string $equation e.g. "2x + 5 = 15" or "x^2 - 5x + 6 = 0"
     * @return array Solutions with step-by-step derivation.
     */
    public function solve(string $equation): array
    {
        $cleanEq = trim($this->redactor->redact($equation));
        if (empty($cleanEq)) {
            throw new \InvalidArgumentException('Equation cannot be empty');
        }

        // Strip any trailing comments or parameters after semicolon
        if (str_contains($cleanEq, ';')) {
            $cleanEq = trim(explode(';', $cleanEq)[0]);
        }

        if (!str_contains($cleanEq, '=')) {
            throw new \InvalidArgumentException("Equation must contain an '=' sign");
        }

        $parts = explode('=', $cleanEq);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid equation structure: multiple equals signs');
        }

        $lhs = trim($parts[0]);
        $rhs = trim($parts[1]);

        // Check if quadratic: contains ^2 or x^2
        if (preg_match('/x\s*\^\s*2/i', $cleanEq)) {
            return $this->solveQuadratic($lhs, $rhs, $cleanEq);
        }

        // Default to linear equation solving
        return $this->solveLinear($lhs, $rhs, $cleanEq);
    }

    /**
     * Solves a linear equation of the form ax + b = c.
     */
    private function solveLinear(string $lhs, string $rhs, string $original): array
    {
        $steps = [];
        $steps[] = "Original equation: {$original}";

        // Normalize equation into form: (a_lhs - a_rhs)x = (c_rhs - c_lhs)
        $parsedLhs = $this->parseLinearSide($lhs);
        $parsedRhs = $this->parseLinearSide($rhs);

        $a = $parsedLhs['x'] - $parsedRhs['x'];
        $b = $parsedRhs['const'] - $parsedLhs['const'];

        $steps[] = "Group x terms on LHS and constants on RHS: {$a}x = {$b}";

        if ($a == 0.0) {
            if ($b == 0.0) {
                return [
                    'equation'   => $original,
                    'type'       => 'linear',
                    'solutions'  => ['Infinite solutions (identity)'],
                    'steps'      => array_merge($steps, ['Equation is true for all real numbers x']),
                    'status'     => 'infinite',
                ];
            }
            return [
                'equation'   => $original,
                'type'       => 'linear',
                'solutions'  => ['No solution (contradiction)'],
                'steps'      => array_merge($steps, ['Contradiction: 0x cannot equal non-zero constant']),
                'status'     => 'inconsistent',
            ];
        }

        $solution = round($b / $a, 6);
        $steps[] = "Divide both sides by {$a}: x = {$b} / {$a}";
        $steps[] = "Final solution: x = {$solution}";

        return [
            'equation'   => $original,
            'type'       => 'linear',
            'solutions'  => [$solution],
            'steps'      => $steps,
            'status'     => 'solved',
        ];
    }

    /**
     * Solves a quadratic equation of the form ax^2 + bx + c = 0.
     */
    private function solveQuadratic(string $lhs, string $rhs, string $original): array
    {
        $steps = [];
        $steps[] = "Original equation: {$original}";

        // Move all terms to LHS: lhs - rhs = 0
        $parsedLhs = $this->parseQuadraticSide($lhs);
        $parsedRhs = $this->parseQuadraticSide($rhs);

        $a = $parsedLhs['x2'] - $parsedRhs['x2'];
        $b = $parsedLhs['x'] - $parsedRhs['x'];
        $c = $parsedLhs['const'] - $parsedRhs['const'];

        if ($a == 0.0) {
            // Degenerates to linear
            return $this->solveLinear($lhs, $rhs, $original);
        }

        $steps[] = "Standard form (ax^2 + bx + c = 0): ({$a})x^2 + ({$b})x + ({$c}) = 0";

        // Discriminant D = b^2 - 4ac
        $discriminant = ($b * $b) - (4 * $a * $c);
        $steps[] = "Compute discriminant: D = b^2 - 4ac = ({$b})^2 - 4*({$a})*({$c}) = {$discriminant}";

        if ($discriminant > 0) {
            $sqrtD = sqrt($discriminant);
            $x1 = round((-$b + $sqrtD) / (2 * $a), 6);
            $x2 = round((-$b - $sqrtD) / (2 * $a), 6);

            $steps[] = "D > 0: Two distinct real roots.";
            $steps[] = "x1 = (-({$b}) + {$sqrtD}) / (2*{$a}) = {$x1}";
            $steps[] = "x2 = (-({$b}) - {$sqrtD}) / (2*{$a}) = {$x2}";

            return [
                'equation'     => $original,
                'type'         => 'quadratic',
                'discriminant' => $discriminant,
                'solutions'    => [$x1, $x2],
                'steps'        => $steps,
                'status'       => 'solved',
            ];
        } elseif ($discriminant == 0.0) {
            $x = round(-$b / (2 * $a), 6);
            $steps[] = "D = 0: One repeated real root.";
            $steps[] = "x = -({$b}) / (2*{$a}) = {$x}";

            return [
                'equation'     => $original,
                'type'         => 'quadratic',
                'discriminant' => 0.0,
                'solutions'    => [$x],
                'steps'        => $steps,
                'status'       => 'solved',
            ];
        } else {
            $realPart = round(-$b / (2 * $a), 6);
            $imagPart = round(sqrt(abs($discriminant)) / (2 * $a), 6);

            $steps[] = "D < 0: Complex conjugate roots.";
            $steps[] = "x1 = {$realPart} + {$imagPart}i";
            $steps[] = "x2 = {$realPart} - {$imagPart}i";

            return [
                'equation'     => $original,
                'type'         => 'quadratic',
                'discriminant' => $discriminant,
                'solutions'    => ["{$realPart} + {$imagPart}i", "{$realPart} - {$imagPart}i"],
                'steps'        => $steps,
                'status'       => 'complex_roots',
            ];
        }
    }

    /**
     * Parses linear expression side into x coefficient and constant.
     */
    private function parseLinearSide(string $expr): array
    {
        $x = 0.0;
        $const = 0.0;

        $terms = $this->tokenizeExpression($expr);
        foreach ($terms as $term) {
            if (str_contains($term, 'x')) {
                $coefStr = str_replace(['x', ' '], '', $term);
                if ($coefStr === '' || $coefStr === '+') {
                    $x += 1.0;
                } elseif ($coefStr === '-') {
                    $x -= 1.0;
                } else {
                    $x += (float)$coefStr;
                }
            } else {
                $const += (float)str_replace(' ', '', $term);
            }
        }

        return ['x' => $x, 'const' => $const];
    }

    /**
     * Parses quadratic expression side into x^2, x, and constant coefficients.
     */
    private function parseQuadraticSide(string $expr): array
    {
        $x2 = 0.0;
        $x = 0.0;
        $const = 0.0;

        $terms = $this->tokenizeExpression($expr);
        foreach ($terms as $term) {
            if (preg_match('/x\s*\^\s*2/i', $term)) {
                $coefStr = preg_replace('/x\s*\^\s*2/i', '', $term);
                $coefStr = str_replace(' ', '', $coefStr);
                if ($coefStr === '' || $coefStr === '+') {
                    $x2 += 1.0;
                } elseif ($coefStr === '-') {
                    $x2 -= 1.0;
                } else {
                    $x2 += (float)$coefStr;
                }
            } elseif (str_contains($term, 'x')) {
                $coefStr = str_replace(['x', ' '], '', $term);
                if ($coefStr === '' || $coefStr === '+') {
                    $x += 1.0;
                } elseif ($coefStr === '-') {
                    $x -= 1.0;
                } else {
                    $x += (float)$coefStr;
                }
            } else {
                $const += (float)str_replace(' ', '', $term);
            }
        }

        return ['x2' => $x2, 'x' => $x, 'const' => $const];
    }

    /**
     * Splits expression into signed terms without using regex eval.
     */
    private function tokenizeExpression(string $expr): array
    {
        $normalized = str_replace(['-', '+'], ['+-', '+'], trim($expr));
        $rawTerms = explode('+', $normalized);
        $terms = [];

        foreach ($rawTerms as $t) {
            $trimmed = trim($t);
            if ($trimmed !== '') {
                $terms[] = $trimmed;
            }
        }

        return $terms;
    }

    /**
     * Simplifies arithmetic expression.
     */
    public function simplify(string $expression): string
    {
        $parsed = $this->parseQuadraticSide($expression);
        $parts = [];

        if ($parsed['x2'] != 0.0) {
            $parts[] = ($parsed['x2'] == 1.0 ? '' : ($parsed['x2'] == -1.0 ? '-' : $parsed['x2'])) . 'x^2';
        }
        if ($parsed['x'] != 0.0) {
            $prefix = ($parsed['x'] > 0 && !empty($parts)) ? '+ ' : '';
            $val = ($parsed['x'] == 1.0 ? 'x' : ($parsed['x'] == -1.0 ? '-x' : $parsed['x'] . 'x'));
            $parts[] = $prefix . $val;
        }
        if ($parsed['const'] != 0.0 || empty($parts)) {
            $prefix = ($parsed['const'] > 0 && !empty($parts)) ? '+ ' : '';
            $parts[] = $prefix . $parsed['const'];
        }

        return implode(' ', $parts);
    }
}
