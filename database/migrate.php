<?php
/**
 * PRAYAS Platform - Database Migration Runner
 */

require_once dirname(__DIR__) . '/config/database.php';

echo "===========================================\n";
echo "  PRAYAS PLATFORM - DATABASE MIGRATION\n";
echo "===========================================\n";

try {
    $pdo = getDb();
    $driver = Database::getDriver();
    echo "Active DB Driver: " . strtoupper($driver) . "\n";

    if ($driver === 'mysql') {
        // Run MySQL schema
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
        echo "[SUCCESS] All MySQL tables and indexes migrated successfully!\n";
    } else {
        // SQLite already auto-initializes in Database::getConnection()
        echo "[SUCCESS] SQLite tables verified and ready!\n";
    }

    // Verify enquiries table
    $stmt = $pdo->query("SELECT COUNT(*) FROM enquiries");
    $count = $stmt->fetchColumn();
    echo "[INFO] Current enquiries recorded: $count\n";

} catch (Exception $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
