<?php
// Shared database connections and initialization helper for ATOM Web Frontend

$workspaceRoot = dirname(__DIR__, 2);

// PSR-4 autoloader for the Atom namespace (src/ directory)
spl_autoload_register(function ($class) use ($workspaceRoot) {
    $prefix = 'Atom\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $workspaceRoot . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (!class_exists('Atom\Config\Config')) {
    require_once $workspaceRoot . '/config/config.php';
}
\Atom\Config\Config::load($workspaceRoot);

// ── CodeIgniter Global Fallback Functions for Standalone XAMPP Serving ───────

if (!function_exists('esc')) {
    function esc($data, string $context = 'html', ?string $encoding = null): string {
        if ($data === null) return '';
        return htmlspecialchars((string)$data, ENT_QUOTES | ENT_SUBSTITUTE, $encoding ?? 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '/frontend/web');
        $base = $pos !== false ? substr($uri, 0, $pos + 13) : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string {
        return base_url($path);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return 'csrf_test_name';
    }
}

if (!function_exists('csrf_hash')) {
    function csrf_hash(): string {
        return md5('atom_csrf_hash_' . session_id());
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_test_name" value="' . csrf_hash() . '">';
    }
}

// Helpers for dynamic web URL resolution across direct XAMPP and Spark
if (!isset($getBaseUrl)) {
    $getBaseUrl = function () {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '/frontend/web');
        return $pos !== false ? substr($uri, 0, $pos + 13) : '';
    };
}

if (!isset($getAdminUrl)) {
    $getAdminUrl = function ($page = '') use ($getBaseUrl) {
        $base = $getBaseUrl();
        $cleanPage = ltrim($page, '/');
        if (empty($cleanPage)) {
            return $base . '/admin/index.php';
        }
        if (substr($cleanPage, -4) !== '.php') {
            $cleanPage .= '.php';
        }
        return $base . '/admin/' . $cleanPage;
    };
}

// Establish a database connection using the configured credentials
$dbHost = \Atom\Config\Config::get('DB_HOST', 'localhost');
$dbName = \Atom\Config\Config::get('DB_NAME', 'atom_assistant');
$dbUser = \Atom\Config\Config::get('DB_USER', 'root');
$dbPass = \Atom\Config\Config::get('DB_PASSWORD', '');
$dbPort = \Atom\Config\Config::get('DB_PORT', '3306');

$dbConnection = null;
$dbConnected = false;

try {
    $dbConnection = new \Atom\Database\Connection(
        $dbHost ?: 'localhost',
        $dbName ?: 'atom_assistant',
        $dbUser ?: 'root',
        $dbPass ?: '',
        $dbPort ?: '3306'
    );
    $dbConnected = $dbConnection->isConnected();
} catch (\Exception $e) {
    $dbConnected = false;
}

// Global stats calculation — shared with CLI `/status` and Desktop via
// Atom\Knowledge\KnowledgeHealthReport so every viewer reports the same numbers.
$stats = \Atom\Knowledge\KnowledgeHealthReport::compute($dbConnected ? $dbConnection->getPdo() : null);
$stats['active_provider'] = strtoupper(\Atom\Config\Config::get('LLM_PROVIDER', 'groq'));
$stats['active_model']    = \Atom\Config\Config::get('LLM_MODEL', 'openai/gpt-oss-120b');
