<?php

declare(strict_types=1);

namespace Atom\MyLife\Client;

use Atom\MyLife\Contract\MyLifeClientInterface;
use Atom\MyLife\Contract\MyLifeModuleEnum;
use Atom\MyLife\Contract\MyLifeResponse;
use JsonException;

/** Authenticated client for the phone-hosted MyLife OS API. */
final class MyLifeClient implements MyLifeClientInterface
{
    private string $baseUrl;
    private ?string $sharedSecret;
    private string $clientId;

    public function __construct(
        string $baseUrl,
        ?string $sharedSecret = null,
        string $clientId = 'ATOM-WINDOWS',
        private readonly string $deviceName = 'ATOM Windows',
        private readonly string $fingerprint = 'atom-win-default',
        private readonly int $timeoutSeconds = 5
    ) {
        $this->baseUrl = self::validateBaseUrl($baseUrl);
        $this->sharedSecret = $sharedSecret;
        $this->clientId = $clientId;

        if ($timeoutSeconds < 1 || $timeoutSeconds > 30) {
            throw new \InvalidArgumentException('MyLife timeout must be between 1 and 30 seconds.');
        }
        if (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $clientId)) {
            throw new \InvalidArgumentException('MyLife client ID has an invalid format.');
        }
        if (trim($deviceName) === '' || strlen($deviceName) > 128 || trim($fingerprint) === '' || strlen($fingerprint) > 128) {
            throw new \InvalidArgumentException('MyLife device identity is invalid.');
        }
    }

    public static function validateBaseUrl(string $baseUrl): string
    {
        $parts = parse_url(trim($baseUrl));
        if (!is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) !== 'http'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new \InvalidArgumentException('MyLife URL must be a plain HTTP local server URL without credentials or query parameters.');
        }

        $host = strtolower($parts['host']);
        $port = (int)($parts['port'] ?? 8765);
        $path = rtrim((string)($parts['path'] ?? ''), '/');
        if ($port < 1 || $port > 65535 || !in_array($path, ['', '/api/v1'], true)) {
            throw new \InvalidArgumentException('MyLife URL must use the local API root and a valid port.');
        }

        $allowedHost = $host === 'mylife-os.local' || $host === 'localhost' || self::isPrivateIpv4($host);
        if (!$allowedHost) {
            throw new \InvalidArgumentException('MyLife connections are restricted to mDNS, loopback, or RFC1918 IPv4 hosts.');
        }

        return 'http://' . $host . ':' . $port . '/api/v1';
    }

    public function ping(): MyLifeResponse
    {
        return $this->request('GET', '/ping', [], null, false);
    }

    public function pair(string $pairingSecret, string $clientName = 'ATOM-WINDOWS'): MyLifeResponse
    {
        if (!preg_match('/^[a-fA-F0-9]{64}$/', $pairingSecret)) {
            return MyLifeResponse::failure('Pairing secret must be a 256-bit hexadecimal value.', 400);
        }
        if (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $clientName)) {
            return MyLifeResponse::failure('Client ID has an invalid format.', 400);
        }

        $this->sharedSecret = $pairingSecret;
        $this->clientId = $clientName;
        $timestamp = (string)time();
        $payload = [
            'client_id' => $clientName,
            'device_name' => $this->deviceName,
            'client_fingerprint' => $this->fingerprint,
            'timestamp' => (int)$timestamp,
            'signature' => MyLifeSigner::pairingSignature(
                $pairingSecret,
                $clientName,
                $this->deviceName,
                $this->fingerprint,
                $timestamp
            ),
        ];

        return $this->request('POST', '/pair', [], $payload, false);
    }

    public function getDeviceStatus(): MyLifeResponse
    {
        return $this->request('GET', '/status');
    }

    public function setTrackingPaused(bool $paused): MyLifeResponse
    {
        return MyLifeResponse::failure('The current phone API does not expose a tracking pause endpoint.', 501);
    }

    public function getExecutiveSummary(): MyLifeResponse
    {
        return $this->request('GET', '/modules/summary');
    }

    public function getModuleData(MyLifeModuleEnum $module, array $params = []): MyLifeResponse
    {
        $path = match ($module) {
            MyLifeModuleEnum::IDENTITY => '/identity',
            MyLifeModuleEnum::FINANCE => '/finance/summary',
            MyLifeModuleEnum::HEALTH => '/health/today',
            MyLifeModuleEnum::WELLBEING => '/wellbeing/mood',
            MyLifeModuleEnum::LOCATION => '/location/places',
            MyLifeModuleEnum::ACTIVITIES => '/activities/today',
            MyLifeModuleEnum::HABITS => '/habits',
            MyLifeModuleEnum::LEARNING => '/learning/progress',
            MyLifeModuleEnum::CAREER => '/career/summary',
            MyLifeModuleEnum::SOCIAL => '/social/contacts',
            MyLifeModuleEnum::NUTRITION => '/nutrition/today',
            MyLifeModuleEnum::DOCUMENTS => '/documents',
            MyLifeModuleEnum::AI_INSIGHTS => '/insights',
            MyLifeModuleEnum::DIGITAL => null,
        };
        if ($path === null) {
            return MyLifeResponse::failure('The current phone API does not expose digital usage data.', 501);
        }

        $query = [];
        foreach ($params as $key => $value) {
            if (is_string($key) && preg_match('/^[A-Za-z][A-Za-z0-9_]{0,31}$/', $key)
                && (is_string($value) || is_int($value) || is_bool($value))) {
                $query[$key] = $value;
            }
        }

        return $this->request('GET', $path, $query);
    }

    public function postModuleAction(MyLifeModuleEnum $module, string $action, array $payload): MyLifeResponse
    {
        $routes = [
            MyLifeModuleEnum::IDENTITY->value => ['update' => ['PUT', '/identity']],
            MyLifeModuleEnum::FINANCE->value => ['transaction' => ['POST', '/finance/transactions']],
            MyLifeModuleEnum::HEALTH->value => [
                'water' => ['POST', '/health/water'],
                'metric' => ['POST', '/health/metrics'],
            ],
            MyLifeModuleEnum::WELLBEING->value => ['mood' => ['POST', '/wellbeing/mood']],
            MyLifeModuleEnum::LOCATION->value => ['breadcrumb' => ['POST', '/location/breadcrumbs']],
            MyLifeModuleEnum::ACTIVITIES->value => ['task' => ['POST', '/activities/tasks']],
            MyLifeModuleEnum::LEARNING->value => ['study-session' => ['POST', '/learning/study-session']],
            MyLifeModuleEnum::NUTRITION->value => ['meal' => ['POST', '/nutrition/meals']],
            MyLifeModuleEnum::AI_INSIGHTS->value => ['create' => ['POST', '/insights']],
        ];

        if ($module === MyLifeModuleEnum::HABITS && $action === 'checkin') {
            $habitId = $payload['habit_id'] ?? null;
            if (!is_int($habitId) && !(is_string($habitId) && ctype_digit($habitId))) {
                return MyLifeResponse::failure('A numeric habit_id is required for check-in.', 400);
            }
            unset($payload['habit_id']);
            return $this->request('POST', '/habits/' . (int)$habitId . '/checkin', [], $payload);
        }

        $route = $routes[$module->value][$action] ?? null;
        if ($route === null) {
            return MyLifeResponse::failure('That action is not supported by the current MyLife phone API.', 501);
        }

        return $this->request($route[0], $route[1], [], $payload);
    }

    public function downloadEncryptedBackup(string $passphrase, string $destinationPath): MyLifeResponse
    {
        return MyLifeResponse::failure(
            'Encrypted backup download is not implemented by the current MyLife phone server.',
            501
        );
    }

    private function request(
        string $method,
        string $path,
        array $query = [],
        ?array $payload = null,
        bool $signed = true
    ): MyLifeResponse {
        if ($signed && !$this->hasValidSecret()) {
            return MyLifeResponse::failure('MyLife pairing secret is not configured. Set MYLIFE_SHARED_SECRET in the local .env file.', 401);
        }

        try {
            $requestUri = '/api/v1' . $path;
            if ($query !== []) {
                $requestUri .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            }
            $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $headers = [
                'Accept: application/json',
                'Connection: close',
            ];
            if ($payload !== null) {
                $headers[] = 'Content-Type: application/json';
            }
            if ($signed) {
                $timestamp = (string)time();
                $headers[] = 'X-MyLife-Client: ' . $this->clientId;
                $headers[] = 'X-MyLife-Timestamp: ' . $timestamp;
                $headers[] = 'X-MyLife-Signature: ' . MyLifeSigner::requestSignature(
                    (string)$this->sharedSecret,
                    $method,
                    $requestUri,
                    $timestamp,
                    $body
                );
            }

            $context = stream_context_create(['http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'protocol_version' => 1.1,
                'follow_location' => 0,
                'max_redirects' => 0,
            ]]);

            $warning = null;
            set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
                $warning = $message;
                return true;
            });
            $startedAt = microtime(true);
            try {
                $responseBody = file_get_contents($this->baseUrl . $path . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986)), false, $context);
                $responseHeaders = $http_response_header ?? [];
            } finally {
                restore_error_handler();
            }
            $latency = (microtime(true) - $startedAt) * 1000;

            if ($responseBody === false) {
                return MyLifeResponse::failure($warning === null ? 'MyLife server request failed.' : 'MyLife server is unreachable or timed out.', 503, $latency);
            }

            $statusCode = self::readStatusCode($responseHeaders);
            try {
                $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return MyLifeResponse::failure('MyLife server returned an invalid JSON response.', $statusCode ?: 502, $latency);
            }
            if (!is_array($decoded)) {
                return MyLifeResponse::failure('MyLife server returned an unexpected response shape.', $statusCode ?: 502, $latency);
            }
            if ($statusCode < 200 || $statusCode >= 300 || ($decoded['success'] ?? true) === false) {
                $message = is_string($decoded['error'] ?? null) ? $decoded['error'] : 'MyLife request was rejected.';
                return MyLifeResponse::failure(substr($message, 0, 300), $statusCode ?: 502, $latency);
            }

            $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;
            $device = is_string($decoded['device'] ?? null) ? $decoded['device'] : 'MyLife OS';
            return new MyLifeResponse(true, $data, null, $statusCode ?: 200, $latency, $device);
        } catch (\Throwable) {
            return MyLifeResponse::failure('MyLife request could not be created.', 400);
        }
    }

    private function hasValidSecret(): bool
    {
        return is_string($this->sharedSecret) && preg_match('/^[a-fA-F0-9]{64}$/', $this->sharedSecret) === 1;
    }

    private static function readStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int)$matches[1];
            }
        }
        return 0;
    }

    private static function isPrivateIpv4(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }
        $octets = array_map('intval', explode('.', $host));
        $first = $octets[0];
        $second = $octets[1];
        return $first === 10
            || ($first === 172 && $second >= 16 && $second <= 31)
            || ($first === 192 && $second === 168)
            || ($first === 127);
    }
}
