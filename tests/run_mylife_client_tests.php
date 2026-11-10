<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atom\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$secret = str_repeat('a', 64);
$timestamp = '1793912400';
$pairMessage = "ATOM-WINDOWS\nVishnu-Desktop-PC\natom-win-a89c01bf\n{$timestamp}";
$assert(
    Atom\MyLife\Client\MyLifeSigner::pairingSignature(
        $secret,
        'ATOM-WINDOWS',
        'Vishnu-Desktop-PC',
        'atom-win-a89c01bf',
        $timestamp
    ) === hash_hmac('sha256', $pairMessage, $secret),
    'Pairing proof signature does not match the documented canonical message.'
);

$requestUri = '/api/v1/finance/summary?period=month';
$requestMessage = "GET\n{$requestUri}\n{$timestamp}\n" . hash('sha256', '');
$assert(
    Atom\MyLife\Client\MyLifeSigner::requestSignature($secret, 'get', $requestUri, $timestamp, '')
        === hash_hmac('sha256', $requestMessage, $secret),
    'Request signature does not match the documented HMAC format.'
);
$assert(
    Atom\MyLife\Client\MyLifeClient::validateBaseUrl('http://mylife-os.local:8765/api/v1')
        === 'http://mylife-os.local:8765/api/v1',
    'The supported MyLife mDNS URL was rejected.'
);
$assert(
    Atom\MyLife\Client\MyLifeClient::validateBaseUrl('http://192.168.1.20:8765')
        === 'http://192.168.1.20:8765/api/v1',
    'A valid RFC1918 phone URL was rejected.'
);

foreach (['http://example.com:8765/api/v1', 'http://user:pass@mylife-os.local:8765/api/v1'] as $unsafeUrl) {
    try {
        Atom\MyLife\Client\MyLifeClient::validateBaseUrl($unsafeUrl);
        throw new RuntimeException('Unsafe MyLife URL was accepted: ' . $unsafeUrl);
    } catch (InvalidArgumentException) {
        // Expected: MyLife credentials and requests must stay on local targets.
    }
}

$client = new Atom\MyLife\Client\MyLifeClient('http://mylife-os.local:8765/api/v1');
$unpaired = $client->getDeviceStatus();
$assert(!$unpaired->isSuccess() && $unpaired->getStatusCode() === 401, 'Protected reads must require a pairing secret.');
$badPair = $client->pair('not-a-secret');
$assert(!$badPair->isSuccess() && $badPair->getStatusCode() === 400, 'Invalid pairing secrets must be rejected before network access.');

$clientWithSecret = new Atom\MyLife\Client\MyLifeClient(
    'http://mylife-os.local:8765/api/v1',
    $secret
);
$unsupported = $clientWithSecret->getModuleData(Atom\MyLife\Contract\MyLifeModuleEnum::DIGITAL);
$assert(!$unsupported->isSuccess() && $unsupported->getStatusCode() === 501, 'Unavailable phone endpoints must fail explicitly.');

fwrite(STDOUT, "MyLife client checks passed (7 assertions).\n");
