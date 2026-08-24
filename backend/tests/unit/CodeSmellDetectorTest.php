<?php

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\CodeSmellDetector;

/**
 * Phase 35 — CodeSmellDetector unit tests (5 tests).
 */
class CodeSmellDetectorTest extends TestCase
{
    private CodeSmellDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new CodeSmellDetector();
    }

    public function testDetectHighCyclomaticComplexity(): void
    {
        $code = 'class ComplexHandler {
            public function evaluate($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k) {
                if ($a && $b) { return 1; }
                elseif ($c || $d) { return 2; }
                elseif ($e ?? $f) { return 3; }
                elseif ($g ? $h : $i) { return 4; }
                while ($j) { for ($x = 0; $x < 10; $x++) { switch($k) { case 1: break; } } }
                return 0;
            }
        }';

        $res = $this->detector->scan($code);

        $this->assertGreaterThan(10, $res['cyclomatic_complexity']);
        $this->assertNotEmpty($res['smells']);
        $this->assertSame('HIGH_CYCLOMATIC_COMPLEXITY', $res['smells'][0]['type']);
    }

    public function testDetectLongMethod(): void
    {
        $longBody = implode("\n", array_fill(0, 60, '        $x++;'));
        $code = "class BigMethod {\n    public function giant() {\n{$longBody}\n    }\n}";

        $res = $this->detector->scan($code);

        $this->assertNotEmpty($res['smells']);
        $hasLongMethod = false;
        foreach ($res['smells'] as $s) {
            if ($s['type'] === 'LONG_METHOD') {
                $hasLongMethod = true;
                break;
            }
        }
        $this->assertTrue($hasLongMethod);
    }

    public function testMaintainabilityIndexCalculation(): void
    {
        $cleanCode = "class SimpleCalculator {\n    public function add(\$a, \$b) {\n        return \$a + \$b;\n    }\n}";
        $res = $this->detector->scan($cleanCode);

        $this->assertGreaterThan(80.0, $res['maintainability_index']);
        $this->assertSame('LOW', $res['refactoring_urgency']);
    }

    public function testDeepNestingDetection(): void
    {
        $nestedCode = 'class DeepNesting {
            public function process() {
                if (true) {
                    if (true) {
                        if (true) {
                            if (true) {
                                if (true) {
                                    return 42;
                                }
                            }
                        }
                    }
                }
            }
        }';

        $res = $this->detector->scan($nestedCode);

        $hasDeepNesting = false;
        foreach ($res['smells'] as $s) {
            if ($s['type'] === 'DEEP_NESTING') {
                $hasDeepNesting = true;
                break;
            }
        }
        $this->assertTrue($hasDeepNesting);
    }

    public function testEmptyCodeReturnsZeroSmells(): void
    {
        $res = $this->detector->scan('   ');

        $this->assertSame(0, $res['total_smells']);
        $this->assertSame(100.0, $res['maintainability_index']);
        $this->assertSame('LOW', $res['refactoring_urgency']);
    }
}
