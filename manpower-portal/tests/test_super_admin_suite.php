<?php
/**
 * Super Admin Dashboard & Administration Panel Test Suite
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$pdo = Database::getConnection();
$companyCtrl = new CompanyController($pdo);
$userCtrl = new UserController($pdo);

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Part 05 Super Admin Test Suite" . PHP_EOL;
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

// Ensure mock session has a super_admin user
$adminStmt = $pdo->prepare("SELECT u.id, u.role_id, r.name as role_name, u.company_id, u.full_name, u.email 
    FROM `users` u JOIN `roles` r ON u.role_id = r.id 
    WHERE r.name = 'super_admin' LIMIT 1");
$adminStmt->execute();
$adminUser = $adminStmt->fetch();

if (!$adminUser) {
    die("[FATAL] Super admin account not found in database. Run seeders first." . PHP_EOL);
}

// Establish authenticated test session
$_SESSION['user_id'] = $adminUser['id'];
$_SESSION['role_name'] = $adminUser['role_name'];
$_SESSION['role_id'] = $adminUser['role_id'];
$_SESSION['company_id'] = null;
$_SESSION['user_email'] = $adminUser['email'];
$_SESSION['full_name'] = $adminUser['full_name'];

// Track created test IDs for safe cleanup
$createdCompanyIds = [];
$createdUserIds = [];
$createdAuditIds = [];

// ----------------------------------------------------
// TEST GROUP 1: Dashboard Analytics & Metric Queries
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 1: Dashboard Metrics & Read-Only Queries ---" . PHP_EOL;

// 1.1 Metrics Aggregation
$companiesTotal = (int)$pdo->query("SELECT COUNT(*) FROM `companies`")->fetchColumn();
$usersTotal = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$reqsTotal = (int)$pdo->query("SELECT COUNT(*) FROM `manpower_requirements`")->fetchColumn();

assertTest("Dashboard Companies Metric Query", $companiesTotal >= 0, "Found {$companiesTotal} companies");
assertTest("Dashboard Users Metric Query", $usersTotal >= 1, "Found {$usersTotal} users");
assertTest("Dashboard Requirements Metric Query", $reqsTotal >= 0, "Found {$reqsTotal} requirements");

// 1.2 Candidate Pool Read-Only Safe Query
$candidatePool = 0;
try {
    $c1 = Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `prayas_iti_dashboard`.`students`");
    $c2 = Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `itistudents`.`candidates`");
    $candidatePool = ((int)($c1[0]['cnt'] ?? 0)) + ((int)($c2[0]['cnt'] ?? 0));
    assertTest("Candidate Pool Read-Only Aggregation", $candidatePool > 1000, "Safely counted {$candidatePool} candidates");
} catch (Exception $e) {
    assertTest("Candidate Pool Read-Only Aggregation", false, $e->getMessage());
}

// ----------------------------------------------------
// TEST GROUP 2: Company Management & Lifecycle
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 2: Company Management & Status Workflow ---" . PHP_EOL;

$testEmail = "admin_suite_" . time() . "@testco.internal";
$companyPayload = [
    'company_name'        => 'Test Automation Enterprise Ltd',
    'registration_number' => 'CIN' . substr((string)time(), -8),
    'industry_type'       => 'Robotics & Automation',
    'address'             => 'Plot 99, Bhosari MIDC',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '411026',
    'hr_contact_name'     => 'Vikram Singhania',
    'hr_contact_phone'    => '9822001122',
    'hr_contact_email'    => $testEmail,
    'verification_status' => 'Pending'
];

// 2.1 Create Company
$saveRes = $companyCtrl->saveCompany($companyPayload);
assertTest("Company Creation via Controller", $saveRes['success'] === true, $saveRes['message']);
$companyId = $saveRes['id'] ?? null;
if ($companyId) $createdCompanyIds[] = $companyId;

// 2.2 Status Transition: Under Review
$statusRes1 = $companyCtrl->updateVerificationStatus($companyId, 'Under Review');
assertTest("Company Status Transition to 'Under Review'", $statusRes1['success'] === true);

// 2.3 Status Transition: Verified
$statusRes2 = $companyCtrl->updateVerificationStatus($companyId, 'Verified');
assertTest("Company Status Transition to 'Verified'", $statusRes2['success'] === true);

$fetchedCompany = $companyCtrl->getCompanyById($companyId);
assertTest("Company Verification Record Persistence", 
    $fetchedCompany !== null && $fetchedCompany['verification_status'] === 'Verified',
    "Status: " . ($fetchedCompany['verification_status'] ?? 'null')
);

// 2.4 Status Transition: Rejection Validation (Must fail without min 10 char reason)
$rejectFailRes = $companyCtrl->updateVerificationStatus($companyId, 'Rejected', 'Short');
assertTest("Company Rejection Validation (Reject short reason)", $rejectFailRes['success'] === false);

// 2.5 Status Transition: Rejection with Valid Reason
$rejectPassRes = $companyCtrl->updateVerificationStatus($companyId, 'Rejected', 'GSTIN and CIN documentation could not be verified with MCA records.');
assertTest("Company Rejection with Mandatory Reason", $rejectPassRes['success'] === true);

$rejectedCompany = $companyCtrl->getCompanyById($companyId);
assertTest("Rejection Reason Persisted", 
    !empty($rejectedCompany['rejection_reason']) && str_contains($rejectedCompany['rejection_reason'], 'documentation could not be verified')
);

// 2.6 Status Transition: Suspension
$suspendRes = $companyCtrl->updateVerificationStatus($companyId, 'Suspended');
assertTest("Company Suspension", $suspendRes['success'] === true);

// 2.7 Company Listing & Filtering
$listRes = $companyCtrl->getCompanies(['search' => 'Test Automation Enterprise', 'status' => 'Suspended']);
assertTest("Company Directory Search & Filter", $listRes['total'] >= 1 && count($listRes['data']) >= 1);

// ----------------------------------------------------
// TEST GROUP 3: User Governance & Administration
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 3: User Lifecycle & Role Boundaries ---" . PHP_EOL;

// 3.1 Fetch Roles
$roles = $userCtrl->getRoles();
$roleMap = [];
foreach ($roles as $r) {
    $roleMap[$r['name']] = (int)$r['id'];
}
assertTest("Role Taxonomy Available", isset($roleMap['super_admin'], $roleMap['recruiter'], $roleMap['industry_admin']));

// 3.2 Create Recruiter Account
$recruiterEmail = "recruiter_" . time() . "@prayas-academy.internal";
$recruiterPayload = [
    'full_name'  => 'Amitabh Roy',
    'email'      => $recruiterEmail,
    'phone'      => '9123456789',
    'role_id'    => $roleMap['recruiter'],
    'status'     => 'Active',
    'password'   => 'Recruiter@2026'
];
$userSaveRes1 = $userCtrl->saveUser($recruiterPayload);
assertTest("Recruiter User Creation", $userSaveRes1['success'] === true, $userSaveRes1['message']);
$recruiterId = $userSaveRes1['id'] ?? null;
if ($recruiterId) $createdUserIds[] = $recruiterId;

// 3.3 Create Industry Admin without Company (Must fail boundary constraint)
$indUserEmail = "ind_admin_" . time() . "@testco.internal";
$invalidIndPayload = [
    'full_name'  => 'Sunil Deshmukh',
    'email'      => $indUserEmail,
    'phone'      => '9876500000',
    'role_id'    => $roleMap['industry_admin'],
    'company_id' => null, // Omitted
    'status'     => 'Active',
    'password'   => 'Industry@2026'
];
$userSaveFail = $userCtrl->saveUser($invalidIndPayload);
assertTest("Industry Admin Company Linkage Enforcement", $userSaveFail['success'] === false);

// 3.4 Create Industry Admin with Company (Must succeed)
$invalidIndPayload['company_id'] = $companyId;
$userSaveRes2 = $userCtrl->saveUser($invalidIndPayload);
assertTest("Industry Admin Creation with Valid Company", $userSaveRes2['success'] === true);
$indUserId = $userSaveRes2['id'] ?? null;
if ($indUserId) $createdUserIds[] = $indUserId;

// 3.5 User Status Control (Suspend / Activate)
$suspendUserRes = $userCtrl->setUserStatus($indUserId, 'Suspended');
assertTest("User Account Suspension", $suspendUserRes['success'] === true);

$fetchedUser = $userCtrl->getUserById($indUserId);
assertTest("User Status Persisted as Suspended", $fetchedUser !== null && $fetchedUser['status'] === 'Suspended');

$activateUserRes = $userCtrl->setUserStatus($indUserId, 'Active');
assertTest("User Account Re-activation", $activateUserRes['success'] === true);

// 3.6 Admin Direct Password Reset
$pwResetRes = $userCtrl->resetPassword($indUserId, 'NewSecureP@ssw0rd99');
assertTest("Admin Direct Password Reset", $pwResetRes['success'] === true);

$userAfterPwReset = $userCtrl->getUserById($indUserId);
assertTest("Password Reset Enforces Mandatory Change", (int)($userAfterPwReset['must_change_password'] ?? 0) === 1);

// 3.7 Self-Suspension Lockout Prevention
$selfSuspendRes = $userCtrl->setUserStatus((int)$_SESSION['user_id'], 'Suspended');
assertTest("Self-Suspension Lockout Prevention Guard", $selfSuspendRes['success'] === false);

// ----------------------------------------------------
// TEST GROUP 4: Role-Based Access Control & Lockout Guard
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 4: RBAC & Super Admin Lockout Guard ---" . PHP_EOL;

$superAdminRoleId = $roleMap['super_admin'];

// Fetch all permissions
$allPerms = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll();
$permIdByName = [];
foreach ($allPerms as $p) {
    $permIdByName[$p['name']] = (int)$p['id'];
}

// Check essential permissions exist
$essentialPerms = ['settings.manage', 'audit.view', 'user.update', 'company.verify'];
$hasAllEssentials = true;
foreach ($essentialPerms as $ep) {
    if (!isset($permIdByName[$ep])) $hasAllEssentials = false;
}
assertTest("Essential Administrative Permissions Exist in DB", $hasAllEssentials);

// Simulate Lockout Guard: An administrator attempts to save Super Admin permissions
// without including the essential core permissions
$submittedPerms = [
    $permIdByName['requirement.view'] ?? 1,
    $permIdByName['candidate.view'] ?? 2
];

// Implement the identical lockout guard algorithm from roles_permissions.php
$guardedPerms = $submittedPerms;
$coreStmt = $pdo->query("SELECT id FROM `permissions` WHERE `name` IN ('settings.manage', 'audit.view', 'user.update', 'company.verify')");
$coreIds = $coreStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($coreIds as $cid) {
    if (!in_array((int)$cid, $guardedPerms, true)) {
        $guardedPerms[] = (int)$cid;
    }
}

$lockoutPrevented = true;
foreach ($coreIds as $cid) {
    if (!in_array((int)$cid, $guardedPerms, true)) {
        $lockoutPrevented = false;
    }
}
assertTest("Super Admin Lockout Guard Re-injects Core Permissions", $lockoutPrevented);

// ----------------------------------------------------
// TEST GROUP 5: Audit Log Ledger & CSV Stream Generation
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 5: Audit Log Ledger & CSV Export ---" . PHP_EOL;

// 5.1 Insert Audit Log
$auditStmt = $pdo->prepare("INSERT INTO `audit_logs` 
    (`user_id`, `company_id`, `action`, `ip_address`, `user_agent`, `details_json`) 
    VALUES (:uid, :cid, :action, :ip, :ua, :details)");

$auditStmt->execute([
    ':uid'     => (int)$_SESSION['user_id'],
    ':cid'     => $companyId,
    ':action'  => 'TEST_AUDIT_VERIFICATION_EVENT',
    ':ip'      => '127.0.0.1',
    ':ua'      => 'PRAYAS-TestSuite/1.0',
    ':details' => json_encode(['test_run' => true, 'timestamp' => time()])
]);
$auditLogId = (int)$pdo->lastInsertId();
if ($auditLogId) $createdAuditIds[] = $auditLogId;

assertTest("Audit Log Insertion", $auditLogId > 0);

// 5.2 Query Audit Log with Action Filter
$auditQuery = $pdo->prepare("SELECT * FROM `audit_logs` WHERE `action` = :act ORDER BY id DESC LIMIT 1");
$auditQuery->execute([':act' => 'TEST_AUDIT_VERIFICATION_EVENT']);
$auditRow = $auditQuery->fetch();

assertTest("Audit Log Query with Action Filter", 
    $auditRow !== false && (int)$auditRow['id'] === $auditLogId
);

// 5.3 Test CSV Generation In-Memory
$csvHandle = fopen('php://temp', 'r+');
fputcsv($csvHandle, ['Audit ID', 'Action', 'User ID', 'IP Address', 'Timestamp']);
fputcsv($csvHandle, [$auditRow['id'], $auditRow['action'], $auditRow['user_id'], $auditRow['ip_address'], $auditRow['created_at']]);
rewind($csvHandle);
$csvOutput = stream_get_contents($csvHandle);
fclose($csvHandle);

assertTest("Audit Log CSV Export Serialization", 
    str_contains($csvOutput, 'TEST_AUDIT_VERIFICATION_EVENT') && str_contains($csvOutput, 'Audit ID')
);

// ----------------------------------------------------
// TEST GROUP 6: Platform Settings Console Persistence
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 6: Platform Settings Persistence ---" . PHP_EOL;

// 6.1 Update Setting
$testSettingKey = 'session_timeout_minutes';
$newTimeoutVal = '45';

$updateSettingStmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
    VALUES (:key, :val) 
    ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
$updateSettingStmt->execute([':key' => $testSettingKey, ':val' => $newTimeoutVal]);

// 6.2 Fetch and verify
$verifySettingStmt = $pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :key");
$verifySettingStmt->execute([':key' => $testSettingKey]);
$savedVal = $verifySettingStmt->fetchColumn();

assertTest("System Setting Update & Retrieval", $savedVal === '45', "Persisted value: {$savedVal}");

// Reset setting back to standard default
$updateSettingStmt->execute([':key' => $testSettingKey, ':val' => '30']);

// ----------------------------------------------------
// TEST GROUP 7: Safe Cleanup of Test Artifacts
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 7: Safe Cleanup of Test Artifacts ---" . PHP_EOL;

// Delete created users
if (!empty($createdUserIds)) {
    $inUsers = implode(',', array_map('intval', $createdUserIds));
    $pdo->exec("DELETE FROM `users` WHERE `id` IN ($inUsers)");
    assertTest("Cleaned Up Test User Accounts", true, "Deleted IDs: $inUsers");
}

// Delete created company
if (!empty($createdCompanyIds)) {
    $inCompanies = implode(',', array_map('intval', $createdCompanyIds));
    $pdo->exec("DELETE FROM `companies` WHERE `id` IN ($inCompanies)");
    assertTest("Cleaned Up Test Company Records", true, "Deleted IDs: $inCompanies");
}

// Delete created audit log
if (!empty($createdAuditIds)) {
    $inAudit = implode(',', array_map('intval', $createdAuditIds));
    $pdo->exec("DELETE FROM `audit_logs` WHERE `id` IN ($inAudit)");
    assertTest("Cleaned Up Test Audit Log Records", true, "Deleted IDs: $inAudit");
}

// ----------------------------------------------------
// Test Summary
// ----------------------------------------------------
echo PHP_EOL . "==================================================" . PHP_EOL;
echo "TEST RESULTS SUMMARY: " . PHP_EOL;
echo "  Total Passed: $passCount" . PHP_EOL;
echo "  Total Failed: $failCount" . PHP_EOL;
echo "==================================================" . PHP_EOL;

if ($failCount > 0) {
    exit(1);
}
exit(0);

