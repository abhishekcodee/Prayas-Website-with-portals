<?php
/**
 * PRAYAS Platform - Database Connection Manager
 * Supports MySQL/MariaDB with automatic SQLite fallback.
 */

if (!function_exists('loadEnv')) {
    function loadEnv($envPath) {
        if (!file_exists($envPath)) {
            return;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Load .env from project root
loadEnv(dirname(__DIR__) . '/.env');

class Database {
    private static ?PDO $instance = null;
    private static string $driverUsed = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $driver = getenv('DB_DRIVER') ?: 'mysql';
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'student_portal';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

        if ($driver === 'mysql') {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                self::$instance = $pdo;
                self::$driverUsed = 'mysql';
                return self::$instance;
            } catch (PDOException $e) {
                // If MySQL fails, fallback to SQLite
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite.");
            }
        }

        // SQLite connection fallback
        $sqliteRelPath = getenv('SQLITE_PATH') ?: 'database/database.sqlite';
        $sqliteFullPath = dirname(__DIR__) . '/' . ltrim($sqliteRelPath, '/\\');
        $sqliteDir = dirname($sqliteFullPath);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0777, true);
        }

        $dsn = "sqlite:" . $sqliteFullPath;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        self::$instance = $pdo;
        self::$driverUsed = 'sqlite';

        // Auto-initialize SQLite schema if database is newly created
        self::initSqliteTables(self::$instance);

        return self::$instance;
    }

    public static function getDriver(): string {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$driverUsed;
    }

    private static function initSqliteTables(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS enquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            organisation TEXT,
            role TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'new',
            notes TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            status TEXT DEFAULT 'active',
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            registration_no TEXT UNIQUE,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            mobile TEXT UNIQUE NOT NULL,
            password TEXT,
            status TEXT DEFAULT 'Active',
            current_step INTEGER DEFAULT 1,
            otp TEXT,
            otp_expiry DATETIME,
            otp_verified INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Ensure default admin exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin");
        if ((int)$stmt->fetchColumn() === 0) {
            $passHash = password_hash('admin123', PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO admin (name, email, password, role, status) VALUES (?, ?, ?, 'super_admin', 'active')");
            $ins->execute(['PRAYAS Administrator', 'admin@prayas.org', $passHash]);
        }
    }
}

// Global convenience accessor
function getDb(): PDO {
    return Database::getConnection();
}
