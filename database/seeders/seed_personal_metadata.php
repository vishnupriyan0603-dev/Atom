<?php
/**
 * Seeder script to populate owner profile and personal preferences database tables.
 */

$workspaceRoot = dirname(__DIR__, 2);

// Load env configuration manually or via Config class if available
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
    echo "Connected to database: {$dbName}\n";

    // 1. Seed/Update Owner Profile
    echo "Seeding owner profile...\n";
    $stmt = $pdo->query("SELECT id FROM atom_owner_profile LIMIT 1");
    $owner = $stmt->fetch();

    $ownerData = [
        'full_name' => 'Vishnupriyan R',
        'preferred_name' => 'Vichu',
        'atom_display_name' => 'ATOM',
        'profile_image' => '',
        'preferred_language' => 'English',
        'response_style' => 'detailed',
        'explanation_level' => 'practical, project-oriented',
        'main_technologies' => 'PHP, CodeIgniter, Laravel, MySQL, JavaScript, jQuery, CSS, Bootstrap, React, React Native CLI, Angular, Node.js, Express.js',
        'main_use_cases' => 'Software development, GATE 2028 preparation, Study guidance, Personal assistant',
        'timezone' => 'Asia/Kolkata'
    ];

    if ($owner) {
        $updateStmt = $pdo->prepare("
            UPDATE atom_owner_profile SET
                full_name = ?,
                preferred_name = ?,
                atom_display_name = ?,
                profile_image = ?,
                preferred_language = ?,
                response_style = ?,
                explanation_level = ?,
                main_technologies = ?,
                main_use_cases = ?,
                timezone = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $ownerData['full_name'],
            $ownerData['preferred_name'],
            $ownerData['atom_display_name'],
            $ownerData['profile_image'],
            $ownerData['preferred_language'],
            $ownerData['response_style'],
            $ownerData['explanation_level'],
            $ownerData['main_technologies'],
            $ownerData['main_use_cases'],
            $ownerData['timezone'],
            $owner['id']
        ]);
        echo "Updated existing owner profile.\n";
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO atom_owner_profile 
            (full_name, preferred_name, atom_display_name, profile_image, preferred_language, response_style, explanation_level, main_technologies, main_use_cases, timezone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $ownerData['full_name'],
            $ownerData['preferred_name'],
            $ownerData['atom_display_name'],
            $ownerData['profile_image'],
            $ownerData['preferred_language'],
            $ownerData['response_style'],
            $ownerData['explanation_level'],
            $ownerData['main_technologies'],
            $ownerData['main_use_cases'],
            $ownerData['timezone']
        ]);
        echo "Inserted new owner profile.\n";
    }

    // 2. Seed Personal Preferences
    echo "Seeding personal preferences...\n";
    $preferences = [
        'education' => 'BE in Computer Science and Engineering from VSB Engineering College, Karur (2020–2024)',
        'os_environment' => 'Windows 11 Home Single Language, WSL 2, Ubuntu',
        'primary_role' => 'PHP Developer / PHP Full-Stack Developer',
        'learning_goals' => 'Advanced PHP, Laravel, React, React Native CLI, Angular, Node.js, Express.js, .NET, DevOps, AI development',
        'career_goals' => 'GATE 2028 preparation, DRDO/ISRO/BARC opportunities, Freelancing, Website/Server maintenance contracts',
        'interests' => 'ARK: Survival Evolved (The Island map), Travel, Online gaming, Sour & spicy food, curd without salt',
        'favorite_colors' => 'Black, Red, Green, White',
        'explanation_preference' => 'Problem -> Explanation -> Solution -> Code -> Testing -> Improvements',
        'teaching_style' => 'practical, project-oriented approach',
        'business_software_experience' => 'CRM, Project management, Attendance, Payroll, Invoicing, Billing, Daily task reports, cron automation'
    ];

    foreach ($preferences as $key => $val) {
        $checkStmt = $pdo->prepare("SELECT id FROM atom_personal_profile WHERE project_id IS NULL AND preference_key = ?");
        $checkStmt->execute([$key]);
        $pref = $checkStmt->fetch();

        if ($pref) {
            $updatePref = $pdo->prepare("UPDATE atom_personal_profile SET preference_value = ?, source = ?, confidence = 1.0 WHERE id = ?");
            $updatePref->execute([$val, 'explicit_user_request', $pref['id']]);
        } else {
            $insertPref = $pdo->prepare("INSERT INTO atom_personal_profile (project_id, preference_key, preference_value, source, confidence) VALUES (NULL, ?, ?, ?, 1.0)");
            $insertPref->execute([$key, $val, 'explicit_user_request']);
        }
    }
    echo "Successfully seeded personal preferences.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
