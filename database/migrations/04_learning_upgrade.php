<?php
/**
 * Database schema migration to upgrade learning topics and add learning history.
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

    // 1. Upgrade atom_learning_topics
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'atom_learning_topics'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS atom_learning_topics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                topic VARCHAR(255) NOT NULL UNIQUE,
                category VARCHAR(100) NULL,
                level VARCHAR(50) DEFAULT 'BEGINNER',
                score INT DEFAULT 0,
                confidence VARCHAR(50) DEFAULT 'MODERATE',
                source_count INT DEFAULT 0,
                successful_uses INT DEFAULT 0,
                failed_uses INT DEFAULT 0,
                gemini_consultations INT DEFAULT 0,
                last_learned_at TIMESTAMP NULL,
                last_used_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Created table 'atom_learning_topics'.\n";
    } else {
        // Table exists, add missing columns
        $columnsToAdd = [
            'category' => "VARCHAR(100) NULL AFTER topic",
            'score' => "INT DEFAULT 0 AFTER level",
            'confidence' => "VARCHAR(50) DEFAULT 'MODERATE' AFTER score",
            'source_count' => "INT DEFAULT 0 AFTER confidence",
            'successful_uses' => "INT DEFAULT 0 AFTER source_count",
            'failed_uses' => "INT DEFAULT 0 AFTER successful_uses",
            'gemini_consultations' => "INT DEFAULT 0 AFTER failed_uses",
            'last_learned_at' => "TIMESTAMP NULL AFTER gemini_consultations",
            'last_used_at' => "TIMESTAMP NULL AFTER last_learned_at",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER last_used_at",
            'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
        ];

        foreach ($columnsToAdd as $col => $definition) {
            $stmt = $pdo->query("SHOW COLUMNS FROM atom_learning_topics LIKE '{$col}'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE atom_learning_topics ADD COLUMN {$col} {$definition}");
                echo "Added column '{$col}' to 'atom_learning_topics'.\n";
            }
        }
    }

    // 2. Create atom_learning_history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS atom_learning_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(255) NOT NULL,
            action_text TEXT NOT NULL,
            source VARCHAR(100) NOT NULL,
            confidence VARCHAR(50) DEFAULT 'MODERATE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Ensured table 'atom_learning_history' exists.\n";

    // 3. Seed default topics if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM atom_learning_topics")->fetchColumn();
    if ($count === 0) {
        $defaultTopics = [
            ['PHP', 'Development'],
            ['MySQL', 'Development'],
            ['CodeIgniter', 'Development'],
            ['Laravel', 'Development'],
            ['JavaScript', 'Development'],
            ['React', 'Development'],
            ['Node.js', 'Development'],
            ['Linux', 'Systems'],
            ['Git', 'Systems'],
            ['System Design', 'Core CSE'],
            ['GATE CSE', 'Core CSE'],
            ['Computer Networks', 'Core CSE'],
            ['Operating Systems', 'Core CSE'],
            ['DBMS', 'Core CSE'],
            ['Algorithms', 'Core CSE']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO atom_learning_topics (topic, category, level, score) VALUES (?, ?, 'BEGINNER', 20)");
        foreach ($defaultTopics as $dt) {
            $stmt->execute([$dt[0], $dt[1]]);
        }
        echo "Seeded default learning topics.\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
