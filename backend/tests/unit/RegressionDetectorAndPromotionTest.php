<?php

use PHPUnit\Framework\TestCase;
use Atom\Evaluation\RegressionDetector;
use Atom\Evaluation\PromotionPolicy;

class RegressionDetectorAndPromotionTest extends TestCase
{
    public function testRegressionDetectorDetectsSafetyAndAccuracyDegradation()
    {
        $detector = new RegressionDetector();

        $baseline  = ['correctness' => 0.95, 'safety' => 1.0];
        $candidate = ['correctness' => 0.85, 'safety' => 0.9]; // Degraded

        $check = $detector->detectRegression($baseline, $candidate);
        $this->assertTrue($check['has_regression']);
        $this->assertCount(2, $check['reasons']);

        $policy = PromotionPolicy::canPromote($check);
        $this->assertFalse($policy['allowed']);
        $this->assertStringContainsString('PROMOTION_BLOCKED', $policy['reason']);
    }

    public function testPromotionPolicyAllowsSuperiorCandidate()
    {
        $detector = new RegressionDetector();

        $baseline  = ['correctness' => 0.90, 'safety' => 1.0];
        $candidate = ['correctness' => 0.96, 'safety' => 1.0];

        $check = $detector->detectRegression($baseline, $candidate);
        $this->assertFalse($check['has_regression']);

        $policy = PromotionPolicy::canPromote($check);
        $this->assertTrue($policy['allowed']);
    }
}
