<?php
/**
 * Migration Runner
 * Database: manpower_portal_db
 * Organization: PRAYAS – The Automation Academy
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'manpower_portal_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Ensure schema_migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version` VARCHAR(100) NOT NULL UNIQUE,
    `migration_file` VARCHAR(255) NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$applied = $pdo->query("SELECT `version` FROM `schema_migrations`")->fetchAll(PDO::FETCH_COLUMN);

$migrationFiles = glob(__DIR__ . "/*.sql");
sort($migrationFiles);

$appliedCount = 0;

foreach ($migrationFiles as $filePath) {
    $filename = basename($filePath);
    $version = explode('_', $filename)[0];

    if (in_array($version, $applied)) {
        echo "[-] Skipping already applied: $filename" . PHP_EOL;
        continue;
    }

    echo "[+] Applying migration: $filename ... ";
    $sql = file_get_contents($filePath);

    try {
        // DDL in MySQL performs implicit commits, so execute SQL directly
        $pdo->exec($sql);
        
        $stmt = $pdo->prepare("INSERT INTO `schema_migrations` (`version`, `migration_file`) VALUES (:ver, :file)");
        $stmt->execute([':ver' => $version, ':file' => $filename]);
        
        echo "SUCCESS!" . PHP_EOL;
        $appliedCount++;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . "Migration complete! Total newly applied: $appliedCount" . PHP_EOL;
