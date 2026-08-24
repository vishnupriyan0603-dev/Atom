<?php

namespace Atom\Math;

/**
 * Statistical Analyzer — Phase 31
 *
 * Provides descriptive statistics, percentile modeling, Ordinary Least Squares
 * (OLS) linear regression, covariance, and Pearson correlation coefficients.
 */
class StatisticalAnalyzer
{
    /**
     * Computes comprehensive descriptive statistics for numerical array.
     */
    public function describe(array $data): array
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Cannot compute statistics for empty dataset');
        }

        $numericData = array_map('floatval', array_values($data));
        sort($numericData);

        $n = count($numericData);
        $sum = array_sum($numericData);
        $mean = round($sum / $n, 6);

        // Median
        $mid = (int)floor($n / 2);
        $median = ($n % 2 !== 0) ? $numericData[$mid] : round(($numericData[$mid - 1] + $numericData[$mid]) / 2, 6);

        // Variance & Standard Deviation
        $variance = 0.0;
        foreach ($numericData as $val) {
            $variance += pow($val - $mean, 2);
        }
        $sampleVariance = ($n > 1) ? round($variance / ($n - 1), 6) : 0.0;
        $stdDev = round(sqrt($sampleVariance), 6);

        // Percentiles & IQR
        $p25 = $this->percentile($numericData, 25);
        $p75 = $this->percentile($numericData, 75);
        $iqr = round($p75 - $p25, 6);

        return [
            'count'         => $n,
            'sum'           => round($sum, 6),
            'mean'          => $mean,
            'median'        => $median,
            'min'           => $numericData[0],
            'max'           => $numericData[$n - 1],
            'range'         => round($numericData[$n - 1] - $numericData[0], 6),
            'variance'      => $sampleVariance,
            'std_dev'       => $stdDev,
            'p25'           => $p25,
            'p75'           => $p75,
            'iqr'           => $iqr,
        ];
    }

    /**
     * Computes the p-th percentile of a sorted dataset.
     */
    public function percentile(array $sortedData, float $percentile): float
    {
        if (empty($sortedData)) {
            throw new \InvalidArgumentException('Dataset cannot be empty');
        }
        $n = count($sortedData);
        if ($n === 1) return (float)$sortedData[0];

        $rank = ($percentile / 100) * ($n - 1);
        $lowerIndex = (int)floor($rank);
        $upperIndex = (int)ceil($rank);
        $weight = $rank - $lowerIndex;

        return round($sortedData[$lowerIndex] + $weight * ($sortedData[$upperIndex] - $sortedData[$lowerIndex]), 6);
    }

    /**
     * Computes Ordinary Least Squares (OLS) linear regression for pairs (x, y).
     */
    public function linearRegression(array $x, array $y): array
    {
        $n = count($x);
        if ($n === 0 || count($y) !== $n) {
            throw new \InvalidArgumentException('X and Y arrays must be non-empty and of equal length');
        }
        if ($n < 2) {
            throw new \InvalidArgumentException('At least 2 data points required for linear regression');
        }

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = 0.0;
        $denominator = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $diffY = $y[$i] - $meanY;
            $numerator += ($diffX * $diffY);
            $denominator += pow($diffX, 2);
        }

        if (abs($denominator) < 1e-12) {
            throw new \InvalidArgumentException('Vertical line: undefined slope (zero variance in X)');
        }

        $slope = round($numerator / $denominator, 6);
        $intercept = round($meanY - ($slope * $meanX), 6);

        // Compute R^2
        $totalSumSquares = 0.0;
        $residualSumSquares = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $predictedY = ($slope * $x[$i]) + $intercept;
            $totalSumSquares += pow($y[$i] - $meanY, 2);
            $residualSumSquares += pow($y[$i] - $predictedY, 2);
        }

        $rSquared = ($totalSumSquares > 0) ? round(1 - ($residualSumSquares / $totalSumSquares), 6) : 1.0;

        return [
            'slope'       => $slope,
            'intercept'   => $intercept,
            'r_squared'   => $rSquared,
            'formula'     => "y = {$slope}x + {$intercept}",
            'data_points' => $n,
        ];
    }

    /**
     * Computes Pearson correlation coefficient between two variables X and Y.
     */
    public function correlation(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2 || count($y) !== $n) {
            throw new \InvalidArgumentException('X and Y must have at least 2 matching pairs');
        }

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $num = 0.0;
        $denomX = 0.0;
        $denomY = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $num += ($dx * $dy);
            $denomX += ($dx * $dx);
            $denomY += ($dy * $dy);
        }

        $denom = sqrt($denomX * $denomY);
        if (abs($denom) < 1e-12) {
            return 0.0;
        }

        return round($num / $denom, 6);
    }
}
