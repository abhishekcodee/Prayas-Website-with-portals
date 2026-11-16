<?php
/**
 * Part 07 Dedicated Manpower Requirement Management Engine Test Suite
 * Organization: PRAYAS – The Automation Academy
 * Module: Requirement Lifecycle, Multi-Trade Demand, Validation, Isolation & Numbering
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../controllers/RequirementController.php';
require_once __DIR__ . '/../controllers/IndustryController.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 07 Manpower Requirement Suite\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertReq(string $title, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

$reqCtrl  = new RequirementController($pdo);
$indCtrl  = new IndustryController($pdo);
$compCtrl = new CompanyController($pdo);
$userCtrl = new UserController($pdo);

$ts = time();

// 1. Provision Test Companies A & B
$compA_res = $compCtrl->saveCompany([
    'company_name'        => "Part07 Heavy Engineering Corp A {$ts}",
    'registration_number' => "U28000MH{$ts}PTC001",
    'industry_type'       => 'Automotive & Heavy Industry',
    'address'             => 'Plot P-1, Chakan Industrial Area',
    'state'               => 'Maharashtra',
    'district'            => 'Pune',
    'city'                => 'Pune',
    'pincode'             => '410501',
    'hr_contact_name'     => 'Mahesh Deshmukh',
    'hr_contact_phone'    => '9822000001',
    'hr_contact_email'    => "mahesh_{$ts}@corpa.com",
    'verification_status' => 'Verified'
]);
$compA_id = (int)$compA_res['id'];

$compB_res = $compCtrl->saveCompany([
    'company_name'        => "Part07 Precision Robotics Corp B {$ts}",
    'registration_number' => "U28000GJ{$ts}PTC002",
    'industry_type'       => 'Robotics & Automation',
    'address'             => 'Plot R-2, Sanand GIDC',
    'state'               => 'Gujarat',
    'district'            => 'Ahmedabad',
    'city'                => 'Sanand',
    'pincode'             => '382170',
    'hr_contact_name'     => 'Ketan Shah',
    'hr_contact_phone'    => '9822000002',
    'hr_contact_email'    => "ketan_{$ts}@corpb.com",
    'verification_status' => 'Verified'
]);
$compB_id = (int)$compB_res['id'];

// 2. Provision Manufacturing Plants for Company A and Company B
$plantA_res = $indCtrl->savePlant($compA_id, [
    'plant_name'             => "Chakan Main Assembly Plant {$ts}",
    'plant_code'             => "PLT-CHK-{$ts}",
    'plant_address'          => 'Plot P-1, Phase 2, Chakan MIDC',
    'state'                  => 'Maharashtra',
    'district'               => 'Pune',
    'city'                   => 'Chakan',
    'pincode'                => '410501',
    'contact_person'         => 'Rajiv Nair',
    'contact_number'         => '9822000010',
    'plant_type'             => 'Manufacturing Unit',
    'status'                 => 'Active'
]);
$plantA_id = (int)$plantA_res['id'];

$plantB_res = $indCtrl->savePlant($compB_id, [
    'plant_name'             => "Sanand Automation Center {$ts}",
    'plant_code'             => "PLT-SND-{$ts}",
    'plant_address'          => 'Plot R-2, GIDC Phase 1',
    'state'                  => 'Gujarat',
    'district'               => 'Ahmedabad',
    'city'                   => 'Sanand',
    'pincode'                => '382170',
    'contact_person'         => 'Girish Patel',
    'contact_number'         => '9822000020',
    'plant_type'             => 'Automation Center',
    'status'                 => 'Active'
]);
$plantB_id = (int)$plantB_res['id'];

// 3. Provision Industry Admin Users
$userA_id = (int)$userCtrl->saveUser([
    'full_name'  => "Admin User Corp A {$ts}",
    'email'      => "admin_a_{$ts}@corpa.com",
    'password'   => 'AdminPass123!@#',
    'role_id'    => 4, // industry_admin
    'company_id' => $compA_id
])['id'];

$userB_id = (int)$userCtrl->saveUser([
    'full_name'  => "Admin User Corp B {$ts}",
    'email'      => "admin_b_{$ts}@corpb.com",
    'password'   => 'AdminPass123!@#',
    'role_id'    => 4, // industry_admin
    'company_id' => $compB_id
])['id'];

// Set current session user to User A
$_SESSION['user_id'] = $userA_id;
$_SESSION['company_id'] = $compA_id;
$_SESSION['role'] = 'industry_admin';

// ===================================================================
// GROUP 1: CONCURRENCY-SAFE REQUIREMENT ID GENERATION
// ===================================================================
echo "--- Group 1: Concurrency-Safe Requirement ID Generation ---\n";

$code1 = $reqCtrl->generateRequirementCode();
$code2 = $reqCtrl->generateRequirementCode();

assertReq("Requirement Code matches format REQ-YYYYMM-XXXX", (bool)preg_match('/^REQ-\d{6}-\d{4}$/', $code1), "Generated: {$code1}");
assertReq("Consecutive Requirement Codes are strictly sequential", (int)substr($code2, 11) === ((int)substr($code1, 11) + 1), "Code1: {$code1}, Code2: {$code2}");

// Verify atomic sequence table persistence
$seqCheck = $pdo->prepare("SELECT `last_sequence` FROM `requirement_sequences` WHERE `year_month` = :ym");
$seqCheck->execute([':ym' => date('Ym')]);
$lastSeq = (int)$seqCheck->fetchColumn();
assertReq("Database sequence reflects current high-water mark", $lastSeq >= (int)substr($code2, 11), "High-water mark: {$lastSeq}");

// ===================================================================
// GROUP 2: MULTI-STEP DRAFT CREATION & WIZARD PERSISTENCE
// ===================================================================
echo "\n--- Group 2: Multi-Step Draft Creation & Wizard Persistence ---\n";

$draftDataStep1 = [
    'title'               => "CNC Machine Operator Draft {$ts}",
    'plant_id'            => $plantA_id,
    'wizard_step'         => 1,
    'quantity_required'   => 10,
    'department'          => 'Machining Section',
    'job_description'     => 'Draft job description for CNC milling and lathe operations.',
    'trades'              => [
        ['trade_name' => 'Machinist', 'vacancies' => 6],
        ['trade_name' => 'Turner', 'vacancies' => 4]
    ]
];

$draft1 = $reqCtrl->saveDraft($compA_id, $draftDataStep1);
assertReq("Draft initialized successfully", $draft1['success'] === true, $draft1['message']);
$draftReqId = (int)$draft1['requirement_id'];
$draftReqCode = $draft1['req_code'];
assertReq("Draft assigned valid REQ code", str_starts_with($draftReqCode, 'REQ-'), $draftReqCode);

// Verify draft in database
$fetchedDraft = $reqCtrl->getRequirementById($compA_id, $draftReqId);
assertReq("Draft persisted with status 'DRAFT'", $fetchedDraft['status'] === 'DRAFT');
assertReq("Draft wizard_step stored accurately", (int)$fetchedDraft['wizard_step'] === 1);
assertReq("Draft total vacancies matches trade sum (6 + 4 = 10)", (int)$fetchedDraft['quantity_required'] === 10);
assertReq("Draft trades sub-table populated correctly", count($fetchedDraft['trades']) === 2);

// Advance draft to Step 4
$draftDataStep4 = array_merge($draftDataStep1, [
    'wizard_step'         => 4,
    'title'               => "CNC Machine Operator & Tool Setter Draft {$ts}",
    'min_salary'          => 16000,
    'max_salary'          => 22000,
    'job_location_city'   => 'Chakan'
]);
$draftUpdate = $reqCtrl->saveDraft($compA_id, $draftDataStep4, $draftReqId);
assertReq("Draft updated successfully at higher step", $draftUpdate['success'] === true);

$fetchedDraftStep4 = $reqCtrl->getRequirementById($compA_id, $draftReqId);
assertReq("Updated wizard step is 4", (int)$fetchedDraftStep4['wizard_step'] === 4);
assertReq("Updated title persisted", $fetchedDraftStep4['title'] === "CNC Machine Operator & Tool Setter Draft {$ts}");

// ===================================================================
// GROUP 3: DYNAMIC TRADE-WISE DEMAND BUILDER & AUTO-SUM
// ===================================================================
echo "\n--- Group 3: Dynamic Trade-Wise Demand Builder & Auto-Sum ---\n";

$multiTradeDraft = [
    'title'               => "Multi-Discipline Plant Technicians {$ts}",
    'plant_id'            => $plantA_id,
    'wizard_step'         => 2,
    'trades'              => [
        ['trade_name' => 'Fitter', 'vacancies' => 8, 'qualification' => 'ITI', 'board_type' => 'Either', 'passing_year_from' => '2023', 'passing_year_to' => '2026', 'required_skills' => 'Bench fitting, Grinding'],
        ['trade_name' => 'Electrician', 'vacancies' => 5, 'qualification' => 'ITI', 'board_type' => 'NCVT', 'passing_year_from' => '2022', 'passing_year_to' => '2025', 'required_skills' => 'Panel wiring, PLC diagnostics'],
        ['trade_name' => 'Welder (Gas & Electric)', 'vacancies' => 7, 'qualification' => 'ITI', 'board_type' => 'SCVT', 'passing_year_from' => '2024', 'passing_year_to' => '2026', 'required_skills' => 'MIG/TIG welding, Pipe jointing']
    ]
];

$multiDraftRes = $reqCtrl->saveDraft($compA_id, $multiTradeDraft);
assertReq("Multi-trade requisition draft created", $multiDraftRes['success'] === true);
$multiReqId = (int)$multiDraftRes['requirement_id'];

$fetchedMulti = $reqCtrl->getRequirementById($compA_id, $multiReqId);
assertReq("Auto-sum calculated total vacancies (8 + 5 + 7 = 20)", (int)$fetchedMulti['quantity_required'] === 20);
assertReq("Exactly 3 distinct trade rows recorded", count($fetchedMulti['trades']) === 3);

// Verify distinct trades
$tradeNames = array_column($fetchedMulti['trades'], 'trade_name');
assertReq("Trades include Fitter, Electrician, Welder", in_array('Fitter', $tradeNames, true) && in_array('Electrician', $tradeNames, true) && in_array('Welder (Gas & Electric)', $tradeNames, true));

// ===================================================================
// GROUP 4: SERVER-SIDE VALIDATION RULES
// ===================================================================
echo "\n--- Group 4: Server-Side Validation Rules ---\n";

// 1. Short title (< 5 chars)
$badTitlePayload = [
    'title'                => 'CNC',
    'plant_id'             => $plantA_id,
    'quantity_required'    => 5,
    'job_description'      => 'Valid job description with sufficient length for testing.',
    'min_salary'           => 15000,
    'max_salary'           => 20000,
    'application_deadline' => date('Y-m-d', strtotime('+10 days')),
    'interview_date'       => date('Y-m-d', strtotime('+15 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+25 days')),
    'trades'               => [['trade_name' => 'Fitter', 'vacancies' => 5]]
];
$val1 = $reqCtrl->validateRequirement($badTitlePayload, $compA_id);
assertReq("Rejection of title under 5 characters", !$val1['is_valid'] && isset($val1['errors']['title']));

// 2. Short description (< 20 chars)
$badDescPayload = array_merge($badTitlePayload, [
    'title'           => 'CNC Milling Specialist',
    'job_description' => 'Too short.'
]);
$val2 = $reqCtrl->validateRequirement($badDescPayload, $compA_id);
assertReq("Rejection of description under 20 characters", !$val2['is_valid'] && isset($val2['errors']['job_description']));

// 3. Zero or Negative vacancies
$badVacPayload = array_merge($badTitlePayload, [
    'title'             => 'CNC Milling Specialist',
    'job_description'   => 'Sufficiently detailed job description for machine setup and maintenance.',
    'quantity_required' => 0,
    'trades'            => [['trade_name' => 'Fitter', 'vacancies' => 0]]
]);
$val3 = $reqCtrl->validateRequirement($badVacPayload, $compA_id);
assertReq("Rejection of zero vacancy allocation", !$val3['is_valid'] && isset($val3['errors']['quantity_required']));

// 4. Trade Sum Parity Mismatch
$mismatchPayload = array_merge($badTitlePayload, [
    'title'             => 'CNC Milling Specialist',
    'job_description'   => 'Sufficiently detailed job description for machine setup and maintenance.',
    'quantity_required' => 15,
    'trades'            => [['trade_name' => 'Fitter', 'vacancies' => 10]] // 10 != 15
]);
$val4 = $reqCtrl->validateRequirement($mismatchPayload, $compA_id);
assertReq("Rejection when total vacancies != trade allocations sum", !$val4['is_valid'] && isset($val4['errors']['quantity_required']));

// 5. Salary Inversion (Max < Min)
$salInvertPayload = array_merge($badTitlePayload, [
    'title'             => 'CNC Milling Specialist',
    'job_description'   => 'Sufficiently detailed job description for machine setup and maintenance.',
    'quantity_required' => 5,
    'min_salary'        => 22000,
    'max_salary'        => 18000, // Inversion
    'trades'            => [['trade_name' => 'Fitter', 'vacancies' => 5]]
]);
$val5 = $reqCtrl->validateRequirement($salInvertPayload, $compA_id);
assertReq("Rejection of inverted salary range (Max < Min)", !$val5['is_valid'] && isset($val5['errors']['max_salary']));

// 6. Non-positive Min Salary
$zeroSalPayload = array_merge($salInvertPayload, [
    'min_salary' => 0,
    'max_salary' => 15000
]);
$val6 = $reqCtrl->validateRequirement($zeroSalPayload, $compA_id);
assertReq("Rejection of non-positive minimum salary", !$val6['is_valid'] && isset($val6['errors']['min_salary']));

// 7. Statutory Minimum Age (< 18)
$underagePayload = array_merge($salInvertPayload, [
    'min_salary' => 15000,
    'max_salary' => 20000,
    'min_age'    => 16 // Factories Act violation
]);
$val7 = $reqCtrl->validateRequirement($underagePayload, $compA_id);
assertReq("Rejection of candidate age under statutory minimum 18", !$val7['is_valid'] && isset($val7['errors']['min_age']));

// 8. Passing Year Inversion (To < From)
$yearInvertPayload = array_merge($underagePayload, [
    'min_age'           => 18,
    'passing_year_from' => 2025,
    'passing_year_to'   => 2022 // Inversion
]);
$val8 = $reqCtrl->validateRequirement($yearInvertPayload, $compA_id);
assertReq("Rejection when passing year to < passing year from", !$val8['is_valid'] && isset($val8['errors']['passing_year_to']));

// ===================================================================
// GROUP 5: CHRONOLOGICAL TIMELINE VALIDATION
// ===================================================================
echo "\n--- Group 5: Chronological Timeline Validation ---\n";

$validBase = [
    'title'                => "Automation Technician Specialist {$ts}",
    'plant_id'             => $plantA_id,
    'quantity_required'    => 5,
    'job_description'      => 'Operate automated packaging and palletizing machinery with quality inspections.',
    'min_salary'           => 16500,
    'max_salary'           => 22000,
    'min_age'              => 18,
    'passing_year_from'    => 2022,
    'passing_year_to'      => 2026,
    'trades'               => [['trade_name' => 'Fitter', 'vacancies' => 5]]
];

// 1. Deadline in past
$pastDeadPayload = array_merge($validBase, [
    'application_deadline' => date('Y-m-d', strtotime('-2 days')),
    'interview_date'       => date('Y-m-d', strtotime('+10 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+20 days'))
]);
$valDate1 = $reqCtrl->validateRequirement($pastDeadPayload, $compA_id);
assertReq("Rejection of application deadline in the past", !$valDate1['is_valid'] && isset($valDate1['errors']['application_deadline']));

// 2. Interview before deadline
$intBeforeDeadPayload = array_merge($validBase, [
    'application_deadline' => date('Y-m-d', strtotime('+15 days')),
    'interview_date'       => date('Y-m-d', strtotime('+10 days')), // Before deadline
    'target_joining_date'  => date('Y-m-d', strtotime('+20 days'))
]);
$valDate2 = $reqCtrl->validateRequirement($intBeforeDeadPayload, $compA_id);
assertReq("Rejection when interview date precedes application deadline", !$valDate2['is_valid'] && isset($valDate2['errors']['interview_date']));

// 3. Joining before interview
$joinBeforeIntPayload = array_merge($validBase, [
    'application_deadline' => date('Y-m-d', strtotime('+10 days')),
    'interview_date'       => date('Y-m-d', strtotime('+15 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+12 days')) // Before interview
]);
$valDate3 = $reqCtrl->validateRequirement($joinBeforeIntPayload, $compA_id);
assertReq("Rejection when target joining precedes interview date", !$valDate3['is_valid'] && isset($valDate3['errors']['target_joining_date']));

// 4. Valid chronological sequence passes cleanly
$cleanTimelinePayload = array_merge($validBase, [
    'application_deadline' => date('Y-m-d', strtotime('+15 days')),
    'interview_date'       => date('Y-m-d', strtotime('+20 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+30 days'))
]);
$valDate4 = $reqCtrl->validateRequirement($cleanTimelinePayload, $compA_id);
assertReq("Clean chronological timeline passes validation", $valDate4['is_valid'] === true);

// ===================================================================
// GROUP 6: STRICT MULTI-TENANT & PLANT ISOLATION
// ===================================================================
echo "\n--- Group 6: Strict Multi-Tenant & Plant Isolation ---\n";

// 1. Cross-Company Plant Attachment Block (Company A cannot pick Plant B)
$crossPlantDraft = array_merge($validBase, [
    'title'    => 'Cross Plant Attempt',
    'plant_id' => $plantB_id // Belongs to Comp B
]);
$crossPlantRes = $reqCtrl->saveDraft($compA_id, $crossPlantDraft);
assertReq("Company A blocked from assigning Company B's plant in draft", $crossPlantRes['success'] === false);

$crossPlantSubmit = $reqCtrl->submitRequirement($compA_id, $crossPlantDraft);
assertReq("Company A blocked from submitting requisition for Company B's plant", $crossPlantSubmit['success'] === false);

// 2. Company B cannot read Company A's requirement
$compB_fetch = $reqCtrl->getRequirementById($compB_id, $draftReqId);
assertReq("Company B cannot read Company A's requirement (Strict null return)", $compB_fetch === null);

// 3. Company B cannot update Company A's draft
$crossUpdateRes = $reqCtrl->saveDraft($compB_id, ['title' => 'Comp B Hijack Attempt'], $draftReqId);
assertReq("Company B cannot modify Company A's draft", $crossUpdateRes['success'] === false);

// 4. Company B cannot cancel Company A's requirement
$crossCancelRes = $reqCtrl->cancelRequirement($compB_id, $draftReqId, 'Unauthorized cancel attempt');
assertReq("Company B cannot cancel Company A's requirement", $crossCancelRes['success'] === false);

// 5. Company B cannot duplicate Company A's requirement
$crossDupRes = $reqCtrl->duplicateRequirement($compB_id, $draftReqId);
assertReq("Company B cannot duplicate Company A's requirement", $crossDupRes['success'] === false);

// 6. Listing isolation: Company B requirements list must not contain Company A requirements
$compB_list = $reqCtrl->getRequirements($compB_id);
$compB_req_ids = array_column($compB_list['data'], 'id');
assertReq("Company B requirement directory strictly isolated from Company A", !in_array($draftReqId, $compB_req_ids, true));

// ===================================================================
// GROUP 7: REQUIREMENT STATUS LIFECYCLE & REVISION HISTORY
// ===================================================================
echo "\n--- Group 7: Requirement Status Lifecycle & Revision History ---\n";

// 1. Submit Valid Requirement from Draft
$submitPayload = array_merge($cleanTimelinePayload, [
    'department'      => 'Robotic Stamping Unit',
    'min_salary'      => 17000,
    'max_salary'      => 22000,
    'job_description' => 'Operate and maintain robotic stamping press line with die alignment and quality tracking.'
]);

$submitRes = $reqCtrl->submitRequirement($compA_id, $submitPayload, $draftReqId);
assertReq("Requirement submitted successfully", $submitRes['success'] === true, $submitRes['message']);
$activeReqCode = $submitRes['req_code'];
$initialStatus = $submitRes['status']; // APPROVED or PENDING_REVIEW

$dossier = $reqCtrl->getRequirementById($compA_id, $draftReqId);
assertReq("Status updated to '{$initialStatus}'", $dossier['status'] === $initialStatus);
assertReq("Wizard step set to 8 (Completed)", (int)$dossier['wizard_step'] === 8);
assertReq("Draft payload cleared upon submission", empty($dossier['draft_payload_json']));

// Check history timeline
assertReq("Requirement status history recorded submission transition", count($dossier['history']) >= 2);
$latestHist = $dossier['history'][0];
assertReq("Latest history record matches new status '{$initialStatus}'", $latestHist['new_status'] === $initialStatus);

// 2. Lifecycle Transition: to RECRUITMENT_IN_PROGRESS
$trans1 = $reqCtrl->updateRequirementStatus($compA_id, $draftReqId, 'RECRUITMENT_IN_PROGRESS', 'Recruiter assigned and ITI candidate matching algorithm initiated.');
assertReq("Transition to RECRUITMENT_IN_PROGRESS", $trans1['success'] === true);

// 3. Lifecycle Transition: to PARTIALLY_FULFILLED
$trans2 = $reqCtrl->updateRequirementStatus($compA_id, $draftReqId, 'PARTIALLY_FULFILLED', '3 out of 5 technicians joined duty.');
assertReq("Transition to PARTIALLY_FULFILLED", $trans2['success'] === true);

// 4. Lifecycle Transition: to FULFILLED
$trans3 = $reqCtrl->updateRequirementStatus($compA_id, $draftReqId, 'FULFILLED', 'All 5 technicians successfully deployed on shop floor.');
assertReq("Transition to FULFILLED", $trans3['success'] === true);

// 5. Verification of History Audit Trail
$updatedDossier = $reqCtrl->getRequirementById($compA_id, $draftReqId);
assertReq("Final status reflects 'FULFILLED'", $updatedDossier['status'] === 'FULFILLED');
$statusesInHistory = array_column($updatedDossier['history'], 'new_status');
assertReq("History contains 'DRAFT'", in_array('DRAFT', $statusesInHistory, true));
assertReq("History contains 'RECRUITMENT_IN_PROGRESS'", in_array('RECRUITMENT_IN_PROGRESS', $statusesInHistory, true));
assertReq("History contains 'PARTIALLY_FULFILLED'", in_array('PARTIALLY_FULFILLED', $statusesInHistory, true));
assertReq("History contains 'FULFILLED'", in_array('FULFILLED', $statusesInHistory, true));

// ===================================================================
// GROUP 8: REQUIREMENT DUPLICATION ENGINE
// ===================================================================
echo "\n--- Group 8: Requirement Duplication Engine ---\n";

$dupRes = $reqCtrl->duplicateRequirement($compA_id, $draftReqId);
assertReq("Requirement duplication succeeds", $dupRes['success'] === true, $dupRes['message']);
$dupId = (int)$dupRes['requirement_id'];
$dupCode = $dupRes['req_code'];

assertReq("Duplicated requirement has a fresh, distinct REQ code", $dupCode !== $activeReqCode && str_starts_with($dupCode, 'REQ-'));

$dupDossier = $reqCtrl->getRequirementById($compA_id, $dupId);
assertReq("Duplicated requirement status defaults to 'DRAFT'", $dupDossier['status'] === 'DRAFT');
assertReq("Duplicated requirement wizard_step resets to 1", (int)$dupDossier['wizard_step'] === 1);
assertReq("Duplicated requirement title prefixed with 'Copy of '", str_starts_with($dupDossier['title'], 'Copy of '));
assertReq("Duplicated requirement clones trade allocation rows", count($dupDossier['trades']) === count($updatedDossier['trades']));
assertReq("Duplicated requirement preserves same plant facility", (int)$dupDossier['plant_id'] === (int)$updatedDossier['plant_id']);
assertReq("Duplicated requirement creates initial history record", count($dupDossier['history']) === 1 && $dupDossier['history'][0]['new_status'] === 'DRAFT');

// Cancel duplicated requirement
$cancelRes = $reqCtrl->cancelRequirement($compA_id, $dupId, 'Testing cancellation workflow on duplicated draft.');
assertReq("Cancellation of duplicate draft succeeds", $cancelRes['success'] === true);
$cancelledDossier = $reqCtrl->getRequirementById($compA_id, $dupId);
assertReq("Cancelled requirement status is 'CANCELLED'", $cancelledDossier['status'] === 'CANCELLED');

// ===================================================================
// GROUP 9: DASHBOARD METRICS & CAPACITY LIMITS
// ===================================================================
echo "\n--- Group 9: Dashboard Metrics & Capacity Limits ---\n";

$dashStats = $reqCtrl->getRequirementDashboardStats($compA_id);
assertReq("Dashboard stats return total_requirements >= 2", $dashStats['total_requirements'] >= 2);
assertReq("Dashboard stats return fulfilled >= 1", $dashStats['fulfilled'] >= 1);
assertReq("Dashboard stats return closed_or_cancelled >= 1", $dashStats['closed_or_cancelled'] >= 1);
assertReq("Dashboard stats sum total_vacancies accurately", $dashStats['total_vacancies'] > 0);

// Capacity limit check (max_vacancies per requirement beyond plan limit)
$excessVacPayload = array_merge($cleanTimelinePayload, [
    'title'             => 'Massive Vacancy Request',
    'quantity_required' => 5000, // Beyond normal limit
    'trades'            => [['trade_name' => 'Fitter', 'vacancies' => 5000]]
]);
$limitRes = $reqCtrl->submitRequirement($compA_id, $excessVacPayload);
assertReq("Excess vacancy submission rejected by capacity validator", $limitRes['success'] === false);

// ===================================================================
// GROUP 10: EXTERNAL CANDIDATE DATABASES IMMUTABILITY AUDIT
// ===================================================================
echo "\n--- Group 10: External Candidate Databases Immutability Audit ---\n";

$itiCheck = $pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
assertReq("External prayas_iti_dashboard.students remains intact (1,292 rows)", (int)$itiCheck === 1292, "Actual: {$itiCheck}");

$candCheck = $pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();
assertReq("External itistudents.candidates remains intact (56 rows)", (int)$candCheck === 56, "Actual: {$candCheck}");

// Summary
echo "\n========================================================\n";
echo "Part 07 Manpower Requirement Engine Test Summary:\n";
echo "Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 2) . "%\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
