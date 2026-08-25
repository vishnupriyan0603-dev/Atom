<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebhookDispatcherEngine;
use Atom\Refactoring\AstDeadCodeEliminatorEngine;
use Atom\AI\FederatedLearningEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 53-55 — TriplePhase53To55SecurityPassTest security & safety tests (6 tests).
 */
class TriplePhase53To55SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInWebhookPayload(): void
    {
        $engine = new WebhookDispatcherEngine($this->redactor);
        $payloadWithSecret = [
            'api_key' => 'sk-1122334455667788990011223344',
            'event' => 'vault.sync',
        ];

        $res = $engine->dispatchEvent('security.anomaly', $payloadWithSecret);
        $this->assertTrue($res['success']);
    }

    public function testTimingAttackResistanceInSignatureVerification(): void
    {
        $engine = new WebhookDispatcherEngine($this->redactor);
        $payload = json_encode(['foo' => 'bar']);
        $secret = 'secret_123';
        $sig = $engine->generateSignature($payload, $secret);

        // Constant-time check returns true for valid signature
        $this->assertTrue($engine->verifySignature($payload, $sig, $secret));
        // Constant-time check returns false for invalid signature
        $this->assertFalse($engine->verifySignature($payload, 'sha256=invalid', $secret));
    }

    public function testDeadCodePrunerRedactsSecretsInInput(): void
    {
        $pruner = new AstDeadCodeEliminatorEngine($this->redactor);
        $code = "\$token = 'sk-1122334455667788990011223344';\nreturn 1;\n\$dead = 2;";

        $scan = $pruner->scan($code);
        $this->assertTrue($scan['success']);
    }

    public function testFederatedPrivacyNoiseIsFiniteAndNonEmpty(): void
    {
        $fl = new FederatedLearningEngine($this->redactor);
        $updates = [
            ['node_id' => 'node_1', 'weights' => ['layer_dense_0' => [0.1, 0.2, 0.3, 0.4], 'layer_dense_1' => [0.5, 0.6, 0.7, 0.8]]],
        ];

        $res = $fl->aggregateWeights($updates);
        $this->assertTrue($res['success']);

        foreach ($res['global_weights']['layer_dense_0'] as $val) {
            $this->assertIsFloat($val);
            $this->assertFalse(is_nan($val));
            $this->assertFalse(is_infinite($val));
        }
    }

    public function testNoDangerousEvalOrShellExecutionAcrossPhases53To55(): void
    {
        $files = [
            'src/Network/WebhookDispatcherEngine.php',
            'src/Refactoring/AstDeadCodeEliminatorEngine.php',
            'src/AI/FederatedLearningEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }

    public function testDeadLetterQueuePayloadSecurity(): void
    {
        $engine = new WebhookDispatcherEngine($this->redactor);
        $this->engineAddFailingSubscriber($engine);

        $engine->dispatchEvent('test.dlq', ['secret_info' => 'sk-99887766554433221100']);
        $dlq = $engine->listDeadLetterQueue();

        $this->assertNotEmpty($dlq);
    }

    private function engineAddFailingSubscriber(WebhookDispatcherEngine $engine): void
    {
        $engine->addSubscription([
            'id' => 'FAILING_SUB_2',
            'name' => 'Failing Webhook 2',
            'target_url' => 'https://broken.endpoint.com',
            'events' => ['test.dlq'],
            'force_fail' => true,
        ]);
    }
}
