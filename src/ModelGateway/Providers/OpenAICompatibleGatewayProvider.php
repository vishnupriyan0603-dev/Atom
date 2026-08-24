<?php

namespace Atom\ModelGateway\Providers;

use Atom\ModelGateway\AtomGatewayRequest;
use Atom\ModelGateway\AtomGatewayResponse;
use Atom\ModelGateway\ProviderCapabilities;

class OpenAICompatibleGatewayProvider extends AbstractGatewayProvider
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $name = 'openai', string $apiKey = '', string $baseUrl = 'https://api.openai.com/v1')
    {
        parent::__construct($name, new ProviderCapabilities(
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
        $messages = $request->messages;
        if (!empty($request->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $request->systemPrompt]);
        }

        $payload = [
            'model'       => $request->model,
            'messages'    => $messages,
            'temperature' => $request->temperature,
            'max_tokens'  => $request->maxTokens,
            'top_p'       => $request->topP,
            'stream'      => false,
        ];
        if (!empty($request->tools)) {
            $payload['tools'] = $request->tools;
        }

        $ch = curl_init("{$this->baseUrl}/chat/completions");
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->apiKey}"
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => !getenv('ATOM_DISABLE_SSL_VERIFY'),
        ]);

        $res = curl_exec($ch);
        $latencyMs = (int)((microtime(true) - $startTime) * 1000);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return AtomGatewayResponse::error("{$this->name} connection failed: {$err}", $this->name, $request->model);
        }
        curl_close($ch);

        $json = json_decode($res, true);
        if (isset($json['choices'][0]['message']['content'])) {
            $toolCalls = $json['choices'][0]['message']['tool_calls'] ?? [];
            return AtomGatewayResponse::success(
                content: $json['choices'][0]['message']['content'] ?? '',
                provider: $this->name,
                model: $request->model,
                tokensUsed: (int)($json['usage']['total_tokens'] ?? 0),
                latencyMs: $latencyMs,
                toolCalls: $toolCalls,
                rawResponse: $json
            );
        }

        $errMsg = $json['error']['message'] ?? "Invalid {$this->name} response";
        return AtomGatewayResponse::error($errMsg, $this->name, $request->model);
    }

    public function stream(AtomGatewayRequest $request, callable $callback): void
    {
        $messages = $request->messages;
        if (!empty($request->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $request->systemPrompt]);
        }

        $payload = [
            'model'    => $request->model,
            'messages' => $messages,
            'stream'   => true,
        ];

        $ch = curl_init("{$this->baseUrl}/chat/completions");
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->apiKey}"
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => !getenv('ATOM_DISABLE_SSL_VERIFY'),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($callback) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = substr($line, 6);
                        if ($jsonStr === '[DONE]') continue;
                        $json = json_decode($jsonStr, true);
                        $delta = $json['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            $callback($delta);
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
        $payload = [
            'model' => 'text-embedding-3-small',
            'input' => $input,
        ];

        $ch = curl_init("{$this->baseUrl}/embeddings");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer {$this->apiKey}"],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => !getenv('ATOM_DISABLE_SSL_VERIFY'),
        ]);

        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return $json['data'][0]['embedding'] ?? [];
    }

    public function modelInfo(string $model): array
    {
        return ['provider' => $this->name, 'model' => $model, 'type' => 'cloud'];
    }

    public function healthCheck(): bool
    {
        return !empty($this->apiKey);
    }
}
