<?php

use PHPUnit\Framework\TestCase;
use Atom\ModelGateway\ModelGateway;
use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;
use Atom\ModelGateway\Providers\OllamaGatewayProvider;
use Atom\ModelGateway\Providers\OpenAICompatibleGatewayProvider;
use Atom\ModelGateway\Providers\GeminiGatewayProvider;

class ModelGatewayTest extends TestCase
{
    public function testProviderCapabilitiesInitialization()
    {
        $caps = new ProviderCapabilities(
            streaming: true,
            tools: true,
            vision: false,
            embeddings: true,
            structuredOutput: true,
            reasoning: false
        );

        $this->assertTrue($caps->streaming);
        $this->assertTrue($caps->tools);
        $this->assertFalse($caps->vision);
        $this->assertTrue($caps->embeddings);
        $this->assertTrue($caps->structuredOutput);
        $this->assertFalse($caps->reasoning);
        $this->assertIsArray($caps->toArray());
    }

    public function testGatewayRequestAndResponseNormalization()
    {
        $request = AtomGatewayRequest::fromArray([
            'provider'      => 'groq',
            'model'         => 'openai/gpt-oss-120b',
            'messages'      => [['role' => 'user', 'content' => 'Hello Atom']],
            'temperature'   => 0.8,
            'max_tokens'    => 1024,
            'system_prompt' => 'You are Atom AI Assistant.',
        ]);

        $this->assertEquals('groq', $request->provider);
        $this->assertEquals('openai/gpt-oss-120b', $request->model);
        $this->assertEquals(0.8, $request->temperature);
        $this->assertEquals('You are Atom AI Assistant.', $request->systemPrompt);

        $response = AtomGatewayResponse::success(
            content: 'Hello World!',
            provider: 'groq',
            model: 'openai/gpt-oss-120b',
            tokensUsed: 15,
            latencyMs: 120
        );

        $this->assertTrue($response->success);
        $this->assertEquals('Hello World!', $response->content);
        $this->assertEquals(15, $response->tokensUsed);
        $this->assertEquals(120, $response->latencyMs);
    }

    public function testGatewayRegistrationAndFallbackRouting()
    {
        $gateway = new ModelGateway(fallbackEnabled: true, defaultFallbackProvider: 'openai');

        $ollama = new OllamaGatewayProvider('http://localhost:11434');
        $openai = new OpenAICompatibleGatewayProvider('openai', 'fake-key', 'https://api.openai.com/v1');
        $gemini = new GeminiGatewayProvider('fake-gemini-key');

        $gateway->registerProvider('ollama', $ollama);
        $gateway->registerProvider('openai', $openai);
        $gateway->registerProvider('gemini', $gemini);

        $this->assertNotNull($gateway->getProvider('ollama'));
        $this->assertNotNull($gateway->getProvider('openai'));
        $this->assertNotNull($gateway->getProvider('gemini'));

        $this->assertTrue($gateway->getCapabilities('ollama')->streaming);
        $this->assertTrue($gateway->getCapabilities('gemini')->vision);

        // Healthcheck on fake keys
        $this->assertTrue($gateway->healthCheck('openai'));
        $this->assertFalse($gateway->healthCheck('nonexistent'));
    }
}
