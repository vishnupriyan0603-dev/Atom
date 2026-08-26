<?php

namespace Atom\LLM;

class OpenAIProvider implements LLMInterface
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private int $timeout;

    public function __construct(string $apiKey, string $apiUrl, string $model, float $temperature = 0.7, int $maxTokens = 2048, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->model = $model;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
        $this->timeout = $timeout;
    }

    public function chat(array $messages): array
    {
        // One quick retry on transient failures; the ModelManager layer then
        // fails over to another provider instead of waiting for the budget.
        $maxAttempts = 2;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->sendRequest($messages);
            $lastError = $result['error'];

            // Success, or a non-transient error -> return immediately
            if ($result['success'] || ($result['http_code'] ?? 0) < 500) {
                return $result;
            }

            // Transient failure (429 rate limit / 5xx / network). Wait and retry.
            if ($attempt < $maxAttempts) {
                $wait = $this->extractRetryAfter($result['error']);
                if ($wait <= 0) {
                    $wait = 2;
                }
                $wait = min(8, $wait);
                sleep((int)ceil($wait));
            }
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $lastError ?: 'API request failed after ' . $maxAttempts . ' attempts'
        ];
    }

    /**
     * Performs a single HTTP request to the chat completions endpoint.
     */
    private function sendRequest(array $messages): array
    {
        $url = $this->apiUrl . '/chat/completions';
        
        $body = json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ]);

        $headers = [
            'Content-Type: application/json'
        ];

        // Only add authorization header if API Key is not empty (e.g. for local Ollama it can be empty)
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        // Disable SSL checks if using localhost endpoints or if env disables SSL verification
        $disableSsl = (getenv('ATOM_DISABLE_SSL_VERIFY') === 'true' || getenv('ATOM_DISABLE_SSL_VERIFY') === '1' || getenv('CURL_SSL_VERIFY') === 'false' || strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);

        $result = HttpClient::post($url, $body, $headers, $this->timeout, $disableSsl);
        $response = $result['response'];
        $httpCode = $result['http_code'];
        $error = $result['error'];

        if ($response === false) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'cURL Error: ' . $error,
                'http_code' => 0
            ];
        }

        if ($httpCode >= 400) {
            if ($httpCode === 429 || strpos($response, 'insufficient_quota') !== false || strpos($response, 'rate_limit') !== false) {
                return [
                    'success' => false,
                    'content' => '',
                    'error' => 'The AI provider is temporarily rate-limited. Please try again shortly.',
                    'http_code' => 429
                ];
            }
            return [
                'success' => false,
                'content' => '',
                'error' => 'API HTTP Error (' . $httpCode . '). Please check API configuration or connection.',
                'http_code' => $httpCode
            ];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Invalid JSON API Response: ' . json_last_error_msg(),
                'http_code' => $httpCode
            ];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        if (empty($content)) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Empty chat completion response: ' . $response,
                'http_code' => $httpCode
            ];
        }

        return [
            'success' => true,
            'content' => trim($content),
            'error'   => null,
            'http_code' => $httpCode
        ];
    }

    /**
     * Extract the suggested wait time (seconds) from a rate-limit error body,
     * e.g. Groq's "Please try again in 8.625s".
     */
    private function extractRetryAfter(string $error): float
    {
        if (preg_match('/try again in\s+([\d.]+)\s*s?/i', $error, $m)) {
            return (float)$m[1];
        }
        if (preg_match('/retry.?after:?\s*([\d.]+)/i', $error, $m)) {
            return (float)$m[1];
        }
        return 0.0;
    }

    /**
     * Lightweight availability check: hits GET /models with a short timeout.
     * Returns true when the API key is set and the endpoint responds with HTTP 200.
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $url = $this->apiUrl . '/models';
        $disableSsl = (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);

        $result = HttpClient::get($url, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ], 5, $disableSsl);

        return $result['http_code'] === 200;
    }
}
