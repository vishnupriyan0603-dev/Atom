<?php

namespace Atom\LLM;

/**
 * Shared HTTP transport for LLM providers.
 *
 * Uses cURL when the extension is loaded; degrades to a native
 * stream_context_create() request otherwise. Some PHP SAPIs in this project's
 * deployments (e.g. XAMPP's Apache/mod_php build) don't have curl enabled even
 * though the CLI SAPI does — without this fallback, OpenAIProvider/GeminiProvider
 * would hard-fail with "Call to undefined function curl_init()" under Apache
 * while working fine from the CLI, silently breaking every LLM call reached
 * through a web request. Single source of truth so every provider is equally
 * resilient, per the AGENTS.md cross-client parity rule.
 */
class HttpClient
{
    public static function post(string $url, string $body, array $headers, int $timeout, bool $disableSsl = false): array
    {
        return function_exists('curl_init')
            ? self::postCurl($url, $body, $headers, $timeout, $disableSsl)
            : self::postStream($url, $body, $headers, $timeout, $disableSsl);
    }

    public static function get(string $url, array $headers, int $timeout, bool $disableSsl = false): array
    {
        return function_exists('curl_init')
            ? self::getCurl($url, $headers, $timeout, $disableSsl)
            : self::getStream($url, $headers, $timeout, $disableSsl);
    }

    private static function postCurl(string $url, string $body, array $headers, int $timeout, bool $disableSsl): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        if ($disableSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Auto-retry with SSL verify disabled if OpenSSL certificate error occurs
        if ($response === false && !$disableSsl && (strpos($error, 'unable to get local issuer certificate') !== false || strpos($error, 'SSL certificate') !== false || strpos($error, 'certificate') !== false)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
        }

        curl_close($ch);

        return ['response' => $response, 'http_code' => (int)$httpCode, 'error' => $error];
    }

    private static function getCurl(string $url, array $headers, int $timeout, bool $disableSsl): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($disableSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($httpCode !== 200 && !$disableSsl && (strpos($error, 'unable to get local issuer certificate') !== false || strpos($error, 'SSL certificate') !== false || strpos($error, 'certificate') !== false)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
        }

        curl_close($ch);

        return ['response' => $response, 'http_code' => (int)$httpCode, 'error' => $error];
    }

    private static function postStream(string $url, string $body, array $headers, int $timeout, bool $disableSsl): array
    {
        $headerStr = '';
        foreach ($headers as $h) {
            $headerStr .= $h . "\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $headerStr,
                'content'       => $body,
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => !$disableSsl,
                'verify_peer_name' => !$disableSsl,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $httpCode = self::extractStatusCode($http_response_header ?? []);
        $error = $response === false ? (error_get_last()['message'] ?? 'stream request failed') : '';

        return ['response' => $response, 'http_code' => $httpCode, 'error' => $error];
    }

    private static function getStream(string $url, array $headers, int $timeout, bool $disableSsl): array
    {
        $headerStr = '';
        foreach ($headers as $h) {
            $headerStr .= $h . "\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => $headerStr,
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => !$disableSsl,
                'verify_peer_name' => !$disableSsl,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $httpCode = self::extractStatusCode($http_response_header ?? []);
        $error = $response === false ? (error_get_last()['message'] ?? 'stream request failed') : '';

        return ['response' => $response, 'http_code' => $httpCode, 'error' => $error];
    }

    private static function extractStatusCode(array $responseHeaders): int
    {
        foreach ($responseHeaders as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) {
                return (int)$m[1];
            }
        }
        return 0;
    }
}
