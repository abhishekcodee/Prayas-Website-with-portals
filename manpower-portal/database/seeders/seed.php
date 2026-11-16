<?php
/**
 * Master Seeder Orchestrator
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
    echo "Database connection error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

require_once __DIR__ . '/01_roles_permissions_seeder.php';
require_once __DIR__ . '/02_system_settings_seeder.php';
require_once __DIR__ . '/03_super_admin_seeder.php';

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Database Seeding Pipeline" . PHP_EOL;
echo "==================================================" . PHP_EOL;

seedRolesAndPermissions($pdo);
seedSystemSettings($pdo);
seedSuperAdmin($pdo);

echo PHP_EOL . "Seeding completed successfully with zero duplicates." . PHP_EOL;

