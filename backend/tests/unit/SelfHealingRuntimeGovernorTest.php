<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\SelfHealingRuntimeGovernor;

class SelfHealingRuntimeGovernorTest extends TestCase
{
    private string $tempFile;
    private SelfHealingRuntimeGovernor $governor;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'heal_test_') . '.php';
        $this->governor = new SelfHealingRuntimeGovernor(dirname($this->tempFile));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }

    public function testHealsUndefinedArrayKeyWithNullCoalesce(): void
    {
        $code = "<?php\n\$data = [];\n\$val = \$data['missing_key'];\n";
        file_put_contents($this->tempFile, $code);

        $res = $this->governor->diagnoseAndHeal("Undefined array key 'missing_key'", $this->tempFile, 3);
        $this->assertTrue($res['success']);
        $this->assertTrue($res['verified']);

        $healed = file_get_contents($this->tempFile);
        $this->assertStringContainsString("(\$data['missing_key'] ?? null)", $healed);
    }

    public function testStubsMissingMethodSafely(): void
    {
        $code = "<?php\nclass SampleService {\n}\n";
        file_put_contents($this->tempFile, $code);

        $res = $this->governor->diagnoseAndHeal("Call to undefined method SampleService::computeMetrics()", $this->tempFile);
        $this->assertTrue($res['success']);

        $healed = file_get_contents($this->tempFile);
        $this->assertStringContainsString("public function computeMetrics", $healed);
    }

    public function testRollbackRestoresOriginalContent(): void
    {
        $code = "<?php\nclass AnotherService {\n}\n";
        file_put_contents($this->tempFile, $code);

        $this->governor->diagnoseAndHeal("Call to undefined method AnotherService::testAction()", $this->tempFile);
        $rolledBack = $this->governor->rollback($this->tempFile);

        $this->assertTrue($rolledBack);
        $this->assertEquals($code, file_get_contents($this->tempFile));
    }
}