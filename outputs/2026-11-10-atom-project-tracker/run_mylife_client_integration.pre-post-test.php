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

$port = 20_000 + (getmypid() % 30_000);
$baseUrl = 'http://127.0.0.1:' . $port . '/api/v1';
$router = __DIR__ . '/mock_mylife_server.php';
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    dirname(__DIR__),
    null,
    ['bypass_shell' => true]
);

if (!is_resource($server)) {
    throw new RuntimeException('Could not start the local MyLife mock server.');
}

try {
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    usleep(250_000);
    $processStatus = proc_get_status($server);
    if (!$processStatus['running']) {
        $serverOutput = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        throw new RuntimeException('Local MyLife mock server could not start. ' . trim($serverOutput));
    }

    $client = new Atom\MyLife\Client\MyLifeClient($baseUrl, str_repeat('a', 64));
    $paired = $client->pair(str_repeat('a', 64));
    $assert(
        $paired->isSuccess() && ($paired->getData()['paired'] ?? false),
        'Pairing request did not pass the local server fixture (HTTP ' . $paired->getStatusCode() . '): ' . ($paired->getError() ?? 'unexpected response')
    );

    $status = $client->getDeviceStatus();
    $assert($status->isSuccess() && ($status->getData()['battery']['level'] ?? null) === 88, 'Signed device status request failed.');

    $summary = $client->getExecutiveSummary();
    $assert($summary->isSuccess() && ($summary->getData()['health']['today_steps'] ?? null) === 321, 'Signed executive summary request failed.');

    $finance = $client->getModuleData(
        Atom\MyLife\Contract\MyLifeModuleEnum::FINANCE,
        ['period' => 'month & year', 'ignored&key' => 'must-not-be-sent', 'unsupported' => new stdClass()]
    );
    $assert(
        $finance->isSuccess() && ($finance->getData()['period'] ?? null) === 'month & year',
        'Signed query encoding or filtering failed for a module request.'
    );

    fwrite(STDOUT, "MyLife transport integration passed (4 requests).\n");
} finally {
    foreach ([1, 2] as $pipeIndex) {
        if (isset($pipes[$pipeIndex]) && is_resource($pipes[$pipeIndex])) {
            fclose($pipes[$pipeIndex]);
        }
    }
    proc_terminate($server);
    proc_close($server);
}
