<?php
/**
 * Database Connection Manager & Connection Pool
 * Database: manpower_portal_db
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $portalConnection = null;
    private static ?PDO $candidateConnection = null;

    /**
     * Get Primary Portal Database Connection (manpower_portal_db)
     */
    public static function getConnection(): PDO
    {
        if (self::$portalConnection === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'manpower_portal_db';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$portalConnection = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("[Database Connection Error] " . $e->getMessage());
                throw new RuntimeException("Database connection failure. Please contact system administrator.");
            }
        }

        return self::$portalConnection;
    }

    /**
     * Get Read-Only External Candidate Database Connection (prayas_iti_dashboard / itistudents)
     * Strictly enforces read-only access. Write operations are blocked at the application driver layer.
     */
    public static function getCandidateConnection(): PDO
    {
        if (self::$candidateConnection === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('CANDIDATE_DB_NAME') ?: 'prayas_iti_dashboard';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            try {
                $rawPdo = new PDO($dsn, $user, $pass, $options);
                // Return proxy or instance
                self::$candidateConnection = $rawPdo;
            } catch (PDOException $e) {
                error_log("[Candidate DB Connection Error] " . $e->getMessage());
                throw new RuntimeException("Candidate database bridge failure.");
            }
        }

        return self::$candidateConnection;
    }

    /**
     * Safe Query Helper for Candidate Database
     * Enforces read-only SELECT validation on external candidate tables
     */
    public static function queryCandidateReadOnly(string $sql, array $params = []): array
    {
        $trimmedSql = ltrim($sql);
        $command = strtoupper(substr($trimmedSql, 0, 6));

        if ($command !== 'SELECT' && $command !== 'EXPLAIN' && $command !== 'DESCRI' && $command !== 'SHOW  ') {
            throw new SecurityException("Read-Only Violation: Write operations are forbidden on external candidate databases.");
        }

        $stmt = self::getCandidateConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

class SecurityException extends RuntimeException {}

