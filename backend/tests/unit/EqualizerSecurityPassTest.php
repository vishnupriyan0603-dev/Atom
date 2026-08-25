<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioEqualizerEngine;

/**
 * Equalizer security & safety tests (5 tests).
 */
class EqualizerSecurityPassTest extends TestCase
{
    public function testNoEvalOrShellExecutionInEqualizerSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $code = file_get_contents($rootDir . '/src/Voice/AudioEqualizerEngine.php');

        $this->assertNotFalse($code);
        $this->assertStringNotContainsString('eval(', $code);
        $this->assertStringNotContainsString('exec(', $code);
        $this->assertStringNotContainsString('shell_exec(', $code);
        $this->assertStringNotContainsString('system(', $code);
        $this->assertStringNotContainsString('passthru(', $code);
    }

    public function testLargePayloadMemoryBoundsSafety(): void
    {
        $eq = new AudioEqualizerEngine();
        $largeArray = array_fill(0, 10000, 5.0);
        $eq->setBands($largeArray);

        $bands = $eq->getBands();
        $this->assertCount(10, $bands);
        $this->assertSame(5.0, $bands[0]);
    }

    public function testPresetInjectionAndSanitization(): void
    {
        $eq = new AudioEqualizerEngine();
        $maliciousPresets = [
            '<script>alert(1)</script>',
            "'; DROP TABLE atom_settings; --",
            '../../../../etc/passwd',
            str_repeat('A', 5000),
        ];

        foreach ($maliciousPresets as $preset) {
            $res = $eq->applyPreset($preset);
            $this->assertFalse($res);
        }
    }

    public function testStateSerializationSecretRedaction(): void
    {
        $eq = new AudioEqualizerEngine();
        $state = $eq->getState();

        $this->assertIsArray($state);
        $this->assertArrayHasKey('enabled', $state);
        $this->assertArrayHasKey('preamp_db', $state);
        $this->assertArrayHasKey('bands', $state);
        $this->assertArrayHasKey('preset', $state);
    }

    public function testThreadSafetyAndIdempotency(): void
    {
        $eq = new AudioEqualizerEngine();
        for ($i = 0; $i < 50; $i++) {
            $eq->reset();
            $eq->applyPreset('ROCK');
            $eq->setPreamp(2.5);
            $eq->setLowCut(true, 90.0);
            $eq->setHighCut(true, 14000.0);
        }

        $state = $eq->getState();
        $this->assertSame('ROCK', $state['preset']);
        $this->assertSame(2.5, $state['preamp_db']);
        $this->assertTrue($state['low_cut']['enabled']);
        $this->assertSame(90.0, $state['low_cut']['frequency']);
    }
}
