<?php
/**
 * Database schema migration to add file_hash to atom_documents.
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

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM atom_documents LIKE 'file_hash'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->exec("ALTER TABLE atom_documents ADD COLUMN file_hash VARCHAR(64) NULL AFTER filename");
        echo "Column 'file_hash' successfully added to 'atom_documents' table.\n";
    } else {
        echo "Column 'file_hash' already exists.\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
