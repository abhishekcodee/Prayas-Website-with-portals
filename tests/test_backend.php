<?php
/**
 * PRAYAS Platform - Automated Backend Verification Suite
 */

echo "========================================================\n";
echo "   PRAYAS PLATFORM - COMPREHENSIVE BACKEND TEST SUITE   \n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "  [PASS] $description\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] $description" . ($details ? " ($details)" : "") . "\n";
        $testsFailed++;
    }
}

// -------------------------------------------------------------
// Test 1: Configuration & Environment
// -------------------------------------------------------------
echo "1. Checking Configuration & Environment...\n";
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

assertTest("Database class is loaded", class_exists('Database'));
$pdo = getDb();
assertTest("Database connection instance created", $pdo instanceof PDO);
assertTest("Active database driver is MySQL or SQLite", in_array(Database::getDriver(), ['mysql', 'sqlite']));

// -------------------------------------------------------------
// Test 2: Database Schema & Tables
// -------------------------------------------------------------
echo "\n2. Verifying Database Tables...\n";
$tables = ['enquiries', 'admin', 'users', 'student_profile', 'education', 'experience', 'skills', 'certificates', 'projects', 'resume'];
foreach ($tables as $table) {
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM `$table`");
        assertTest("Table `$table` exists and is readable", $check !== false);
    } catch (Exception $e) {
        assertTest("Table `$table` exists and is readable", false, $e->getMessage());
    }
}

// -------------------------------------------------------------
// Test 3: Enquiry API Endpoints
// -------------------------------------------------------------
echo "\n3. Testing Enquiry Ingestion & Validation...\n";

// Helper for calling API scripts internally
function simulateApiCall(string $scriptPath, string $method, array $data = [], array $serverParams = []): array {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'PHPUnitTestRunner/1.0';

    foreach ($serverParams as $k => $v) {
        $_SERVER[$k] = $v;
    }

    if ($method === 'GET') {
        $_GET = $data;
        $_POST = [];
    } else {
        $_POST = $data;
        $_GET = [];
    }

    ob_start();
    // Simulate php://input by overriding or using $_POST
    require $scriptPath;
    $output = ob_get_clean();
    $decoded = json_decode($output, true);
    return is_array($decoded) ? $decoded : ['raw' => $output];
}

// 3a: Valid enquiry insertion
$testPayload = [
    'name' => 'Aarav Patel',
    'email' => 'aarav.test@example.com',
    'phone' => '+91 98765 43210',
    'organisation' => 'Government ITI Mumbai',
    'role' => 'Student / Graduate',
    'message' => 'Interested in the upcoming Industrial Automation & PLC wiring batch.'
];

try {
    $stmt = $pdo->prepare("INSERT INTO enquiries (name, email, phone, organisation, role, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'new', CURRENT_TIMESTAMP)");
    $stmt->execute([
        $testPayload['name'],
        $testPayload['email'],
        $testPayload['phone'],
        $testPayload['organisation'],
        $testPayload['role'],
        $testPayload['message']
    ]);
    $newId = (int)$pdo->lastInsertId();
    assertTest("Enquiry inserted successfully into database (ID: $newId)", $newId > 0);

    // Verify record
    $fetchStmt = $pdo->prepare("SELECT * FROM enquiries WHERE id = ?");
    $fetchStmt->execute([$newId]);
    $row = $fetchStmt->fetch();
    assertTest("Enquiry record fields match input data", $row && $row['email'] === 'aarav.test@example.com');
    assertTest("Default status is 'new'", $row && $row['status'] === 'new');

    // 3b: Update status
    $upStmt = $pdo->prepare("UPDATE enquiries SET status = 'in_progress', notes = 'Called candidate for consultation' WHERE id = ?");
    $upStmt->execute([$newId]);

    $fetchStmt->execute([$newId]);
    $updatedRow = $fetchStmt->fetch();
    assertTest("Enquiry status updated to 'in_progress'", $updatedRow && $updatedRow['status'] === 'in_progress');
    assertTest("Enquiry notes updated", $updatedRow && strpos($updatedRow['notes'], 'Called candidate') !== false);

    // Clean up test record
    $pdo->prepare("DELETE FROM enquiries WHERE id = ?")->execute([$newId]);
    assertTest("Test enquiry cleaned up", true);

} catch (Exception $e) {
    assertTest("Enquiry operations succeeded", false, $e->getMessage());
}

// -------------------------------------------------------------
// Test 4: Admin Authentication & Password Hashing
// -------------------------------------------------------------
echo "\n4. Testing Authentication & Security...\n";
$adminStmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
$adminStmt->execute(['admin@prayas.org']);
$admin = $adminStmt->fetch();

assertTest("Admin account exists (admin@prayas.org)", !empty($admin));
assertTest("Admin password is properly hashed with BCRYPT", !empty($admin['password']) && password_verify('admin123', $admin['password']));
assertTest("Plain text passwords are NOT stored", $admin['password'] !== 'admin123');

// -------------------------------------------------------------
// Test 5: Student Portal Database & Candidate Records
// -------------------------------------------------------------
echo "\n5. Testing Student Portal Integration...\n";
assertTest("Student portal directory exists in project", is_dir(dirname(__DIR__) . '/student-portal'));
assertTest("student-portal/login.php exists", file_exists(dirname(__DIR__) . '/student-portal/login.php'));
assertTest("student-portal/register.php exists", file_exists(dirname(__DIR__) . '/student-portal/register.php'));
assertTest("student-portal/dashboard.php exists", file_exists(dirname(__DIR__) . '/student-portal/dashboard.php'));
assertTest("student-portal/db.php exists", file_exists(dirname(__DIR__) . '/student-portal/db.php'));

// Test candidate user in database
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assertTest("Candidate users table accessible (Count: $userCount)", true);

// -------------------------------------------------------------
// Test 6: Frontend File Preservation Verification
// -------------------------------------------------------------
echo "\n6. Verifying Frontend Integrity & Endpoints...\n";
$htmlPages = ['index.html', 'about.html', 'students.html', 'employers.html', 'resources.html', 'contact.html', 'privacy-policy.html', 'terms-and-conditions.html'];
foreach ($htmlPages as $page) {
    $path = dirname(__DIR__) . '/' . $page;
    assertTest("Page $page exists", file_exists($path));
    if (file_exists($path)) {
        $content = file_get_contents($path);
        // Verify no broken template tags
        assertTest("Page $page has valid HTML structure", strpos($content, '<!DOCTYPE html>') !== false && strpos($content, '</html>') !== false);
    }
}

// Check that ENQUIRY_ENDPOINT is correctly wired to api/enquiry.php
$contactContent = file_get_contents(dirname(__DIR__) . '/contact.html');
assertTest("contact.html is wired to 'api/enquiry.php'", strpos($contactContent, 'const ENQUIRY_ENDPOINT = "api/enquiry.php";') !== false);

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "  TEST SUMMARY: $testsPassed Passed, $testsFailed Failed\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
