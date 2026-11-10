<?php

$inputJson = file_get_contents('php://stdin');
$config = json_decode((string) $inputJson, true) ?: [];

$method = $config['method'] ?? 'POST';
$extraServer = $config['server'] ?? [];
$post = $config['post'] ?? [];
$body = $config['body'] ?? null;
$secret = $config['secret'] ?? 'test-secret';

$repositoryRoot = dirname(__DIR__, 3);
require_once $repositoryRoot . '/backend/vendor/autoload.php';
require_once $repositoryRoot . '/config/config.php';

spl_autoload_register(static function (string $class) use ($repositoryRoot): void {
    if (!str_starts_with($class, 'Atom\\')) {
        return;
    }
    $file = $repositoryRoot . '/src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

\Atom\Config\Config::load($repositoryRoot);

$testPdo = new PDO('sqlite::memory:');
$testPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
$testPdo->exec("INSERT INTO users (id, name, email) VALUES (20, 'Vichu', 'vichu@atom.local')");
$GLOBALS['atomAdminAuthTestPdo'] = $testPdo;

$_SERVER = array_merge([
    'REQUEST_METHOD' => $method,
    'REQUEST_URI' => '/my%20work/Atom/frontend/web/admin/session.php',
    'SCRIPT_NAME' => '/my%20work/Atom/frontend/web/admin/session.php',
    'SERVER_NAME' => 'localhost',
    'HTTP_HOST' => 'localhost',
    'SERVER_PORT' => '80',
    'HTTPS' => 'off',
], $extraServer);

$_POST = $post;
$_GET = [];

if ($body !== null) {
    $GLOBALS['atomAdminSessionRawBody'] = $body;
}

register_shutdown_function(static function() {
    $output = ob_get_clean();
    $status = http_response_code() ?: 200;
    echo json_encode([
        'status' => $status,
        'raw' => $output,
        'json' => json_decode((string) $output, true),
    ]);
});

ob_start();
require $repositoryRoot . '/frontend/web/admin/session.php';
