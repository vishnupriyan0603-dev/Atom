<?php

namespace Atom\Algorithms;

/**
 * Computational Geometry — Phase 31
 *
 * Implements fundamental geometric algorithms: polygon area (Shoelace formula),
 * point-in-polygon ray casting, Convex Hull (Monotone Chain), distance metrics,
 * and line segment intersection.
 */
class ComputationalGeometry
{
    /**
     * Calculates the area of a 2D polygon using the Shoelace formula.
     *
     * @param array $points Array of points: [['x' => 0, 'y' => 0], ...]
     * @return float Polygon area.
     */
    public function polygonArea(array $points): float
    {
        $n = count($points);
        if ($n < 3) {
            throw new \InvalidArgumentException('A polygon must have at least 3 vertices');
        }

        $area = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $p1 = $this->extractPoint($points[$i]);
            $p2 = $this->extractPoint($points[$j]);
            $area += ($p1['x'] * $p2['y']) - ($p2['x'] * $p1['y']);
        }

        return round(abs($area) / 2.0, 6);
    }

    /**
     * Tests if a point lies inside a 2D polygon using the Ray Casting algorithm.
     */
    public function isPointInPolygon(array $point, array $polygon): bool
    {
        $pt = $this->extractPoint($point);
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $pi = $this->extractPoint($polygon[$i]);
            $pj = $this->extractPoint($polygon[$j]);

            $intersect = (($pi['y'] > $pt['y']) !== ($pj['y'] > $pt['y'])) &&
                ($pt['x'] < ($pj['x'] - $pi['x']) * ($pt['y'] - $pi['y']) / ($pj['y'] - $pi['y'] + 1e-15) + $pi['x']);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Computes distances between two points using Euclidean, Manhattan, and Chebyshev metrics.
     */
    public function distance(array $p1, array $p2, string $metric = 'euclidean'): float
    {
        $pt1 = $this->extractPoint($p1);
        $pt2 = $this->extractPoint($p2);

        $dx = abs($pt1['x'] - $pt2['x']);
        $dy = abs($pt1['y'] - $pt2['y']);

        return match (strtolower($metric)) {
            'manhattan' => round($dx + $dy, 6),
            'chebyshev' => round(max($dx, $dy), 6),
            default     => round(sqrt(($dx * $dx) + ($dy * $dy)), 6), // Euclidean
        };
    }

    /**
     * Computes the 2D Convex Hull of a set of points using Andrew's Monotone Chain algorithm.
     */
    public function convexHull(array $points): array
    {
        $n = count($points);
        if ($n <= 3) {
            return $points;
        }

        $pts = [];
        foreach ($points as $p) {
            $pts[] = $this->extractPoint($p);
        }

        // Sort points lexicographically by x, then by y
        usort($pts, function ($a, $b) {
            if ($a['x'] === $b['x']) {
                return $a['y'] <=> $b['y'];
            }
            return $a['x'] <=> $b['x'];
        });

        // 2D cross product of OA and OB vectors: (A.x - O.x)*(B.y - O.y) - (A.y - O.y)*(B.x - O.x)
        $cross = function ($o, $a, $b) {
            return ($a['x'] - $o['x']) * ($b['y'] - $o['y']) - ($a['y'] - $o['y']) * ($b['x'] - $o['x']);
        };

        // Build lower hull
        $lower = [];
        foreach ($pts as $p) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }

        // Build upper hull
        $upper = [];
        for ($i = count($pts) - 1; $i >= 0; $i--) {
            $p = $pts[$i];
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }

        array_pop($lower);
        array_pop($upper);

        return array_merge($lower, $upper);
    }

    /**
     * Tests if two line segments (p1-p2 and p3-p4) intersect.
     */
    public function doSegmentsIntersect(array $p1, array $p2, array $p3, array $p4): bool
    {
        $a = $this->extractPoint($p1);
        $b = $this->extractPoint($p2);
        $c = $this->extractPoint($p3);
        $d = $this->extractPoint($p4);

        $ccw = function ($p, $q, $r) {
            return ($r['y'] - $p['y']) * ($q['x'] - $p['x']) > ($q['y'] - $p['y']) * ($r['x'] - $p['x']);
        };

        return ($ccw($a, $c, $d) !== $ccw($b, $c, $d)) && ($ccw($a, $b, $c) !== $ccw($a, $b, $d));
    }

    private function extractPoint(array $pt): array
    {
        if (isset($pt['x']) && isset($pt['y'])) {
            return ['x' => (float)$pt['x'], 'y' => (float)$pt['y']];
        }
        if (isset($pt[0]) && isset($pt[1])) {
            return ['x' => (float)$pt[0], 'y' => (float)$pt[1]];
        }
        throw new \InvalidArgumentException('Point must have x,y or 0,1 indices');
    }
}
