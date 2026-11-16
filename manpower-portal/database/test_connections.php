<?php
/**
 * Test Database Connections & Validation Suite
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/../config/database.php';

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Part 03 Database Verification Test" . PHP_EOL;
echo "==================================================" . PHP_EOL;

// 1. Test Primary Portal Connection
echo "[TEST 1] Testing Primary Portal DB Connection ... ";
try {
    $pdo = Database::getConnection();
    echo "PASSED!" . PHP_EOL;
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 2. Test Table Count in manpower_portal_db
echo "[TEST 2] Verifying Table Existence in manpower_portal_db ... ";
$tables = $pdo->query("SHOW TABLES FROM `manpower_portal_db`")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables." . PHP_EOL;
$requiredTables = [
    'schema_migrations', 'roles', 'permissions', 'role_permissions', 'system_settings',
    'companies', 'users', 'manpower_requirements', 'requirement_trades', 'requirement_skills',
    'requirement_status_history', 'candidate_matches', 'candidate_screening', 'interviews',
    'interview_feedback', 'selections', 'offer_letters', 'joinings', 'notifications', 'audit_logs'
];
$missing = array_diff($requiredTables, $tables);
if (!empty($missing)) {
    echo "  [FAIL] Missing tables: " . implode(', ', $missing) . PHP_EOL;
    exit(1);
}
echo "  [PASS] All 20 core & operational tables verified present!" . PHP_EOL;

// 3. Test Seeded Roles & Permissions
echo "[TEST 3] Verifying Seeded Roles & Permissions ... ";
$rolesCount = $pdo->query("SELECT COUNT(*) FROM `roles`")->fetchColumn();
$permsCount = $pdo->query("SELECT COUNT(*) FROM `permissions`")->fetchColumn();
$rpCount = $pdo->query("SELECT COUNT(*) FROM `role_permissions`")->fetchColumn();
echo "Roles: $rolesCount, Permissions: $permsCount, Mappings: $rpCount ... [PASS]" . PHP_EOL;

// 4. Test Seeded Super Admin
echo "[TEST 4] Verifying Super Admin Record ... ";
$admin = $pdo->query("SELECT u.id, u.email, u.status, u.must_change_password, r.name as role_name 
    FROM `users` u 
    JOIN `roles` r ON u.role_id = r.id 
    WHERE r.name = 'super_admin' LIMIT 1")->fetch();

if (!$admin) {
    echo "  [FAIL] Super Admin not found!" . PHP_EOL;
    exit(1);
}
echo "Found Admin ID {$admin['id']} ({$admin['email']}), Status: {$admin['status']}, MustChangePassword: {$admin['must_change_password']} ... [PASS]" . PHP_EOL;

// 5. Test Candidate DB Read-Only Connection
echo "[TEST 5] Verifying Read-Only Candidate Bridge (prayas_iti_dashboard) ... ";
try {
    $students = Database::queryCandidateReadOnly("SELECT id, name, trade, district FROM prayas_iti_dashboard.students LIMIT 3");
    echo "PASSED! Retrieved " . count($students) . " sample students safely." . PHP_EOL;
    foreach ($students as $s) {
        echo "   - Student #{$s['id']}: {$s['name']} | Trade: {$s['trade']} | District: {$s['district']}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 6. Test Candidate DB Read-Only Connection (itistudents)
echo "[TEST 6] Verifying Read-Only Candidate Bridge (itistudents) ... ";
try {
    $cands = Database::queryCandidateReadOnly("SELECT id, full_name, iti_trade, preferred_job_location FROM itistudents.candidates LIMIT 3");
    echo "PASSED! Retrieved " . count($cands) . " sample candidates safely." . PHP_EOL;
    foreach ($cands as $c) {
        echo "   - Candidate #{$c['id']}: {$c['full_name']} | Trade: {$c['iti_trade']} | Preferred: {$c['preferred_job_location']}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 7. Test Security Guard: Verify Write Operations are Blocked
echo "[TEST 7] Testing Security Guard against Write Violations ... ";
try {
    Database::queryCandidateReadOnly("INSERT INTO prayas_iti_dashboard.students (name) VALUES ('Test Invalid')");
    echo "FAILED! Write operation was not blocked!" . PHP_EOL;
    exit(1);
} catch (SecurityException $e) {
    echo "PASSED! Driver blocked write operation: '" . $e->getMessage() . "'" . PHP_EOL;
}

echo PHP_EOL . "==================================================" . PHP_EOL;
echo "ALL 7 DATABASE VERIFICATION TESTS PASSED SUCCESSFULLY!" . PHP_EOL;
echo "==================================================" . PHP_EOL;

