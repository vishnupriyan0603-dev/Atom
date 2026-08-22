<?php
/**
 * Database schema migration to setup observability tables.
 */

$workspaceRoot = dirname(__DIR__, 2);

$envFile = $workspaceRoot . '/.env';
$config = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
}

$dbHost = $config['DB_HOST'] ?? 'localhost';
$dbName = $config['DB_NAME'] ?? 'atom_assistant';
$dbUser = $config['DB_USER'] ?? 'root';
$dbPass = $config['DB_PASSWORD'] ?? '';
$dbPort = $config['DB_PORT'] ?? '3306';

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Create atom_requests
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS atom_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(50) NOT NULL UNIQUE,
            user_query TEXT NOT NULL,
            intent VARCHAR(100) NULL,
            resolution_type VARCHAR(100) DEFAULT 'LOCAL',
            provider VARCHAR(100) NULL,
            rag_used TINYINT DEFAULT 0,
            confidence INT DEFAULT 0,
            duration_ms INT DEFAULT 0,
            status VARCHAR(50) DEFAULT 'SUCCESS',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created table 'atom_requests'.\n";

    // 2. Create atom_responses
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS atom_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(50) NOT NULL,
            conversation_id INT DEFAULT 0,
            final_response TEXT NOT NULL,
            provider VARCHAR(100) NULL,
            model VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_req (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created table 'atom_responses'.\n";

    // 3. Create atom_errors
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS atom_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            error_id VARCHAR(50) NOT NULL UNIQUE,
            category VARCHAR(100) NOT NULL,
            severity VARCHAR(50) DEFAULT 'ERROR',
            message TEXT NOT NULL,
            request_id VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created table 'atom_errors'.\n";

    // 4. Create atom_tool_executions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS atom_tool_executions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tool_name VARCHAR(100) NOT NULL,
            arguments TEXT NOT NULL,
            result TEXT NOT NULL,
            duration_ms INT DEFAULT 0,
            status VARCHAR(50) DEFAULT 'SUCCESS',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created table 'atom_tool_executions'.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
