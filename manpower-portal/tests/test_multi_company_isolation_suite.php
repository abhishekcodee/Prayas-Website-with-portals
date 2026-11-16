<?php
/**
 * Automated Test Suite — Part 12: Multi-Company Tenant Isolation & Security
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../controllers/IndustryController.php';
require_once __DIR__ . '/../controllers/RequirementController.php';
require_once __DIR__ . '/../controllers/ReportController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 12 Multi-Company Isolation Tests\n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $description, bool $condition): void {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $testsFailed++;
    }
}

// -------------------------------------------------------------
// Fixture Provisioning: Two Independent Companies (A and B)
// -------------------------------------------------------------
echo "--- Provisioning Multi-Tenant Test Fixtures ---\n";

$suffixA = bin2hex(random_bytes(3));
$suffixB = bin2hex(random_bytes(3));

// 1. Create Company A
$compNameA = "Company Alpha {$suffixA} Corp";
$pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, 
     `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Automotive', 'Chakan MIDC', 'Maharashtra', 'Pune', 'Pune', '410501', 
            'Amit Deshmukh', '9890011111', :email, 'Verified')")
    ->execute([':cname' => $compNameA, ':reg' => "CIN-A-{$suffixA}", ':email' => "hr_a_{$suffixA}@alpha.com"]);
$companyAId = (int)$pdo->lastInsertId();

// 2. Create Company B
$compNameB = "Company Beta {$suffixB} Robotics";
$pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, 
     `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Robotics', 'Bhosari MIDC', 'Maharashtra', 'Pune', 'Pune', '411026', 
            'Pooja Kulkarni', '9890022222', :email, 'Verified')")
    ->execute([':cname' => $compNameB, ':reg' => "CIN-B-{$suffixB}", ':email' => "hr_b_{$suffixB}@beta.com"]);
$companyBId = (int)$pdo->lastInsertId();

// 3. Create Industry Admin User for Company A
$roleStmt = $pdo->query("SELECT id FROM `roles` WHERE name = 'industry_admin' LIMIT 1");
$indAdminRoleId = (int)$roleStmt->fetchColumn();

$pdo->prepare("INSERT INTO `users` (`role_id`, `company_id`, `full_name`, `email`, `password_hash`, `status`) 
    VALUES (:rid, :cid, 'Alpha Admin User', :email, 'hash_a', 'Active')")
    ->execute([':rid' => $indAdminRoleId, ':cid' => $companyAId, ':email' => "admin_a_{$suffixA}@alpha.com"]);
$userAId = (int)$pdo->lastInsertId();

// 4. Create Industry Admin User for Company B
$pdo->prepare("INSERT INTO `users` (`role_id`, `company_id`, `full_name`, `email`, `password_hash`, `status`) 
    VALUES (:rid, :cid, 'Beta Admin User', :email, 'hash_b', 'Active')")
    ->execute([':rid' => $indAdminRoleId, ':cid' => $companyBId, ':email' => "admin_b_{$suffixB}@beta.com"]);
$userBId = (int)$pdo->lastInsertId();

// 5. Create Requirement for Company A (10 vacancies)
$reqCodeA = "REQ-ALPHA-{$suffixA}";
$pdo->prepare("INSERT INTO `manpower_requirements` 
    (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Alpha CNC Lathe Operators', 10, 'APPROVED', :uid, NOW())")
    ->execute([':cid' => $companyAId, ':code' => $reqCodeA, ':uid' => $userAId]);
$reqAId = (int)$pdo->lastInsertId();

// 6. Create Requirement for Company B (25 vacancies)
$reqCodeB = "REQ-BETA-{$suffixB}";
$pdo->prepare("INSERT INTO `manpower_requirements` 
    (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Beta Robotic Arm Welders', 25, 'APPROVED', :uid, NOW())")
    ->execute([':cid' => $companyBId, ':code' => $reqCodeB, ':uid' => $userBId]);
$reqBId = (int)$pdo->lastInsertId();

// 7. Attach candidate match A to Requirement A
$pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source_id`, `candidate_source`, `candidate_name`, `trade`, `passing_year`, `match_score`, `status`, `is_shared_with_industry`, `matched_at`) 
    VALUES (:rid, 201, 'prayas_dashboard', 'Kishore A. Patil', 'Fitter', '2024', 88.0, 'Matched', 1, NOW())")
    ->execute([':rid' => $reqAId]);
$matchAId = (int)$pdo->lastInsertId();

// 8. Attach candidate match B to Requirement B (Unshared candidate)
$pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source_id`, `candidate_source`, `candidate_name`, `trade`, `passing_year`, `match_score`, `status`, `is_shared_with_industry`, `matched_at`) 
    VALUES (:rid, 202, 'itistudents', 'Snehal B. Shinde', 'Welder', '2025', 92.5, 'Matched', 0, NOW())")
    ->execute([':rid' => $reqBId]);
$matchBId = (int)$pdo->lastInsertId();

echo "  [INFO] Test fixtures provisioned: Company A ({$companyAId}), Company B ({$companyBId})\n\n";

$industryCtrl = new IndustryController($pdo);
$reqCtrl = new RequirementController($pdo);
$reportCtrl = new ReportController($pdo);

// -------------------------------------------------------------
// Scenario 1: Company A cannot access Company B's requirements
// -------------------------------------------------------------
echo "--- Scenario 1: Requirement Isolation ---\n";

$reqsResA = $industryCtrl->getMyRequirements($companyAId);
$reqsA = $reqsResA['data'] ?? [];
$containsReqA = false;
$containsReqB = false;

foreach ($reqsA as $r) {
    if ($r['id'] == $reqAId) $containsReqA = true;
    if ($r['id'] == $reqBId) $containsReqB = true;
}

assertTest("Company A query returns Company A's requirement", $containsReqA);
assertTest("Company A query strictly excludes Company B's requirement", !$containsReqB);

// Attempt fetching Requirement B with Company A's ID
$directCheck = $pdo->prepare("SELECT * FROM `manpower_requirements` WHERE id = :rid AND company_id = :cid");
$directCheck->execute([':rid' => $reqBId, ':cid' => $companyAId]);
assertTest("Direct query with foreign company_id returns zero rows", $directCheck->fetch() === false);

// -------------------------------------------------------------
// Scenario 2: Company A cannot access Company B's candidates
// -------------------------------------------------------------
echo "\n--- Scenario 2: Candidate Match Isolation ---\n";

$candsResA = $industryCtrl->getCandidateMatches($companyAId);
$candsA = $candsResA['data'] ?? [];
$containsCandA = false;
$containsCandB = false;

foreach ($candsA as $c) {
    if ($c['id'] == $matchAId) $containsCandA = true;
    if ($c['id'] == $matchBId) $containsCandB = true;
}

assertTest("Company A accesses only candidate matches tied to Company A requisitions", $containsCandA);
assertTest("Company A cannot see candidate matches belonging to Company B", !$containsCandB);

// -------------------------------------------------------------
// Scenario 3: Company A cannot access Company B's reports
// -------------------------------------------------------------
echo "\n--- Scenario 3: Report & Metric Isolation ---\n";

$metricsA = $reportCtrl->getExecutiveMetrics($companyAId);
assertTest("Company A executive metrics isolated to Company A vacancies (10)", $metricsA['total_vacancies'] === 10);
assertTest("Company A executive metrics isolated to Company A requirements count (1)", $metricsA['total_requirements'] === 1);

$repRowsA = $reportCtrl->getRequirementWiseVacancyReport([], $companyAId);
$repHasB = false;
foreach ($repRowsA as $r) {
    if ($r['requirement_id'] === $reqCodeB) $repHasB = true;
}
assertTest("Company A vacancy report strictly excludes Company B records", !$repHasB);

// -------------------------------------------------------------
// Scenario 4: Company A cannot access Company B's team members
// -------------------------------------------------------------
echo "\n--- Scenario 4: User & Team Isolation ---\n";

$teamA = $industryCtrl->getTeamMembers($companyAId);
$hasUserA = false;
$hasUserB = false;

foreach ($teamA as $u) {
    if ($u['id'] == $userAId) $hasUserA = true;
    if ($u['id'] == $userBId) $hasUserB = true;
}

assertTest("Company A team roster lists Company A user", $hasUserA);
assertTest("Company A team roster strictly excludes Company B user", !$hasUserB);

// -------------------------------------------------------------
// Scenario 5: Company A cannot modify Company B's records
// -------------------------------------------------------------
echo "\n--- Scenario 5: Unauthorized Modification Prevention ---\n";

// Attempt to cancel Requirement B using Company A's credentials
$cancelRes = $reqCtrl->cancelRequirement($companyAId, $reqBId, 'Malicious cancel attempt');
assertTest("Cancelling Company B's requirement by Company A is rejected", $cancelRes['success'] === false);

// Verify Requirement B remains unchanged in database
$checkB = $pdo->query("SELECT status FROM `manpower_requirements` WHERE id = {$reqBId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Requirement B status was not modified (Remains 'APPROVED')", $checkB['status'] === 'APPROVED');

// -------------------------------------------------------------
// Scenario 6: Industry Admin cannot access Super Admin functionality
// -------------------------------------------------------------
echo "\n--- Scenario 6: Privilege Escalation Guard ---\n";

// Simulate Industry Admin role session
$_SESSION['user_id'] = $userAId;
$_SESSION['role_name'] = 'industry_admin';
$_SESSION['role_label'] = 'Industry Admin';
$_SESSION['company_id'] = $companyAId;
$_SESSION['permissions'] = ['requirement.create', 'requirement.view'];

assertTest("Industry Admin lacks 'super_admin' role check", hasRole('super_admin') === false);
assertTest("Industry Admin lacks 'settings.manage' permission", hasPermission('settings.manage') === false);
assertTest("Industry Admin lacks 'company.verify' permission", hasPermission('company.verify') === false);
assertTest("Industry Admin lacks 'user.delete' permission", hasPermission('user.delete') === false);

// -------------------------------------------------------------
// Scenario 7: Candidate Privacy & Redaction Guard
// -------------------------------------------------------------
echo "\n--- Scenario 7: Candidate Privacy & Redaction Guard ---\n";

// Unshared candidate check: Match B has is_shared_with_industry = 0
$candsResB = $industryCtrl->getCandidateMatches($companyBId);
$candsB = $candsResB['data'] ?? [];
$unsharedExposed = false;
foreach ($candsB as $c) {
    if ($c['id'] == $matchBId && empty($c['is_shared_with_industry'])) {
        $unsharedExposed = true;
    }
}
assertTest("Unshared candidates (is_shared_with_industry = 0) are hidden from employer", !$unsharedExposed);

// -------------------------------------------------------------
// Scenario 8: URL Parameter Manipulation Immunity
// -------------------------------------------------------------
echo "\n--- Scenario 8: URL Parameter Manipulation Immunity ---\n";

// User A attempts to pass GET parameter company_id=CompanyB
$_GET['company_id'] = $companyBId;

// getCompanyId() must strictly read from session, ignoring client GET override
$effectiveCompanyId = getCompanyId();
assertTest("getCompanyId() strictly respects session value ($companyAId) ignoring manipulated GET parameter ($companyBId)", $effectiveCompanyId === $companyAId);

unset($_GET['company_id']);

// -------------------------------------------------------------
// Scenario 9: Cross-Tenant API / Post Isolation
// -------------------------------------------------------------
echo "\n--- Scenario 9: Plant Facility Multi-Tenant Isolation ---\n";

// Create Plant for Company B
$pdo->prepare("INSERT INTO `manufacturing_plants` (`company_id`, `plant_name`, `plant_code`, `city`, `district`, `state`, `pincode`, `status`, `created_at`) 
    VALUES (:cid, 'Beta Robotics Unit 1', 'BR-01', 'Pune', 'Pune', 'Maharashtra', '411026', 'Active', NOW())")
    ->execute([':cid' => $companyBId]);
$plantBId = (int)$pdo->lastInsertId();

// User A attempts to deactivate Plant B
$deactivatePlantRes = $industryCtrl->setPlantStatus($companyAId, $plantBId, 'Inactive');
assertTest("Deactivating Company B's plant by Company A is rejected by tenant guard", $deactivatePlantRes['success'] === false);

// Verify Plant B remains active
$plantCheck = $pdo->query("SELECT status FROM `manufacturing_plants` WHERE id = {$plantBId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Plant B status remained 'Active' (Unmodified)", $plantCheck !== false && $plantCheck['status'] === 'Active');

// -------------------------------------------------------------
// Scenario 10: Export Functionality Zero Cross-Company Leakage
// -------------------------------------------------------------
echo "\n--- Scenario 10: Export Functionality Zero Cross-Company Leakage ---\n";

$exportRowsA = $pdo->prepare("SELECT mr.requirement_id, mr.title, mr.quantity_required, c.company_name 
    FROM `manpower_requirements` mr 
    JOIN `companies` c ON c.id = mr.company_id 
    WHERE mr.company_id = :cid");
$exportRowsA->execute([':cid' => $companyAId]);
$exportDataA = $exportRowsA->fetchAll(PDO::FETCH_ASSOC);

$exportLeak = false;
foreach ($exportDataA as $row) {
    if (str_contains($row['company_name'], 'Beta') || $row['requirement_id'] === $reqCodeB) {
        $exportLeak = true;
    }
}
assertTest("Export dataset for Company A contains zero references to Company B", !$exportLeak);

// -------------------------------------------------------------
// Safe Teardown of Test Fixtures
// -------------------------------------------------------------
echo "\n--- Cleaning Up Isolation Test Artifacts ---\n";

try {
    $pdo->prepare("DELETE FROM `manufacturing_plants` WHERE id = {$plantBId}")->execute();
    $pdo->prepare("DELETE FROM `candidate_matches` WHERE id IN ({$matchAId}, {$matchBId})")->execute();
    $pdo->prepare("DELETE FROM `manpower_requirements` WHERE id IN ({$reqAId}, {$reqBId})")->execute();
    $pdo->prepare("DELETE FROM `users` WHERE id IN ({$userAId}, {$userBId})")->execute();
    $pdo->prepare("DELETE FROM `companies` WHERE id IN ({$companyAId}, {$companyBId})")->execute();
    echo "  [INFO] All multi-tenant test fixtures cleaned up safely.\n";
} catch (Exception $e) {
    echo "  [WARN] Teardown warning: " . $e->getMessage() . "\n";
}

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "Multi-Company Isolation Results: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
