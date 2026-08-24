<?php

use PHPUnit\Framework\TestCase;
use Atom\Search\CodeVectorEmbedder;

/**
 * Phase 39 — CodeVectorEmbedder unit tests (5 tests).
 */
class CodeVectorEmbedderTest extends TestCase
{
    private CodeVectorEmbedder $embedder;

    protected function setUp(): void
    {
        $this->embedder = new CodeVectorEmbedder(64);
    }

    public function testEmbedReturnsCorrectDimension(): void
    {
        $vec = $this->embedder->embed('function calculateTotal($items) { return 0; }');

        $this->assertCount(64, $vec);
    }

    public function testEmbedVectorIsUnitNormalized(): void
    {
        $vec = $this->embedder->embed('class EncryptionManager { public function encrypt() {} }');

        $norm = 0.0;
        foreach ($vec as $val) {
            $norm += $val * $val;
        }

        $this->assertEqualsWithDelta(1.0, sqrt($norm), 0.01);
    }

    public function testEmptyTextReturnsZeroVector(): void
    {
        $vec = $this->embedder->embed('    ');

        $this->assertCount(64, $vec);
        $this->assertSame(0.0, array_sum($vec));
    }

    public function testDeterministicOutputForSameInput(): void
    {
        $vec1 = $this->embedder->embed('public function dispatchEvent()');
        $vec2 = $this->embedder->embed('public function dispatchEvent()');

        $this->assertSame($vec1, $vec2);
    }

    public function testCustomDimensionConfiguration(): void
    {
        $embedder32 = new CodeVectorEmbedder(32);
        $vec = $embedder32->embed('class CustomService');

        $this->assertCount(32, $vec);
    }
}
