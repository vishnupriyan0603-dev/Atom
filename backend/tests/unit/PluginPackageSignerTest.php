<?php

use PHPUnit\Framework\TestCase;
use Atom\Marketplace\PluginPackageSigner;

/**
 * Phase 32 — PluginPackageSigner unit tests (5 tests).
 */
class PluginPackageSignerTest extends TestCase
{
    private PluginPackageSigner $signer;

    protected function setUp(): void
    {
        $this->signer = new PluginPackageSigner();
    }

    public function testManifestSignatureGenerationAndVerification(): void
    {
        $manifest = [
            'id'           => 'sample_tool',
            'name'         => 'Sample Tool',
            'version'      => '1.0.0',
            'author'       => 'ATOM Labs',
            'permissions'  => ['allow_database'],
            'capabilities' => ['run_tool'],
        ];

        $sig = $this->signer->signManifest($manifest);
        $this->assertNotEmpty($sig);
        $this->assertTrue($this->signer->verifySignature($manifest, $sig));
    }

    public function testTamperedManifestSignatureFails(): void
    {
        $manifest = [
            'id'           => 'secure_pkg',
            'name'         => 'Secure Package',
            'version'      => '1.0.0',
            'author'       => 'Security Dev',
            'permissions'  => [],
            'capabilities' => ['check_status'],
        ];

        $sig = $this->signer->signManifest($manifest);

        // Tamper with manifest by adding an unpermitted permission
        $tampered = $manifest;
        $tampered['permissions'] = ['allow_network'];

        $this->assertFalse($this->signer->verifySignature($tampered, $sig));
    }

    public function testPayloadChecksumVerification(): void
    {
        $payload = '<?php echo "plugin code";';
        $checksum = hash('sha256', $payload);

        $this->assertTrue($this->signer->verifyChecksum($payload, $checksum));
        $this->assertFalse($this->signer->verifyChecksum('tampered code', $checksum));
    }

    public function testValidateManifestSchemaSucceedsForValidStructure(): void
    {
        $manifest = [
            'id'               => 'valid_plugin',
            'name'             => 'Valid Plugin',
            'version'          => '1.0.0',
            'author'           => 'Dev Team',
            'permissions'      => ['allow_filesystem'],
            'capabilities'     => ['read_file'],
            'min_core_version' => '1.0.0',
        ];

        $check = $this->signer->validateManifestSchema($manifest);

        $this->assertTrue($check['valid']);
        $this->assertEmpty($check['errors']);
    }

    public function testValidateManifestSchemaCatchesMissingFieldsAndInvalidId(): void
    {
        $invalid = [
            'id'      => 'bad id with spaces!',
            'version' => '1.0.0',
        ];

        $check = $this->signer->validateManifestSchema($invalid);

        $this->assertFalse($check['valid']);
        $this->assertNotEmpty($check['errors']);
    }
}
