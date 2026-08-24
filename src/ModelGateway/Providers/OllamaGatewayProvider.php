<?php

namespace Atom\ModelGateway\Providers;

use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;

class OllamaGatewayProvider extends AbstractGatewayProvider
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'http://localhost:11434')
    {
        parent::__construct('ollama', new ProviderCapabilities(
            streaming: true,
            tools: true,
            vision: true,
            embeddings: true,
            structuredOutput: true,
            reasoning: false
        ));
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function chat(AtomGatewayRequest $request): AtomGatewayResponse
    {
        $startTime = microtime(true);
        $payload = [
            'model'    => $request->model,
            'messages' => $request->messages,
            'stream'   => false,
            'options'  => [
                'temperature' => $request->temperature,
                'top_p'       => $request->topP,
            ]
        ];

        $ch = curl_init("{$this->baseUrl}/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $res = curl_exec($ch);
        $latencyMs = (int)((microtime(true) - $startTime) * 1000);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return AtomGatewayResponse::error("Ollama connection failed: {$err}", 'ollama', $request->model);
        }
        curl_close($ch);

        $json = json_decode($res, true);
        if (isset($json['message']['content'])) {
            return AtomGatewayResponse::success(
                content: $json['message']['content'],
                provider: 'ollama',
                model: $request->model,
                tokensUsed: (int)($json['eval_count'] ?? 0),
                latencyMs: $latencyMs,
                rawResponse: $json
            );
        }

        return AtomGatewayResponse::error("Invalid Ollama response: {$res}", 'ollama', $request->model);
    }

    public function stream(AtomGatewayRequest $request, callable $callback): void
    {
        $payload = [
            'model'    => $request->model,
            'messages' => $request->messages,
            'stream'   => true,
        ];

        $ch = curl_init("{$this->baseUrl}/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($callback) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $json = json_decode($line, true);
                    if (isset($json['message']['content'])) {
                        $callback($json['message']['content']);
                    }
                }
                return strlen($data);
            }
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function embeddings(string|array $input): array
    {
        $text = is_array($input) ? implode(" ", $input) : $input;
        $ch = curl_init("{$this->baseUrl}/api/embeddings");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['model' => 'nomic-embed-text', 'prompt' => $text]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return $json['embedding'] ?? [];
    }

    public function modelInfo(string $model): array
    {
        return ['provider' => 'ollama', 'model' => $model, 'type' => 'local'];
    }

    public function healthCheck(): bool
    {
        $ch = curl_init("{$this->baseUrl}/api/tags");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode === 200);
    }
}
