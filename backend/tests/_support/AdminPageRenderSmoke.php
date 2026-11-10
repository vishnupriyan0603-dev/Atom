<?php

declare(strict_types=1);

use Firebase\JWT\JWT;

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

// Use an isolated account fixture for the session gate; never query or write
// the configured application's users table during the render smoke test.
if (extension_loaded('pdo_sqlite')) {
    $adminAuthTestPdo = new PDO('sqlite::memory:');
    $adminAuthTestPdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    $adminAuthTestPdo->exec("INSERT INTO users (id, name, email) VALUES (20, 'Vichu', 'vichu@atom.local')");
    $GLOBALS['atomAdminAuthTestPdo'] = $adminAuthTestPdo;
}

$relativePath = str_replace('\\', '/', (string) ($argv[1] ?? ''));
$unauthenticatedProbe = in_array('--unauth', $argv, true);
$target = realpath($repositoryRoot . '/' . $relativePath);
$adminRoot = realpath($repositoryRoot . '/frontend/web/admin');
$chatPage = realpath($repositoryRoot . '/frontend/web/chat.php');
$loginPage = realpath($repositoryRoot . '/frontend/web/login.php');
$withinAdmin = $target !== false && $adminRoot !== false && str_starts_with($target, $adminRoot . DIRECTORY_SEPARATOR);
if (!$withinAdmin && $target !== $chatPage && $target !== $loginPage) {
    fwrite(STDERR, "Provide an existing Admin PHP page, Web login page, or Chat page.\n");
    exit(2);
}

$secret = (string) \Atom\Config\Config::get('JWT_SECRET', '');
if ($secret === '' || $secret === 'atom-assistant-jwt-secret-key-change-in-production') {
    fwrite(STDERR, "A non-default JWT secret is required for the isolated render test.\n");
    exit(2);
}

$token = JWT::encode([
    'iss' => 'atom-assistant',
    'iat' => time(),
    'exp' => time() + 300,
    'user_id' => 20,
], $secret, 'HS256');

$urlPath = '/my%20work/Atom/' . $relativePath;
$_SERVER = array_merge($_SERVER, [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => $urlPath,
    'SCRIPT_NAME' => $urlPath,
    'SERVER_NAME' => 'localhost',
    'HTTP_HOST' => 'localhost',
    'SERVER_PORT' => '80',
    'HTTPS' => 'off',
]);
$_GET = [];
$_POST = [];
$_FILES = [];
if ($unauthenticatedProbe) {
    unset($_COOKIE[\Atom\Auth\AdminWebAccess::COOKIE_NAME]);
} else {
    $_COOKIE[\Atom\Auth\AdminWebAccess::COOKIE_NAME] = $token;
}

$issues = [];
set_error_handler(static function (int $severity) use (&$issues): bool {
    $issues[] = $severity;
    return true;
});

ob_start();
if ($unauthenticatedProbe) {
    register_shutdown_function(static function (): void {
        echo "\nGUARD_HTTP_STATUS=" . (http_response_code() ?: 200);
    });
}
try {
    require $target;
    $html = (string) ob_get_clean();
    $title = '';
    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $matches) === 1) {
        $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    preg_match_all('~<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)</script\s*>~is', $html, $scriptMatches);
    $inlineScripts = $scriptMatches[1] ?? [];
    $jsSyntaxOk = null;
    if ($inlineScripts !== [] && function_exists('proc_open')) {
        $pipes = [];
        try {
            $process = proc_open(
                ['node', '--check', '-'],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                $repositoryRoot,
                null,
                ['bypass_shell' => true]
            );
            if (is_resource($process)) {
                fwrite($pipes[0], implode("\n;\n", $inlineScripts));
                fclose($pipes[0]);
                stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $jsSyntaxOk = proc_close($process) === 0;
            }
        } catch (Throwable) {
            $jsSyntaxOk = false;
        }
    }

    echo json_encode([
        'ok' => $issues === [] && $html !== '',
        'relative_path' => $relativePath,
        'title' => $title,
        'rendered_bytes' => strlen($html),
        'php_issue_count' => count($issues),
        'php_issue_levels' => array_values(array_unique($issues)),
        'inline_script_count' => count($inlineScripts),
        'inline_js_syntax_ok' => $jsSyntaxOk,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $error) {
    ob_end_clean();
    echo json_encode([
        'ok' => false,
        'relative_path' => $relativePath,
        'error_type' => get_class($error),
        'error_message' => substr($error->getMessage(), 0, 180),
        'error_file' => str_replace($repositoryRoot, '.', $error->getFile()),
        'error_line' => $error->getLine(),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
} finally {
    restore_error_handler();
}
