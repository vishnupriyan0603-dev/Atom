<?php

namespace Atom\PersonalModel;

class AtomLocalModel implements ModelInterface
{
    private string $endpoint;
    private string $modelName;
    private string $providerName;
    private string $apiKey;

    public function __construct(
        string $endpoint = 'http://localhost:11434/v1',
        string $modelName = 'llama3.1',
        string $providerName = 'Ollama',
        string $apiKey = ''
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->modelName = $modelName;
        $this->providerName = $providerName;
        $this->apiKey = $apiKey;
    }

    public function generate(array $messages): ModelResponse
    {
        $payload = [
            'model' => $this->modelName,
            'messages' => $messages,
            'stream' => false
        ];

        // Format endpoint: standard OpenAI completions or Ollama native API
        $url = $this->endpoint . '/chat/completions';
        if (strpos($this->endpoint, '/api/chat') !== false || (strpos($this->endpoint, '11434') !== false && strpos($this->endpoint, '/v1') === false)) {
            if (strpos($this->endpoint, '/api/chat') === false) {
                $url = $this->endpoint . '/api/chat';
            } else {
                $url = $this->endpoint;
            }
        }

        $headers = [
            'Content-Type: application/json'
        ];
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $disableSsl = (getenv('ATOM_DISABLE_SSL_VERIFY') === 'true' || getenv('ATOM_DISABLE_SSL_VERIFY') === '1' || getenv('CURL_SSL_VERIFY') === 'false' || strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);
        if ($disableSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Auto-retry with SSL verify disabled if OpenSSL certificate error occurs
        if ($response === false && (strpos($error, 'unable to get local issuer certificate') !== false || strpos($error, 'SSL certificate') !== false || strpos($error, 'certificate') !== false)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
        }

        curl_close($ch);

        if ($response === false) {
            return new ModelResponse(false, '', 'Local Model Error: ' . $error);
        }

        if ($httpCode >= 400) {
            return new ModelResponse(false, '', 'Local Model HTTP Error (' . $httpCode . '): ' . $response);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ModelResponse(false, '', 'Invalid JSON Local Model Response: ' . json_last_error_msg());
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        if (empty($content)) {
            $content = $data['message']['content'] ?? '';
        }

        if (empty($content)) {
            return new ModelResponse(false, '', 'Empty content response: ' . $response);
        }

        return new ModelResponse(
            true,
            trim($content),
            null,
            $data['usage']['prompt_tokens'] ?? null,
            $data['usage']['completion_tokens'] ?? null
        );
    }

    public function generateStream(array $messages, callable $onChunk): ModelResponse
    {
        $payload = [
            'model' => $this->modelName,
            'messages' => $messages,
            'stream' => true
        ];

        $url = $this->endpoint . '/chat/completions';
        if (strpos($this->endpoint, '/api/chat') !== false || (strpos($this->endpoint, '11434') !== false && strpos($this->endpoint, '/v1') === false)) {
            if (strpos($this->endpoint, '/api/chat') === false) {
                $url = $this->endpoint . '/api/chat';
            } else {
                $url = $this->endpoint;
            }
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $fullContent = '';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $disableSsl = (getenv('ATOM_DISABLE_SSL_VERIFY') === 'true' || getenv('ATOM_DISABLE_SSL_VERIFY') === '1' || strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);
        if ($disableSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$fullContent, $onChunk) {
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (strpos($line, 'data: ') === 0) {
                    $jsonStr = substr($line, 6);
                    if ($jsonStr === '[DONE]') continue;
                    $data = json_decode($jsonStr, true);
                    $text = $data['choices'][0]['delta']['content'] ?? '';
                    if (!empty($text)) {
                        $fullContent .= $text;
                        $onChunk($text);
                    }
                } else {
                    $data = json_decode($line, true);
                    $text = $data['message']['content'] ?? $data['choices'][0]['message']['content'] ?? '';
                    if (!empty($text)) {
                        $fullContent .= $text;
                        $onChunk($text);
                    }
                }
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 400 || !empty($error)) {
            return new ModelResponse(false, $fullContent, 'Stream error: ' . $error);
        }

        return new ModelResponse(true, trim($fullContent));
    }

    public function getName(): string
    {
        return $this->modelName;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function isAvailable(): bool
    {
        $ch = curl_init($this->endpoint . '/api/tags');
        if (strpos($this->endpoint, '/v1') !== false) {
            $ch = curl_init($this->endpoint . '/models');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 200;
    }
}
