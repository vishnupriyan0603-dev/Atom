<?php

namespace Atom\ModelGateway\Providers;

use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;

class GeminiGatewayProvider extends AbstractGatewayProvider
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey = '', string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta')
    {
        parent::__construct('gemini', new ProviderCapabilities(
            streaming: true,
            tools: true,
            vision: true,
            embeddings: true,
            structuredOutput: true,
            reasoning: true
        ));
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function chat(AtomGatewayRequest $request): AtomGatewayResponse
    {
        $startTime = microtime(true);
        $model = $request->model ?: 'gemini-3.6-flash';
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        $contents = [];
        if (!empty($request->systemPrompt)) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => "System Instruction: " . $request->systemPrompt]]];
        }

        foreach ($request->messages as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content'] ?? '']]];
        }

        $payload = [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxTokens,
                'topP' => $request->topP,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => !getenv('ATOM_DISABLE_SSL_VERIFY'),
        ]);

        $res = curl_exec($ch);
        $latencyMs = (int)((microtime(true) - $startTime) * 1000);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return AtomGatewayResponse::error("Gemini connection failed: {$err}", 'gemini', $model);
        }
        curl_close($ch);

        $json = json_decode($res, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return AtomGatewayResponse::success(
                content: $json['candidates'][0]['content']['parts'][0]['text'],
                provider: 'gemini',
                model: $model,
                latencyMs: $latencyMs,
                rawResponse: $json
            );
        }

        $errMsg = $json['error']['message'] ?? 'Invalid Gemini response format';
        return AtomGatewayResponse::error($errMsg, 'gemini', $model);
    }

    public function stream(AtomGatewayRequest $request, callable $callback): void
    {
        $res = $this->chat($request);
        if ($res->success) {
            $callback($res->content);
        }
    }

    public function embeddings(string|array $input): array
    {
        $text = is_array($input) ? implode(" ", $input) : $input;
        $url = "{$this->baseUrl}/models/embedding-001:embedContent?key={$this->apiKey}";
        $payload = [
            'model'   => 'models/embedding-001',
            'content' => ['parts' => [['text' => $text]]],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => !getenv('ATOM_DISABLE_SSL_VERIFY'),
        ]);

        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return $json['embedding']['values'] ?? [];
    }

    public function modelInfo(string $model): array
    {
        return ['provider' => 'gemini', 'model' => $model, 'type' => 'cloud'];
    }

    public function healthCheck(): bool
    {
        return !empty($this->apiKey);
    }
}
