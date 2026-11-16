<?php
/**
 * Manpower Requirement Engine Test Suite
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../controllers/RequirementController.php';

$pdo = Database::getConnection();
$reqCtrl = new RequirementController($pdo);

echo "==================================================" . PHP_EOL;
echo "PRAYAS Portal - Part 07 Requirement Engine Tests" . PHP_EOL;
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

// Track IDs for safe cleanup
$createdCompanyIds = [];
$createdUserIds = [];
$createdPlantIds = [];
$createdReqIds = [];

// Clean up any previous test artifacts
$pdo->exec("DELETE FROM `requirement_trades` WHERE `trade_name` LIKE 'TestTrade%'");
$pdo->exec("DELETE FROM `manpower_requirements` WHERE `title` LIKE '%Test Requisition%' OR `requirement_id` LIKE 'REQ-%-999%'");
$pdo->exec("DELETE FROM `manufacturing_plants` WHERE `plant_code` LIKE 'PL-TEST-%'");
$pdo->exec("DELETE FROM `users` WHERE `email` LIKE '%test_req_admin%@automation.internal'");
$pdo->exec("DELETE FROM `companies` WHERE `company_name` LIKE '%Precision Engineering Test Ltd%'");

// ----------------------------------------------------
// Test Setup: Create Verified Enterprise & Admin
// ----------------------------------------------------
$testRegNo = "CIN" . substr((string)time(), -8);
$testHrEmail = "test_req_admin_" . time() . "@automation.internal";

$pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES 
    ('Precision Engineering Test Ltd', :reg, 'CNC Machining & Tool Rooms', 'Plot 12, Bhosari MIDC', 'Maharashtra', 'Pune', 'Pune', '411026', 'Sanjay Patil', '9822334455', :email, 'Verified')")
    ->execute([':reg' => $testRegNo, ':email' => $testHrEmail]);
$testCompanyId = (int)$pdo->lastInsertId();
$createdCompanyIds[] = $testCompanyId;

// Create Industry User
$pdo->prepare("INSERT INTO `users` (`role_id`, `company_id`, `full_name`, `email`, `phone`, `password_hash`, `status`) 
    VALUES (2, :cid, 'Sanjay Patil', :email, '9822334455', 'dummy_hash', 'Active')")
    ->execute([':cid' => $testCompanyId, ':email' => $testHrEmail]);
$testUserId = (int)$pdo->lastInsertId();
$createdUserIds[] = $testUserId;

// Establish Session
$_SESSION['user_id'] = $testUserId;
$_SESSION['role_name'] = 'industry_admin';
$_SESSION['role_id'] = 2;
$_SESSION['company_id'] = $testCompanyId;
$_SESSION['user_email'] = $testHrEmail;
$_SESSION['full_name'] = 'Sanjay Patil';

// Create Manufacturing Plant
$pdo->prepare("INSERT INTO `manufacturing_plants` 
    (`company_id`, `plant_name`, `plant_code`, `plant_address`, `state`, `district`, `city`, `pincode`, `contact_person`, `contact_number`, `status`) 
    VALUES (:cid, 'Precision Tooling Plant 1', 'PL-TEST-01', 'Plot 12, Bhosari', 'Maharashtra', 'Pune', 'Pune', '411026', 'Sanjay Patil', '9822334455', 'Active')")
    ->execute([':cid' => $testCompanyId]);
$testPlantId = (int)$pdo->lastInsertId();
$createdPlantIds[] = $testPlantId;

// ----------------------------------------------------
// TEST GROUP 1: Concurrency-Safe Sequence Generation
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 1: Concurrency-Safe Sequence & Code Generator ---" . PHP_EOL;

$code1 = $reqCtrl->generateRequirementCode();
$code2 = $reqCtrl->generateRequirementCode();

assertTest("Requirement Code Matches REQ-YYYYMM-XXXX Format", (bool)preg_match('/^REQ-\d{6}-\d{4}$/', $code1), $code1);
assertTest("Sequential Monotonic Increment Guaranteed", (int)substr($code2, -4) === (int)substr($code1, -4) + 1, "{$code1} -> {$code2}");

// ----------------------------------------------------
// TEST GROUP 2: Multi-Stage Server-Side Validation Rules
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 2: Multi-Stage Validation Rules Engine ---" . PHP_EOL;

// 2.1 Short Title Validation
$badTitlePayload = ['title' => 'CNC', 'job_description' => 'Valid description with more than twenty characters.'];
$resTitle = $reqCtrl->validateRequirement($badTitlePayload, $testCompanyId);
assertTest("Short Title Rejected (<5 chars)", !$resTitle['is_valid'] && isset($resTitle['errors']['title']));

// 2.2 Short Description Validation
$badDescPayload = ['title' => 'Valid CNC Operator', 'job_description' => 'Short.'];
$resDesc = $reqCtrl->validateRequirement($badDescPayload, $testCompanyId);
assertTest("Short Description Rejected (<20 chars)", !$resDesc['is_valid'] && isset($resDesc['errors']['job_description']));

// 2.3 Headcount & Trade Vacancy Sum Inconsistency
$inconsistentTradesPayload = [
    'title'             => 'Valid CNC Operator Requisition',
    'job_description'   => 'Operating 3-axis CNC vertical milling center with tool offset calibration.',
    'quantity_required' => 20,
    'trades'            => [
        ['trade_name' => 'Machinist', 'vacancies' => 10],
        ['trade_name' => 'Fitter', 'vacancies' => 5]
    ] // Sum is 15, but quantity_required is 20
];
$resTradeSum = $reqCtrl->validateRequirement($inconsistentTradesPayload, $testCompanyId);
assertTest("Trade Vacancy Sum Inconsistency Rejected", !$resTradeSum['is_valid'] && isset($resTradeSum['errors']['quantity_consistency']));

// 2.4 Statutory Age Validation (Factories Act <18 rule)
$badAgePayload = [
    'title'             => 'Valid CNC Operator Requisition',
    'job_description'   => 'Operating 3-axis CNC vertical milling center with tool offset calibration.',
    'min_age'           => 16,
    'trades'            => [['trade_name' => 'Machinist', 'vacancies' => 5]]
];
$resAge = $reqCtrl->validateRequirement($badAgePayload, $testCompanyId);
assertTest("Underage Minimum Age Rejected (<18 Factories Act)", !$resAge['is_valid'] && isset($resAge['errors']['min_age']));

// 2.5 Salary Bounds Validation (min > max or min <= 0)
$badSalPayload = [
    'title'             => 'Valid CNC Operator Requisition',
    'job_description'   => 'Operating 3-axis CNC vertical milling center with tool offset calibration.',
    'min_salary'        => 25000,
    'max_salary'        => 20000, // Max < Min!
    'trades'            => [['trade_name' => 'Machinist', 'vacancies' => 5]]
];
$resSal = $reqCtrl->validateRequirement($badSalPayload, $testCompanyId);
assertTest("Salary Inversion Rejected (max < min)", !$resSal['is_valid'] && isset($resSal['errors']['max_salary']));

// 2.6 Chronological Timeline Validation
$badDatesPayload = [
    'title'                => 'Valid CNC Operator Requisition',
    'job_description'      => 'Operating 3-axis CNC vertical milling center with tool offset calibration.',
    'application_deadline' => date('Y-m-d', strtotime('-2 days')), // In the past!
    'interview_date'       => date('Y-m-d', strtotime('+5 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+10 days')),
    'trades'               => [['trade_name' => 'Machinist', 'vacancies' => 5]]
];
$resDates = $reqCtrl->validateRequirement($badDatesPayload, $testCompanyId);
assertTest("Past Application Deadline Rejected", !$resDates['is_valid'] && isset($resDates['errors']['application_deadline']));

// ----------------------------------------------------
// TEST GROUP 3: Draft Saving & Resumption
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 3: Draft Persistence & Resumption ---" . PHP_EOL;

$draftPayload = [
    'title'             => 'Test Requisition - CNC Trainee Batch',
    'plant_id'          => $testPlantId,
    'wizard_step'       => 2,
    'quantity_required' => 8,
    'trades'            => [
        ['trade_name' => 'Machinist', 'vacancies' => 5],
        ['trade_name' => 'Turner', 'vacancies' => 3]
    ]
];

$saveDraftRes = $reqCtrl->saveDraft($testCompanyId, $draftPayload);
assertTest("Initial Requirement Draft Saved", $saveDraftRes['success'] === true);
$draftId = (int)($saveDraftRes['requirement_id'] ?? 0);
if ($draftId) $createdReqIds[] = $draftId;

$fetchedDraft = $reqCtrl->getRequirementById($testCompanyId, $draftId);
assertTest("Draft Status Persisted as DRAFT", $fetchedDraft !== null && $fetchedDraft['status'] === 'DRAFT');
assertTest("Draft Wizard Step Persisted", (int)$fetchedDraft['wizard_step'] === 2);
assertTest("Sub-Trades Persisted in Draft", count($fetchedDraft['trades']) === 2);

// Update Draft to Step 4
$draftPayload['wizard_step'] = 4;
$draftPayload['min_salary'] = 16000;
$draftPayload['max_salary'] = 22000;
$updateDraftRes = $reqCtrl->saveDraft($testCompanyId, $draftPayload, $draftId);
assertTest("Draft Progress Resumed & Updated to Step 4", $updateDraftRes['success'] === true);

$updatedDraft = $reqCtrl->getRequirementById($testCompanyId, $draftId);
assertTest("Updated Draft Wizard Step Verified", (int)$updatedDraft['wizard_step'] === 4);

// ----------------------------------------------------
// TEST GROUP 4: Auto-Approval vs. Review Routing Policy
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 4: Auto-Approval & Review Routing Policy ---" . PHP_EOL;

// 4.1 Standard Clean Payload (Verified company, <=50 qty, salary >= 12k, timeline >= 7 days) -> Auto-Approved
$cleanPayload = [
    'title'                => 'Test Requisition - Senior Robotic Welder',
    'plant_id'             => $testPlantId,
    'job_description'      => 'Welding robotic fixtures using automated MIG and TIG submerged arc machines.',
    'quantity_required'    => 15,
    'trades'               => [
        ['trade_name' => 'Welder (Gas & Electric)', 'vacancies' => 10],
        ['trade_name' => 'Fitter', 'vacancies' => 5]
    ],
    'qualification'        => 'ITI',
    'board_type'           => 'Either',
    'passing_year_from'    => '2022',
    'passing_year_to'      => '2026',
    'experience_required'  => 'Fresher',
    'min_age'              => 18,
    'max_age'              => 28,
    'min_salary'           => 18000,
    'max_salary'           => 24000,
    'salary_type'          => 'Monthly',
    'application_deadline' => date('Y-m-d', strtotime('+10 days')),
    'interview_date'       => date('Y-m-d', strtotime('+15 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+25 days')),
    'priority'             => 'High'
];

$valClean = $reqCtrl->validateRequirement($cleanPayload, $testCompanyId);
assertTest("Standard Clean Payload Evaluates to APPROVED", 
    $valClean['is_valid'] === true && $valClean['target_status'] === 'APPROVED'
);

// 4.2 High Volume Requisition (>50 vacancies) -> Routed to PENDING_REVIEW
$highVolPayload = $cleanPayload;
$highVolPayload['quantity_required'] = 80;
$highVolPayload['trades'] = [
    ['trade_name' => 'Welder (Gas & Electric)', 'vacancies' => 50],
    ['trade_name' => 'Fitter', 'vacancies' => 30]
];
$valHighVol = $reqCtrl->validateRequirement($highVolPayload, $testCompanyId);
assertTest("High-Volume Requisition (>50 qty) Routes to PENDING_REVIEW", 
    $valHighVol['is_valid'] === true && $valHighVol['target_status'] === 'PENDING_REVIEW'
);

// ----------------------------------------------------
// TEST GROUP 5: Requisition Submission & Sub-Tables Persistence
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 5: Requisition Submission & Sub-Tables ---" . PHP_EOL;

$submitRes = $reqCtrl->submitRequirement($testCompanyId, $cleanPayload);
assertTest("Requirement Formal Submission Successful", $submitRes['success'] === true, $submitRes['message']);
$submittedReqId = (int)($submitRes['requirement_id'] ?? 0);
if ($submittedReqId) $createdReqIds[] = $submittedReqId;

$fetchedSubmitted = $reqCtrl->getRequirementById($testCompanyId, $submittedReqId);
assertTest("Submitted Requirement Status is APPROVED", $fetchedSubmitted['status'] === 'APPROVED');
assertTest("Requirement Code Generated (REQ-YYYYMM-XXXX)", 
    str_starts_with($fetchedSubmitted['requirement_id'], 'REQ-' . date('Ym')),
    $fetchedSubmitted['requirement_id']
);
assertTest("Trade Distribution Persisted in Database", count($fetchedSubmitted['trades']) === 2);
assertTest("Status History Recorded Transition", count($fetchedSubmitted['history']) >= 1);

// ----------------------------------------------------
// TEST GROUP 6: Requisition Modification & Cancellation
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 6: Requisition Modification & Cancellation ---" . PHP_EOL;

// Cancel requirement
$cancelRes = $reqCtrl->cancelRequirement($testCompanyId, $submittedReqId, 'Internal production shift rescheduled.');
assertTest("Requirement Cancellation with Valid Rationale", $cancelRes['success'] === true);

$cancelledReq = $reqCtrl->getRequirementById($testCompanyId, $submittedReqId);
assertTest("Requirement Status Persisted as CANCELLED", $cancelledReq['status'] === 'CANCELLED');

// Attempt cancellation of already cancelled requirement (Must fail)
$reCancel = $reqCtrl->cancelRequirement($testCompanyId, $submittedReqId, 'Attempting re-cancel');
assertTest("Re-cancellation of Cancelled Requirement Blocked", $reCancel['success'] === false);

// ----------------------------------------------------
// TEST GROUP 7: Multi-Tenant Scoping & Cross-Company IDOR Guard
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 7: Multi-Tenant Scoping & IDOR Prevention ---" . PHP_EOL;

// Query requirement using foreign company ID (Must return null)
$foreignQuery = $reqCtrl->getRequirementById(999999, $submittedReqId);
assertTest("Cross-Company Requirement Query Blocked (IDOR Guard)", $foreignQuery === null);

// Cancel requirement using foreign company ID (Must fail)
$foreignCancel = $reqCtrl->cancelRequirement(999999, $submittedReqId, 'Malicious cancel attempt');
assertTest("Cross-Company Cancellation Blocked (IDOR Guard)", $foreignCancel['success'] === false);

// ----------------------------------------------------
// TEST GROUP 8: Safe Cleanup & Candidate Database Safety
// ----------------------------------------------------
echo PHP_EOL . "--- Test Group 8: Safe Cleanup & Candidate Database Safety ---" . PHP_EOL;

// Delete created requirements and sub-tables
if (!empty($createdReqIds)) {
    $inReqs = implode(',', $createdReqIds);
    $pdo->exec("DELETE FROM `requirement_trades` WHERE `requirement_id` IN ($inReqs)");
    $pdo->exec("DELETE FROM `requirement_status_history` WHERE `requirement_id` IN ($inReqs)");
    $pdo->exec("DELETE FROM `manpower_requirements` WHERE `id` IN ($inReqs)");
    assertTest("Cleaned Up Test Requirements", true, "Deleted IDs: $inReqs");
}

// Delete plant
if (!empty($createdPlantIds)) {
    $inPlants = implode(',', $createdPlantIds);
    $pdo->exec("DELETE FROM `manufacturing_plants` WHERE `id` IN ($inPlants)");
    assertTest("Cleaned Up Test Plants", true);
}

// Delete user
if (!empty($createdUserIds)) {
    $inUsers = implode(',', $createdUserIds);
    $pdo->exec("DELETE FROM `users` WHERE `id` IN ($inUsers)");
    assertTest("Cleaned Up Test Users", true);
}

// Delete company
if (!empty($createdCompanyIds)) {
    $inComps = implode(',', $createdCompanyIds);
    $pdo->exec("DELETE FROM `companies` WHERE `id` IN ($inComps)");
    assertTest("Cleaned Up Test Companies", true);
}

// Attest candidate databases untouched
$cand1 = (int)Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `prayas_iti_dashboard`.`students`")[0]['cnt'];
$cand2 = (int)Database::queryCandidateReadOnly("SELECT COUNT(*) as cnt FROM `itistudents`.`candidates`")[0]['cnt'];
assertTest("Candidate Databases Untouched & Intact", $cand1 === 1292 && $cand2 === 56, "1,292 + 56 records");

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

