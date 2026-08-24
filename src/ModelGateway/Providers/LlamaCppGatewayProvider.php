<?php

namespace Atom\ModelGateway\Providers;

use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;

class LlamaCppGatewayProvider extends AbstractGatewayProvider
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'http://localhost:8080')
    {
        parent::__construct('llama.cpp', new ProviderCapabilities(
            streaming: true,
            tools: false,
            vision: false,
            embeddings: true,
            structuredOutput: false,
            reasoning: false
        ));
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function chat(AtomGatewayRequest $request): AtomGatewayResponse
    {
        $startTime = microtime(true);
        $payload = [
            'prompt'      => json_encode($request->messages),
            'temperature' => $request->temperature,
            'n_predict'   => $request->maxTokens,
            'stream'      => false,
        ];

        $ch = curl_init("{$this->baseUrl}/completion");
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
            return AtomGatewayResponse::error("llama.cpp connection failed: {$err}", 'llama.cpp', $request->model);
        }
        curl_close($ch);

        $json = json_decode($res, true);
        if (isset($json['content'])) {
            return AtomGatewayResponse::success(
                content: $json['content'],
                provider: 'llama.cpp',
                model: $request->model,
                latencyMs: $latencyMs,
                rawResponse: $json
            );
        }

        return AtomGatewayResponse::error("Invalid llama.cpp response", 'llama.cpp', $request->model);
    }

    public function stream(AtomGatewayRequest $request, callable $callback): void
    {
        $payload = [
            'prompt' => json_encode($request->messages),
            'stream' => true,
        ];

        $ch = curl_init("{$this->baseUrl}/completion");
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($callback) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'data: ')) {
                        $json = json_decode(substr($line, 6), true);
                        if (isset($json['content'])) {
                            $callback($json['content']);
                        }
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
        $ch = curl_init("{$this->baseUrl}/embedding");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['content' => $text]),
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
        return ['provider' => 'llama.cpp', 'model' => $model, 'type' => 'local'];
    }

    public function healthCheck(): bool
    {
        $ch = curl_init("{$this->baseUrl}/health");
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
