<?php

declare(strict_types=1);

// Local-only protocol fixture used by run_mylife_client_integration.php.
$secret = str_repeat('a', 64);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$rawBody = file_get_contents('php://input') ?: '';
$timestamp = $_SERVER['HTTP_X_MYLIFE_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_MYLIFE_SIGNATURE'] ?? '';
$client = $_SERVER['HTTP_X_MYLIFE_CLIENT'] ?? '';

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
};

if ($requestUri === '/api/v1/ping' && $method === 'GET') {
    $respond(200, ['status' => 'ok', 'device' => 'MyLife Test Fixture']);
    return;
}

if ($requestUri === '/api/v1/pair' && $method === 'POST') {
    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        $respond(400, ['success' => false, 'error' => 'invalid json']);
        return;
    }
    $proofMessage = implode("\n", [
        (string)($payload['client_id'] ?? ''),
        (string)($payload['device_name'] ?? ''),
        (string)($payload['client_fingerprint'] ?? ''),
        (string)($payload['timestamp'] ?? ''),
    ]);
    $isCurrent = abs(time() - (int)($payload['timestamp'] ?? 0)) <= 60;
    $isValid = hash_equals(hash_hmac('sha256', $proofMessage, $secret), (string)($payload['signature'] ?? ''));
    if (!$isCurrent || !$isValid) {
        $respond(401, ['success' => false, 'error' => 'invalid pairing proof']);
        return;
    }
    $respond(200, ['success' => true, 'paired' => true, 'device' => 'MyLife Test Fixture']);
    return;
}

$bodyHash = hash('sha256', $rawBody);
$requestMessage = $method . "\n" . $requestUri . "\n" . $timestamp . "\n" . $bodyHash;
$isCurrent = ctype_digit((string)$timestamp) && abs(time() - (int)$timestamp) <= 60;
$isValid = $client === 'ATOM-WINDOWS'
    && $isCurrent
    && hash_equals(hash_hmac('sha256', $requestMessage, $secret), (string)$signature);
if (!$isValid) {
    $respond(401, ['success' => false, 'error' => 'invalid request signature']);
    return;
}

if ($requestUri === '/api/v1/status' && $method === 'GET') {
    $respond(200, [
        'success' => true,
        'device' => 'MyLife Test Fixture',
        'data' => ['tracking' => ['is_paused' => false], 'battery' => ['level' => 88]],
    ]);
    return;
}

if ($requestUri === '/api/v1/modules/summary' && $method === 'GET') {
    $respond(200, [
        'success' => true,
        'device' => 'MyLife Test Fixture',
        'data' => ['health' => ['today_steps' => 321]],
    ]);
    return;
}

if ($requestUri === '/api/v1/finance/summary?period=month%20%26%20year' && $method === 'GET') {
    $respond(200, [
        'success' => true,
        'device' => 'MyLife Test Fixture',
        'data' => ['period' => 'month & year'],
    ]);
    return;
}

$respond(404, ['success' => false, 'error' => 'not found']);
