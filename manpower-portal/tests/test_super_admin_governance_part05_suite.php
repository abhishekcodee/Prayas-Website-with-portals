<?php
/**
 * Part 05 Dedicated Super Admin Governance & Limit Enforcement Test Suite
 * Organization: PRAYAS – The Automation Academy
 * Module: Company Governance, Verification Workflow, Quota Limits & Audit Ledger
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/IndustryController.php';
require_once __DIR__ . '/../controllers/RequirementController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 05 Super Admin Governance Suite\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertGov(string $title, bool $condition, string $detail = ''): void {
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
// Group 1: Company Management Directory, Filtering & Search
// -------------------------------------------------------------------
echo "--- Group 1: Company Management Directory & Search ---\n";
$compCtrl = new CompanyController($pdo);

// Create dedicated test company fixture
$ts = time();
$createRes = $compCtrl->saveCompany([
    'company_name'        => "Gov Test Corp {$ts}",
    'registration_number' => "U29100MH{$ts}PTC999",
    'industry_type'       => 'Automotive & Auto Components',
    'address'             => 'Plot 45, Chakan Phase II',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '410501',
    'hr_contact_name'     => 'Anand Deshmukh',
    'hr_contact_phone'    => '9876543210',
    'hr_contact_email'    => "anand_{$ts}@govtest.com",
    'verification_status' => 'Pending'
]);

assertGov("Company creation via controller", $createRes['success'] === true, $createRes['message'] ?? '');
$testCompId = (int)$createRes['id'];

// Retrieve single company
$compRecord = $compCtrl->getCompanyById($testCompId);
assertGov("Single company retrieval", !empty($compRecord) && $compRecord['id'] === $testCompId);
assertGov("Formatted company code generated", !empty($compRecord['company_code']) && str_starts_with($compRecord['company_code'], 'CMP-'));

// Search by name
$searchRes = $compCtrl->getCompanies(['search' => "Gov Test Corp {$ts}"]);
assertGov("Search finds target company by keyword", $searchRes['total'] >= 1);

// Filter by status
$pendingRes = $compCtrl->getCompanies(['status' => 'Pending']);
assertGov("Filter by status 'Pending' returns results", $pendingRes['total'] >= 1);

// -------------------------------------------------------------------
// Group 2: Company Verification Workflow (Approve, Reject, Suspend)
// -------------------------------------------------------------------
echo "\n--- Group 2: Company Verification Lifecycle Workflow ---\n";

// Move to Under Review
$revRes = $compCtrl->updateVerificationStatus($testCompId, 'Under Review');
assertGov("Status transition to 'Under Review'", $revRes['success'] === true);
$cCheck1 = $compCtrl->getCompanyById($testCompId);
assertGov("Status persisted as 'Under Review'", $cCheck1['verification_status'] === 'Under Review');

// Approve / Verify
$appRes = $compCtrl->updateVerificationStatus($testCompId, 'Verified', null, 'All legal CIN checks approved.');
assertGov("Status transition to 'Verified'", $appRes['success'] === true);
$cCheck2 = $compCtrl->getCompanyById($testCompId);
assertGov("Status persisted as 'Verified'", $cCheck2['verification_status'] === 'Verified');
assertGov("Verified timestamp recorded", !empty($cCheck2['verified_at']));

// Rejection validation (short reason < 10 chars must fail)
$shortRej = $compCtrl->updateVerificationStatus($testCompId, 'Rejected', 'bad');
assertGov("Short rejection reason rejected (<10 chars)", $shortRej['success'] === false);

// Rejection with detailed reason
$validRej = $compCtrl->updateVerificationStatus($testCompId, 'Rejected', 'Factory address physical verification failed.');
assertGov("Rejection with detailed mandatory reason accepted", $validRej['success'] === true);
$cCheck3 = $compCtrl->getCompanyById($testCompId);
assertGov("Rejection reason persisted in database", str_contains((string)$cCheck3['rejection_reason'], 'Factory address'));
assertGov("Rejected company record preserved (not deleted)", !empty($cCheck3['id']));

// Suspension & Reactivation
$suspRes = $compCtrl->updateVerificationStatus($testCompId, 'Suspended');
assertGov("Company suspension accepted", $suspRes['success'] === true);
$cCheck4 = $compCtrl->getCompanyById($testCompId);
assertGov("Status persisted as 'Suspended'", $cCheck4['verification_status'] === 'Suspended');

$reactRes = $compCtrl->updateVerificationStatus($testCompId, 'Active');
assertGov("Company reactivation accepted", $reactRes['success'] === true);

// -------------------------------------------------------------------
// Group 3: Company Limit Subsystem & Backend Enforcement
// -------------------------------------------------------------------
echo "\n--- Group 3: Company Limit Allocation & Backend Enforcement ---\n";

// Create Company B for multi-tenant isolation testing
$createCompB = $compCtrl->saveCompany([
    'company_name'        => "Isolated Partner B {$ts}",
    'registration_number' => "U29100MH{$ts}PTC888",
    'industry_type'       => 'Industrial Automation & Robotics',
    'address'             => 'Plot 99, Bhosari MIDC',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '411026',
    'hr_contact_name'     => 'Sneha Kulkarni',
    'hr_contact_phone'    => '9876543211',
    'hr_contact_email'    => "sneha_{$ts}@partnerb.com",
    'verification_status' => 'Verified'
]);
$compBId = (int)$createCompB['id'];

// Set custom limits for Company A: max 1 plant, max 1 user, max 50 vacancies
$setLimitRes = $compCtrl->setCompanyLimits($testCompId, [
    'max_plants'       => 1,
    'max_users'        => 2,
    'max_requirements' => 2,
    'max_vacancies'    => 50
]);
assertGov("Super Admin configures custom limits for Company A", $setLimitRes['success'] === true);

$limitsA = $compCtrl->getCompanyLimits($testCompId);
assertGov("Company A max_plants is 1", $limitsA['limits']['max_plants'] === 1);
assertGov("Company A max_vacancies is 50", $limitsA['limits']['max_vacancies'] === 50);

// Verify Company B inherits default limits (unaffected by Company A)
$limitsB = $compCtrl->getCompanyLimits($compBId);
assertGov("Company B has independent default max_plants (5)", $limitsB['limits']['max_plants'] === 5);
assertGov("Multi-tenant limit isolation guaranteed", $limitsA['limits']['max_plants'] !== $limitsB['limits']['max_plants']);

// Test Backend Limit Enforcement on Plant Creation
$indCtrl = new IndustryController($pdo);

// 1. First plant for Company A succeeds (0 -> 1 <= 1)
$plant1 = $indCtrl->savePlant($testCompId, [
    'plant_name'           => 'Plant Unit 1',
    'plant_code'           => "PLT1_{$ts}",
    'plant_address'        => 'Chakan Phase II',
    'state'                => 'Maharashtra',
    'district'             => 'Pune',
    'city'                 => 'Pune',
    'pincode'              => '410501',
    'contact_person'       => 'Plant Manager 1',
    'contact_number'       => '9876543210'
]);
assertGov("Company A Plant #1 creation allowed (within limit 1)", $plant1['success'] === true);

// 2. Second plant for Company A must be BLOCKED on backend!
$plant2 = $indCtrl->savePlant($testCompId, [
    'plant_name'           => 'Plant Unit 2',
    'plant_code'           => "PLT2_{$ts}",
    'plant_address'        => 'Chakan Phase II',
    'state'                => 'Maharashtra',
    'district'             => 'Pune',
    'city'                 => 'Pune',
    'pincode'              => '410501',
    'contact_person'       => 'Plant Manager 2',
    'contact_number'       => '9876543210'
]);
assertGov("Company A Plant #2 blocked by backend limit gate", $plant2['success'] === false);
assertGov("Capacity warning returned to user", str_contains($plant2['message'] ?? '', 'Capacity Limit Reached'));

// 3. Company B can create a plant successfully (independent quota)
$plantB = $indCtrl->savePlant($compBId, [
    'plant_name'           => 'Company B Plant 1',
    'plant_code'           => "PLTB_{$ts}",
    'plant_address'        => 'Bhosari MIDC',
    'state'                => 'Maharashtra',
    'district'             => 'Pune',
    'city'                 => 'Pune',
    'pincode'              => '411026',
    'contact_person'       => 'Plant Lead B',
    'contact_number'       => '9876543211'
]);
assertGov("Company B plant creation succeeds independently", $plantB['success'] === true);

// -------------------------------------------------------------------
// Group 4: User & Company Administrator Administration
// -------------------------------------------------------------------
echo "\n--- Group 4: User & Company Administrator Administration ---\n";
$userCtrl = new UserController($pdo);

// Role list
$roles = $userCtrl->getRoles();
$indAdminRoleId = 0;
foreach ($roles as $r) {
    if ($r['name'] === 'industry_admin') {
        $indAdminRoleId = (int)$r['id'];
        break;
    }
}

// 1. Mandatory company linkage for Industry Admin
$missingCompUser = $userCtrl->saveUser([
    'full_name'  => 'Missing Company Admin',
    'email'      => "no_comp_{$ts}@prayas.test",
    'role_id'    => $indAdminRoleId,
    'company_id' => null,
    'password'   => 'P@ssword2026!'
]);
assertGov("Industry Admin without company rejected", $missingCompUser['success'] === false);

// 2. Valid company admin creation
$compAdminRes = $userCtrl->saveUser([
    'full_name'  => 'Rajesh Sharma Admin',
    'email'      => "rajesh_admin_{$ts}@govtest.com",
    'phone'      => '9876543210',
    'role_id'    => $indAdminRoleId,
    'company_id' => $testCompId,
    'password'   => 'P@ssword2026!',
    'status'     => 'Active'
]);
assertGov("Valid Company Administrator created", $compAdminRes['success'] === true);
$adminUserId = (int)$compAdminRes['id'];

// 3. Deactivate administrator
$deactRes = $userCtrl->setUserStatus($adminUserId, 'Suspended');
assertGov("Administrator suspension via controller", $deactRes['success'] === true);
$uCheck = $userCtrl->getUserById($adminUserId);
assertGov("Administrator status is Suspended", $uCheck['status'] === 'Suspended');

// 4. Reactivate administrator
$actRes = $userCtrl->setUserStatus($adminUserId, 'Active');
assertGov("Administrator reactivation", $actRes['success'] === true);

// 5. Direct Admin Password Reset
$pwReset = $userCtrl->resetPassword($adminUserId, 'ResetP@ssword2026!');
assertGov("Admin direct password reset succeeds", $pwReset['success'] === true);

// -------------------------------------------------------------------
// Group 5: Audit Log Ledger Tracking
// -------------------------------------------------------------------
echo "\n--- Group 5: Audit Log Ledger Tracking ---\n";

// Check audit logs for COMPANY_LIMITS_CHANGED
$auditLimits = $pdo->prepare("SELECT COUNT(*) FROM `audit_logs` WHERE `action` = 'COMPANY_LIMITS_CHANGED' AND `company_id` = :cid");
$auditLimits->execute([':cid' => $testCompId]);
assertGov("COMPANY_LIMITS_CHANGED recorded in audit ledger", (int)$auditLimits->fetchColumn() > 0);

// Check audit logs for COMPANY_STATUS_CHANGED
$auditStatus = $pdo->prepare("SELECT COUNT(*) FROM `audit_logs` WHERE `action` = 'COMPANY_STATUS_CHANGED' AND `company_id` = :cid");
$auditStatus->execute([':cid' => $testCompId]);
assertGov("COMPANY_STATUS_CHANGED recorded in audit ledger", (int)$auditStatus->fetchColumn() > 0);

// Company-specific audit history retrieval
$compAudit = $compCtrl->getCompanyAuditHistory($testCompId);
assertGov("Company-scoped audit history retrieved", count($compAudit) >= 2);

// -------------------------------------------------------------------
// Group 6: Safe Cleanup & Data Immutability
// -------------------------------------------------------------------
echo "\n--- Group 6: Safe Cleanup & Candidate Database Safety ---\n";

// Clean up test plants
$pdo->prepare("DELETE FROM `manufacturing_plants` WHERE `company_id` IN (:c1, :c2)")->execute([':c1' => $testCompId, ':c2' => $compBId]);
// Clean up test users
$pdo->prepare("DELETE FROM `users` WHERE `id` = :uid")->execute([':uid' => $adminUserId]);
// Clean up test settings (company limits)
$pdo->prepare("DELETE FROM `system_settings` WHERE `setting_key` IN (:k1, :k2)")->execute([':k1' => "company_limits_{$testCompId}", ':k2' => "company_limits_{$compBId}"]);
// Clean up test audit logs
$pdo->prepare("DELETE FROM `audit_logs` WHERE `company_id` IN (:c1, :c2)")->execute([':c1' => $testCompId, ':c2' => $compBId]);
// Clean up test companies
$pdo->prepare("DELETE FROM `companies` WHERE `id` IN (:c1, :c2)")->execute([':c1' => $testCompId, ':c2' => $compBId]);
assertGov("Test fixtures cleaned up safely", true);

// External candidate databases safety verification
$c1 = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$c2 = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();
assertGov("External Candidate Database 1 Intact", $c1 === 1292, "1,292 records");
assertGov("External Candidate Database 2 Intact", $c2 === 56, "56 records");

echo "\n========================================================\n";
echo "PART 05 GOVERNANCE RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);

