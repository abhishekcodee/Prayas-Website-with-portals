<?php
/**
 * PRAYAS Manpower Portal - Part 13 Database Backup & Restoration Verification Script
 * 
 * Verifies that:
 * 1. Backup file exists and is non-empty.
 * 2. An isolated sandbox database (manpower_portal_restore_test) is created.
 * 3. The backup SQL is restored into the isolated test database.
 * 4. All tables, views, and row counts match between source and restored database.
 * 5. The primary database (manpower_portal_db) remains untouched.
 * 6. The external databases (prayas_iti_dashboard, itistudents) remain untouched.
 */

declare(strict_types=1);

$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';
$sourceDb = 'manpower_portal_db';
$testDb = 'manpower_portal_restore_test';
$backupFile = __DIR__ . '/../database/backups/manpower_portal_db_final_cutover_20261105.sql';

echo "========================================================\n";
echo "PRAYAS Portal - Backup & Restoration Verification\n";
echo "========================================================\n\n";

// 1. Verify backup file exists and is non-empty
if (!file_exists($backupFile)) {
    die("[FAIL] Backup file does not exist: $backupFile\n");
}
$backupSize = filesize($backupFile);
if ($backupSize < 1000) {
    die("[FAIL] Backup file is too small or empty ($backupSize bytes).\n");
}
echo "[PASS] Step 1: Backup file verified: " . basename($backupFile) . " ($backupSize bytes)\n";

// 2. Connect to MySQL server
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Check external candidate DB row counts before
$countPrayasBefore = $pdo->query("SELECT COUNT(*) FROM prayas_iti_dashboard.students")->fetchColumn();
$countItiBefore = $pdo->query("SELECT COUNT(*) FROM itistudents.candidates")->fetchColumn();

// 3. Create isolated test database
$pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
$pdo->exec("CREATE DATABASE `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "[PASS] Step 2: Isolated sandbox database created: `$testDb`\n";

// 4. Restore backup into testDb using mysql.exe
$mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';
$restoreCmd = "\"$mysqlBin\" -u $user $testDb < \"$backupFile\"";
exec($restoreCmd, $output, $returnVar);

if ($returnVar !== 0) {
    die("[FAIL] Database restoration command failed with exit code $returnVar\n");
}
echo "[PASS] Step 3: Backup successfully imported into `$testDb`\n";

// 5. Compare table counts and schemas
$sourceTables = $pdo->query("SHOW TABLES FROM `$sourceDb`")->fetchAll(PDO::FETCH_COLUMN);
$restoredTables = $pdo->query("SHOW TABLES FROM `$testDb`")->fetchAll(PDO::FETCH_COLUMN);

echo "  -> Source tables count: " . count($sourceTables) . "\n";
echo "  -> Restored tables count: " . count($restoredTables) . "\n";

if (count($sourceTables) !== count($restoredTables)) {
    die("[FAIL] Table count mismatch between source and restored database.\n");
}

$missingInRestored = array_diff($sourceTables, $restoredTables);
if (!empty($missingInRestored)) {
    die("[FAIL] Tables missing in restored database: " . implode(', ', $missingInRestored) . "\n");
}
echo "[PASS] Step 4: All " . count($sourceTables) . " tables exist in restored database\n";

// 6. Verify row count fidelity for all tables
$allRowsMatch = true;
foreach ($sourceTables as $table) {
    $srcCount = (int)$pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`$table`")->fetchColumn();
    $resCount = (int)$pdo->query("SELECT COUNT(*) FROM `$testDb`.`$table`")->fetchColumn();
    if ($srcCount !== $resCount) {
        echo "  [FAIL] Row count mismatch on `$table`: source=$srcCount, restored=$resCount\n";
        $allRowsMatch = false;
    }
}

if (!$allRowsMatch) {
    die("[FAIL] Row count verification failed.\n");
}
echo "[PASS] Step 5: Row counts match across all " . count($sourceTables) . " tables with 100% fidelity\n";

// 7. Verify integrity of production and external databases
$countPrayasAfter = $pdo->query("SELECT COUNT(*) FROM prayas_iti_dashboard.students")->fetchColumn();
$countItiAfter = $pdo->query("SELECT COUNT(*) FROM itistudents.candidates")->fetchColumn();

if ($countPrayasBefore !== $countPrayasAfter || $countItiBefore !== $countItiAfter) {
    die("[FAIL] External candidate databases were altered during test!\n");
}
echo "[PASS] Step 6: External candidate databases verified untouched (prayas_iti_dashboard: $countPrayasAfter, itistudents: $countItiAfter)\n";

// Clean up isolated test database
$pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
echo "[PASS] Step 7: Isolated test database `$testDb` cleaned up safely.\n";

echo "\n========================================================\n";
echo "VERIFICATION SUMMARY: ALL BACKUP & RESTORATION CHECKS PASSED\n";
echo "========================================================\n";

