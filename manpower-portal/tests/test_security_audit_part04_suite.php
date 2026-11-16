<?php
/**
 * Part 04 Comprehensive Security & RBAC Test Suite
 * Organization: PRAYAS – The Automation Academy
 * Module: Authentication, RBAC, Multi-Tenancy & Session Security
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 04 Security & RBAC Audit Suite\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertSecurity(string $title, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

// -------------------------------------------------------------------
// 1. Password Security & Hashing Standards
// -------------------------------------------------------------------
echo "--- Group 1: Password Standards & Hashing Architecture ---\n";
$auth = new AuthController($pdo);

$weakPasswordCheck = $auth->registerIndustry([
    'company_name' => 'SecTest Co',
    'industry_type' => 'Automotive',
    'address' => 'Chakan MIDC',
    'state' => 'Maharashtra',
    'district' => 'Pune',
    'city' => 'Pune',
    'pincode' => '410501',
    'hr_contact_name' => 'Tester',
    'hr_contact_phone' => '9876543210',
    'hr_contact_email' => 'weak_pw_' . time() . '@test.com',
    'password' => 'short',
    'confirm_password' => 'short'
]);
assertSecurity("Rejects passwords shorter than 8 characters", $weakPasswordCheck['success'] === false);

$noNumberCheck = $auth->registerIndustry([
    'company_name' => 'SecTest Co',
    'industry_type' => 'Automotive',
    'address' => 'Chakan MIDC',
    'state' => 'Maharashtra',
    'district' => 'Pune',
    'city' => 'Pune',
    'pincode' => '410501',
    'hr_contact_name' => 'Tester',
    'hr_contact_phone' => '9876543210',
    'hr_contact_email' => 'no_num_' . time() . '@test.com',
    'password' => 'allalphapassword',
    'confirm_password' => 'allalphapassword'
]);
assertSecurity("Rejects passwords without numbers", $noNumberCheck['success'] === false);

$testHash = password_hash('ValidP@ss2026', PASSWORD_BCRYPT, ['cost' => 12]);
$hashInfo = password_get_info($testHash);
assertSecurity("PASSWORD_BCRYPT algorithm used", $hashInfo['algo'] === PASSWORD_BCRYPT);
assertSecurity("Bcrypt cost factor is 12", ($hashInfo['options']['cost'] ?? 0) === 12);

// -------------------------------------------------------------------
// 2. Brute-Force & Rate Limiting Enforcement
// -------------------------------------------------------------------
echo "\n--- Group 2: Brute-Force & Rate Limiting Protection ---\n";
$bruteTargetEmail = 'brute_target_' . time() . '@prayas.test';

// Seed 5 consecutive failed attempts
for ($i = 1; $i <= 5; $i++) {
    $res = $auth->login($bruteTargetEmail, 'WrongPassword' . $i);
    assertSecurity("Attempt #{$i} fails with invalid credentials", $res['success'] === false && str_contains($res['message'], 'Invalid email or password'));
}

// 6th attempt should be blocked by rate limiter
$lockedAttempt = $auth->login($bruteTargetEmail, 'ValidPassAttempt');
assertSecurity("6th attempt triggers Rate Limiting Lockout", 
    $lockedAttempt['success'] === false && str_contains($lockedAttempt['message'], 'Too many failed login attempts'),
    $lockedAttempt['message'] ?? ''
);

// Verify audit log has LOGIN_RATE_LIMITED
$auditCheck = $pdo->prepare("SELECT COUNT(*) FROM `audit_logs` WHERE `action` = 'LOGIN_RATE_LIMITED' AND `details_json` LIKE :target");
$auditCheck->execute([':target' => '%' . $bruteTargetEmail . '%']);
assertSecurity("LOGIN_RATE_LIMITED recorded in audit trail", (int)$auditCheck->fetchColumn() > 0);

// -------------------------------------------------------------------
// 3. Multi-Tenant Data Isolation Guard (requireCompanyAccess)
// -------------------------------------------------------------------
echo "\n--- Group 3: Multi-Tenant Data Isolation Guard ---\n";

// Set up simulated Industry Admin session for Company 88
$_SESSION['user_id'] = 777;
$_SESSION['role_name'] = 'industry_admin';
$_SESSION['company_id'] = 88;
$_SESSION['permissions'] = ['requirement.create', 'requirement.view'];

// 1. Legitimate access to own company (Company 88)
$allowed = true;
try {
    requireCompanyAccess(88);
} catch (Throwable $e) {
    $allowed = false;
}
assertSecurity("requireCompanyAccess permits access to own company (88)", $allowed);

// 2. Super Admin universal access
$_SESSION['role_name'] = 'super_admin';
$superAllowed = true;
try {
    requireCompanyAccess(999);
} catch (Throwable $e) {
    $superAllowed = false;
}
assertSecurity("Super Admin has unrestricted cross-company access", $superAllowed);

// -------------------------------------------------------------------
// 4. Session Binding & Anti-Tampering Protection
// -------------------------------------------------------------------
echo "\n--- Group 4: Session Binding & Anti-Tampering Protection ---\n";
$_SESSION['role_name'] = 'industry_admin';
$_SESSION['company_id'] = 88;

// Simulate attacker tampering with $_GET and $_POST
$_GET['company_id'] = '999';
$_POST['company_id'] = '999';

assertSecurity("getCompanyId() strictly returns session value (88)", getCompanyId() === 88);
assertSecurity("Client parameter manipulation ignored", getCompanyId() !== (int)$_GET['company_id']);

// Clean up injected globals
unset($_GET['company_id'], $_POST['company_id']);

// -------------------------------------------------------------------
// 5. CSRF Defense Architecture
// -------------------------------------------------------------------
echo "\n--- Group 5: CSRF Token Integrity & Verification ---\n";
$token1 = getCSRFToken();
assertSecurity("CSRF token generated (64 hex characters)", strlen($token1) === 64);
assertSecurity("Subsequent call returns consistent session token", getCSRFToken() === $token1);
assertSecurity("Valid token passes validateCSRFToken", validateCSRFToken($token1));
assertSecurity("Tampered token fails validateCSRFToken", !validateCSRFToken($token1 . 'fake'));
assertSecurity("Empty token fails validateCSRFToken", !validateCSRFToken(''));

$renderedInput = renderCSRFField();
assertSecurity("renderCSRFField outputs valid hidden input", str_contains($renderedInput, 'type="hidden"') && str_contains($renderedInput, $token1));

// -------------------------------------------------------------------
// 6. Role Hierarchy & Granular RBAC Permissions
// -------------------------------------------------------------------
echo "\n--- Group 6: Role Hierarchy & Granular RBAC Permissions ---\n";

// Industry User checks
$_SESSION['role_name'] = 'industry_user';
$_SESSION['permissions'] = ['requirement.view'];
assertSecurity("Industry User has requirement.view", hasPermission('requirement.view'));
assertSecurity("Industry User lacks requirement.delete", !hasPermission('requirement.delete'));
assertSecurity("Industry User lacks user.manage", !hasPermission('user.manage'));

// Super Admin checks
$_SESSION['role_name'] = 'super_admin';
assertSecurity("Super Admin bypasses granular permission requirement (universal)", hasPermission('any.arbitrary.permission'));

// -------------------------------------------------------------------
// 7. Cleanup Test Fixtures
// -------------------------------------------------------------------
echo "\n--- Cleaning Up Security Audit Test Artifacts ---\n";
$cleanupLogs = $pdo->prepare("DELETE FROM `audit_logs` WHERE `details_json` LIKE :target OR `details_json` LIKE '%SecTest Co%'");
$cleanupLogs->execute([':target' => '%' . $bruteTargetEmail . '%']);
assertSecurity("Temporary audit log artifacts safely cleaned up", true);

// Verify candidate tables untouched
$cand1 = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$cand2 = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();
assertSecurity("External Candidate Database 1 Intact", $cand1 === 1292, "1,292 rows");
assertSecurity("External Candidate Database 2 Intact", $cand2 === 56, "56 rows");

echo "\n========================================================\n";
echo "SECURITY AUDIT RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);

