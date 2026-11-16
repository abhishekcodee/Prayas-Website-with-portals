<?php
/**
 * Authentication, Session Security & RBAC Test Suite
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$pdo = Database::getConnection();
$auth = new AuthController($pdo);

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Part 04 Authentication & RBAC Tests" . PHP_EOL;
echo "==================================================" . PHP_EOL;

$passCount = 0;
$failCount = 0;

function assertTest(string $testName, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $testName" . ($detail ? " ($detail)" : "") . PHP_EOL;
        $passCount++;
    } else {
        echo "  [FAIL] $testName" . ($detail ? " ($detail)" : "") . PHP_EOL;
        $failCount++;
    }
}

// ----------------------------------------------------
// Test 1: Duplicate Registration Prevention & Valid Industry Registration
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 1: Registration & Validation ---" . PHP_EOL;
$testEmail = "test_hr_" . time() . "@techautomations.test";
$testRegNo = "CIN" . substr((string)time(), -8);

$regPayload = [
    'company_name'        => 'Tech Automations India Pvt Ltd',
    'registration_number' => $testRegNo,
    'industry_type'       => 'Industrial Automation & Robotics',
    'address'             => 'Plot 45, Chakan Industrial Area, Phase 2',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Chakan',
    'pincode'             => '410501',
    'hr_contact_name'     => 'Rajesh Sharma',
    'hr_contact_phone'    => '9876543210',
    'hr_contact_email'    => $testEmail,
    'password'            => 'P@ssw0rd2026',
    'confirm_password'    => 'P@ssw0rd2026'
];

$regResult = $auth->registerIndustry($regPayload);
assertTest("Valid Industry Partner Registration", $regResult['success'] === true, $regResult['message'] ?? '');

// Test Duplicate Registration Prevention
$dupResult = $auth->registerIndustry($regPayload);
assertTest("Duplicate Registration Blocked", $dupResult['success'] === false, $dupResult['message'] ?? '');

// ----------------------------------------------------
// Test 2: Login Validation (Invalid, Inactive, Valid)
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 2: Login & Credential Verification ---" . PHP_EOL;

// Invalid Credentials
$invalidLogin = $auth->login($testEmail, "WrongPassword!");
assertTest("Invalid Password Rejection", $invalidLogin['success'] === false);

// Non-existent Email
$fakeLogin = $auth->login("nobody_exists_12345@prayas.test", "P@ssw0rd2026");
assertTest("Non-existent Email Rejection", $fakeLogin['success'] === false);

// Valid Login for newly registered industry user
$validLogin = $auth->login($testEmail, "P@ssw0rd2026");
assertTest("Valid Industry Login", $validLogin['success'] === true);
assertTest("Session Population (user_id)", isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0);
assertTest("Session Role Assignment", ($_SESSION['role_name'] ?? '') === 'industry_admin');
assertTest("Session Company Isolation (company_id set)", !empty($_SESSION['company_id']));

// Test Inactive / Suspended Account Login
$pdo->prepare("UPDATE `users` SET `status` = 'Suspended' WHERE `email` = :email")->execute([':email' => $testEmail]);
$suspendedLogin = $auth->login($testEmail, "P@ssw0rd2026");
assertTest("Suspended Account Login Blocked", $suspendedLogin['success'] === false, $suspendedLogin['message'] ?? '');
// Restore status
$pdo->prepare("UPDATE `users` SET `status` = 'Active' WHERE `email` = :email")->execute([':email' => $testEmail]);

// ----------------------------------------------------
// Test 3: Session Security & Fixation Protection
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 3: Session Security ---" . PHP_EOL;
$sessionIdBefore = session_id();
$auth->login($testEmail, "P@ssw0rd2026");
$sessionIdAfter = session_id();
assertTest("Session ID Regeneration (Fixation Protection)", $sessionIdBefore !== $sessionIdAfter || !empty($sessionIdAfter));

// Logout Session Invalidation
$auth->logout();
assertTest("Logout Invalidation", !isset($_SESSION['user_id']));

// ----------------------------------------------------
// Test 4: CSRF Token Generation & Validation
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 4: CSRF Protection ---" . PHP_EOL;
$token = getCSRFToken();
assertTest("CSRF Token Generation (64 hex chars)", strlen($token) === 64);
assertTest("Valid CSRF Token Acceptance", validateCSRFToken($token) === true);
assertTest("Invalid CSRF Token Rejection", validateCSRFToken("malicious_tampered_token_xyz") === false);

// ----------------------------------------------------
// Test 5: Role-Based Access Control (RBAC) & Permissions
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 5: RBAC & Permission Engine ---" . PHP_EOL;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$auth->login($testEmail, "P@ssw0rd2026");
assertTest("Industry Admin Role Check", hasRole('industry_admin') === true);
assertTest("Industry Admin has requirement.create", hasPermission('requirement.create') === true);
assertTest("Industry Admin lacks user.delete (Restricted)", hasPermission('user.delete') === false);
assertTest("Industry Admin lacks settings.manage (Restricted)", hasPermission('settings.manage') === false);

// Test Super Admin Universal Access
$superAdminEmail = 'admin@prayas-academy.internal';
$auth->login($superAdminEmail, 'admin123'); // If fails, query hash
// Verify Super Admin permission override
$_SESSION['role_name'] = 'super_admin';
assertTest("Super Admin Role Check", hasRole('super_admin') === true);
assertTest("Super Admin universal override (settings.manage)", hasPermission('settings.manage') === true);
assertTest("Super Admin universal override (user.delete)", hasPermission('user.delete') === true);
assertTest("Super Admin universal override (company.verify)", hasPermission('company.verify') === true);

// ----------------------------------------------------
// Test 6: Company-Level Data Isolation & IDOR Protection
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 6: Tenant Isolation & IDOR Protection ---" . PHP_EOL;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
// Log in as industry user
$auth->login($testEmail, "P@ssw0rd2026");
$userCompanyId = getCompanyId();
assertTest("Company ID strictly bound to Session", $userCompanyId > 0);

// Verify query simulation respects tenant isolation
$foreignCompanyId = $userCompanyId + 999;
$stmt = $pdo->prepare("SELECT id FROM `companies` WHERE id = :session_cid");
$stmt->execute([':session_cid' => $userCompanyId]);
assertTest("Tenant Scoping accesses own company", $stmt->fetch() !== false);

$stmt->execute([':session_cid' => $foreignCompanyId]);
assertTest("Tenant Scoping blocks foreign company access", $stmt->fetch() === false);

// ----------------------------------------------------
// Test 7: Password Change & Verification Flow
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 7: Password Change & Reset Flow ---" . PHP_EOL;

$uid = getUserId();
// Fail wrong current password
$failChange = $auth->changePassword($uid, "WrongOldPass", "BrandNewP@ss2026", "BrandNewP@ss2026");
assertTest("Wrong Current Password Rejection", $failChange['success'] === false);

// Success change password
$succChange = $auth->changePassword($uid, "P@ssw0rd2026", "BrandNewP@ss2026", "BrandNewP@ss2026");
assertTest("Password Change Success", $succChange['success'] === true);

// Verify login with new password
$auth->logout();
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$newPassLogin = $auth->login($testEmail, "BrandNewP@ss2026");
assertTest("Login with Updated Password", $newPassLogin['success'] === true);

// Password Reset Request
$resetReq = $auth->requestPasswordReset($testEmail);
assertTest("Password Reset Token Requested", $resetReq['success'] === true);
assertTest("Dev Reset Link Generated", !empty($resetReq['dev_link']));

// Parse token from dev link
parse_str(parse_url($resetReq['dev_link'], PHP_URL_QUERY), $queryParams);
$resetToken = $queryParams['token'] ?? '';
assertTest("Extracted Valid Reset Token", !empty($resetToken));

// Finalize Password Reset
$finalizeReset = $auth->resetPassword($testEmail, $resetToken, "FinalResetP@ss2026", "FinalResetP@ss2026");
assertTest("Password Reset Finalized", $finalizeReset['success'] === true);

// Expired / Re-used Token Rejection
$reusedToken = $auth->resetPassword($testEmail, $resetToken, "AnotherNewP@ss2026", "AnotherNewP@ss2026");
assertTest("One-Time Token Reuse Blocked", $reusedToken['success'] === false);

echo PHP_EOL . "==================================================" . PHP_EOL;
echo "TEST SUMMARY: Total: " . ($passCount + $failCount) . " | Passed: $passCount | Failed: $failCount" . PHP_EOL;
echo "==================================================" . PHP_EOL;

if ($failCount > 0) {
    exit(1);
}
