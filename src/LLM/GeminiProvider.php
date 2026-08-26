<?php

namespace Atom\LLM;

class GeminiProvider implements LLMInterface
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
        // Default model if none specified
        $this->model = $model ?: 'gemini-1.5-flash';
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
        $this->timeout = $timeout;
    }

    public function chat(array $messages): array
    {
        // Target endpoint format: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
        $url = $this->apiUrl . '/models/' . $this->model . ':generateContent';

        $contents = [];
        $systemInstruction = null;

        // Convert OpenAI-style role/content messages to Gemini contents structure
        foreach ($messages as $msg) {
            $role = strtolower($msg['role'] ?? 'user');
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemInstruction = [
                    'parts' => [
                        ['text' => $content]
                    ]
                ];
            } else {
                $geminiRole = ($role === 'assistant') ? 'model' : 'user';
                $contents[] = [
                    'role' => $geminiRole,
                    'parts' => [
                        ['text' => $content]
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature'      => $this->temperature,
                'maxOutputTokens'  => $this->maxTokens,
            ]
        ];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        $body = json_encode($payload);

        $headers = [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $this->apiKey
        ];

        // Disable SSL verification if requested or if local/XAMPP environment
        $disableSsl = (getenv('ATOM_DISABLE_SSL_VERIFY') === 'true' || getenv('ATOM_DISABLE_SSL_VERIFY') === '1' || getenv('CURL_SSL_VERIFY') === 'false' || strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);

        $result = HttpClient::post($url, $body, $headers, $this->timeout, $disableSsl);
        $response = $result['response'];
        $httpCode = $result['http_code'];
        $error = $result['error'];

        if ($response === false) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'cURL Error: ' . $error
            ];
        }

        if ($httpCode >= 400) {
            if ($httpCode === 429 || strpos($response, 'RESOURCE_EXHAUSTED') !== false || strpos($response, 'quota') !== false) {
                return [
                    'success' => false,
                    'content' => '',
                    'error' => 'The AI provider (Gemini) is temporarily rate-limited. Please try again shortly.'
                ];
            }
            return [
                'success' => false,
                'content' => '',
                'error' => 'Gemini API HTTP Error (' . $httpCode . '). Please check API configuration or connection.'
            ];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Invalid JSON Gemini Response: ' . json_last_error_msg()
            ];
        }

        // Extract response: candidates[0].content.parts[0].text
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($text)) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Empty content from Gemini response: ' . $response
            ];
        }

        return [
            'success' => true,
            'content' => trim($text),
            'error' => null
        ];
    }

    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Lightweight connection check: query model details
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . '?key=' . $this->apiKey;
        if (strpos($this->apiUrl, 'generativelanguage.googleapis.com') === false) {
            $url = $this->apiUrl . '/models/' . $this->model;
        }

        $headers = ['Content-Type: application/json'];
        if (strpos($this->apiUrl, 'generativelanguage.googleapis.com') === false) {
            $headers[] = 'X-goog-api-key: ' . $this->apiKey;
        }

        $disableSsl = (getenv('ATOM_DISABLE_SSL_VERIFY') === 'true' || getenv('ATOM_DISABLE_SSL_VERIFY') === '1' || getenv('CURL_SSL_VERIFY') === 'false' || strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);

        $result = HttpClient::get($url, $headers, 3, $disableSsl);

        return $result['http_code'] === 200;
    }
}
