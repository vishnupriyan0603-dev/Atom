<?php

use PHPUnit\Framework\TestCase;
use Atom\Testing\TestSynthesizer;

/**
 * Phase 29 — TestSynthesizer unit tests (5 tests).
 */
class TestSynthesizerTest extends TestCase
{
    private TestSynthesizer $synthesizer;

    protected function setUp(): void
    {
        $this->synthesizer = new TestSynthesizer();
    }

    public function testSynthesizeTestGeneratesValidTestCase(): void
    {
        $code = "class PaymentGateway { public function charge(\$amount) { return true; } }";
        $res = $this->synthesizer->synthesizeTest($code, 'PaymentGateway');

        $this->assertTrue($res['success']);
        $this->assertSame('PaymentGateway', $res['class_name']);
        $this->assertSame('PaymentGatewayTest', $res['test_class_name']);
        $this->assertStringContainsString('class PaymentGatewayTest extends TestCase', $res['test_code']);
    }

    public function testSynthesizeExtractsAllPublicMethods(): void
    {
        $code = "class UserService {\n"
            . "    public function findUser(\$id) { return []; }\n"
            . "    public function deleteUser(\$id) { return true; }\n"
            . "    private function helper() {}\n"
            . "}";

        $res = $this->synthesizer->synthesizeTest($code, 'UserService');
        $this->assertSame(2, $res['generated_methods_count']);
        $this->assertContains('findUser', $res['methods_tested']);
        $this->assertContains('deleteUser', $res['methods_tested']);
        $this->assertNotContains('helper', $res['methods_tested']);
    }

    public function testSynthesizeIncludesSetUpMethod(): void
    {
        $code = "class CacheManager { public function get(\$k) { return 1; } }";
        $res = $this->synthesizer->synthesizeTest($code, 'CacheManager');

        $this->assertStringContainsString('protected function setUp(): void', $res['test_code']);
    }

    public function testSynthesizeGeneratesAssertions(): void
    {
        $code = "class Validator { public function check(\$input) { return true; } }";
        $res = $this->synthesizer->synthesizeTest($code, 'Validator');

        $this->assertStringContainsString('$this->assertTrue(', $res['test_code']);
        $this->assertStringContainsString('$this->assertNotNull(', $res['test_code']);
    }

    public function testSynthesizeFallbackWhenNoMethodsFound(): void
    {
        $code = "class EmptyClass {}";
        $res = $this->synthesizer->synthesizeTest($code, 'EmptyClass');

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(1, $res['generated_methods_count']);
    }
}
