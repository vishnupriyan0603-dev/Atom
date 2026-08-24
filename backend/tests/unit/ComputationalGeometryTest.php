<?php

use PHPUnit\Framework\TestCase;
use Atom\Algorithms\ComputationalGeometry;

/**
 * Phase 31 — ComputationalGeometry unit tests (5 tests).
 */
class ComputationalGeometryTest extends TestCase
{
    private ComputationalGeometry $geo;

    protected function setUp(): void
    {
        $this->geo = new ComputationalGeometry();
    }

    public function testPolygonAreaShoelace(): void
    {
        // 4x4 square -> area = 16
        $square = [
            ['x' => 0, 'y' => 0],
            ['x' => 4, 'y' => 0],
            ['x' => 4, 'y' => 4],
            ['x' => 0, 'y' => 4],
        ];

        $area = $this->geo->polygonArea($square);
        $this->assertEqualsWithDelta(16.0, $area, 0.001);
    }

    public function testPointInPolygonRayCasting(): void
    {
        $triangle = [
            ['x' => 0, 'y' => 0],
            ['x' => 6, 'y' => 0],
            ['x' => 3, 'y' => 6],
        ];

        $insidePt = ['x' => 3, 'y' => 2];
        $outsidePt = ['x' => 10, 'y' => 10];

        $this->assertTrue($this->geo->isPointInPolygon($insidePt, $triangle));
        $this->assertFalse($this->geo->isPointInPolygon($outsidePt, $triangle));
    }

    public function testDistanceMetrics(): void
    {
        $p1 = ['x' => 0, 'y' => 0];
        $p2 = ['x' => 3, 'y' => 4];

        $euclid = $this->geo->distance($p1, $p2, 'euclidean');
        $manhattan = $this->geo->distance($p1, $p2, 'manhattan');
        $chebyshev = $this->geo->distance($p1, $p2, 'chebyshev');

        $this->assertEqualsWithDelta(5.0, $euclid, 0.001);
        $this->assertEqualsWithDelta(7.0, $manhattan, 0.001);
        $this->assertEqualsWithDelta(4.0, $chebyshev, 0.001);
    }

    public function testConvexHullMonotoneChain(): void
    {
        $points = [
            ['x' => 0, 'y' => 0],
            ['x' => 5, 'y' => 0],
            ['x' => 5, 'y' => 5],
            ['x' => 0, 'y' => 5],
            ['x' => 2, 'y' => 2], // interior point
        ];

        $hull = $this->geo->convexHull($points);

        $this->assertCount(4, $hull);
    }

    public function testLineSegmentIntersection(): void
    {
        // Segment 1: (0,0) to (4,4)
        // Segment 2: (0,4) to (4,0)
        // They intersect at (2,2)
        $this->assertTrue($this->geo->doSegmentsIntersect(
            ['x' => 0, 'y' => 0],
            ['x' => 4, 'y' => 4],
            ['x' => 0, 'y' => 4],
            ['x' => 4, 'y' => 0]
        ));
    }
}
