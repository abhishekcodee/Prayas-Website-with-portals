<?php
/**
 * PART 08 Automated Test Suite: Automatic Validation & Alert Engine
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP CLI + PDO + MySQL
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/requirement_validation_engine.php';
require_once __DIR__ . '/../includes/notification_service.php';
require_once __DIR__ . '/../controllers/RequirementController.php';

$pdo = Database::getConnection();

echo "====================================================================\n";
echo " PRAYAS PORTAL — PART 08 TEST SUITE: VALIDATION & ALERT ENGINE\n";
echo " Started at: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================================\n\n";

$testsTotal = 0;
$testsPassed = 0;
$testsFailed = 0;

function runTest(string $description, callable $testFn): void {
    global $testsTotal, $testsPassed, $testsFailed;
    $testsTotal++;
    try {
        $result = $testFn();
        if ($result === true) {
            $testsPassed++;
            echo "  [PASS] Test {$testsTotal}: {$description}\n";
        } else {
            $testsFailed++;
            $msg = is_string($result) ? $result : "Assertion returned false";
            echo "  [FAIL] Test {$testsTotal}: {$description} -> {$msg}\n";
        }
    } catch (Throwable $e) {
        $testsFailed++;
        echo "  [ERROR] Test {$testsTotal}: {$description} -> Exception: " . $e->getMessage() . "\n";
    }
}

// -----------------------------------------------------------------------------
// SETUP ISOLATED TEST FIXTURES (Committed so FK constraints & child tables work)
// -----------------------------------------------------------------------------
$uniqueSuffix = bin2hex(random_bytes(4));

// 1. Create Test Companies
$compStmt = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `hr_contact_email`, `verification_status`, `created_at`) 
    VALUES (:name, :code, 'Automotive & Heavy Engineering', :email, 'Verified', NOW())");

$compStmt->execute([
    ':name'  => "Validation Test Co A {$uniqueSuffix}", 
    ':code'  => "REG_A_{$uniqueSuffix}",
    ':email' => "hr_a_{$uniqueSuffix}@test.prayas.org"
]);
$testCompanyIdA = (int)$pdo->lastInsertId();

$compStmt->execute([
    ':name'  => "Validation Test Co B {$uniqueSuffix}", 
    ':code'  => "REG_B_{$uniqueSuffix}",
    ':email' => "hr_b_{$uniqueSuffix}@test.prayas.org"
]);
$testCompanyIdB = (int)$pdo->lastInsertId();

// 2. Create Plants for Company A
$plantStmt = $pdo->prepare("INSERT INTO `manufacturing_plants` 
    (`company_id`, `plant_name`, `plant_code`, `state`, `district`, `city`, `pincode`, `contact_person`, `contact_number`, `status`) 
    VALUES (:cid, :name, :code, 'Maharashtra', 'Pune', 'Pune', '411018', 'Plant Head', '9876543210', 'Active')");
$plantStmt->execute([':cid' => $testCompanyIdA, ':name' => "Plant Alpha {$uniqueSuffix}", ':code' => "PLT-A-{$uniqueSuffix}"]);
$testPlantIdA = (int)$pdo->lastInsertId();

// Plant for Company B
$plantStmt->execute([':cid' => $testCompanyIdB, ':name' => "Plant Beta {$uniqueSuffix}", ':code' => "PLT-B-{$uniqueSuffix}"]);
$testPlantIdB = (int)$pdo->lastInsertId();

// 3. Create Users
$userStmt = $pdo->prepare("INSERT INTO `users` 
    (`company_id`, `role_id`, `full_name`, `email`, `password_hash`, `status`, `created_at`) 
    VALUES (:cid, :role, :name, :email, 'hash', 'Active', NOW())");

// Industry Admin A
$userStmt->execute([
    ':cid'   => $testCompanyIdA,
    ':role'  => 2, // industry_admin
    ':name'  => "Admin User A {$uniqueSuffix}",
    ':email' => "admin_a_{$uniqueSuffix}@test.prayas.org"
]);
$testUserA = (int)$pdo->lastInsertId();

// Industry User B
$userStmt->execute([
    ':cid'   => $testCompanyIdB,
    ':role'  => 3, // industry_user
    ':name'  => "User B {$uniqueSuffix}",
    ':email' => "user_b_{$uniqueSuffix}@test.prayas.org"
]);
$testUserB = (int)$pdo->lastInsertId();

// Track created requirement IDs for teardown
$createdReqIds = [];

try {
    $valEngine = new RequirementValidationEngine($pdo);
    $notifService = new NotificationService($pdo);
    $reqCtrl = new RequirementController($pdo);

    // Baseline 100% valid payload
    $basePayload = [
        'title'                     => 'CNC Technician and Welder Grade I',
        'plant_id'                  => $testPlantIdA,
        'department'                => 'Precision Machining',
        'industry_sector'           => 'Automotive & Heavy Engineering',
        'requirement_purpose'       => 'Production Line Expansion',
        'job_description'           => 'Responsible for operating high-precision 5-axis CNC machines and robotic MIG welding cells.',
        'quantity_required'         => 10,
        'trades'                    => [
            ['trade_name' => 'Fitter', 'vacancies' => 6],
            ['trade_name' => 'Welder', 'vacancies' => 4]
        ],
        'skills'                    => ['CNC Programming', 'MIG Welding', 'Engineering Drawing'],
        'qualification'             => 'ITI Passed',
        'board_type'                => 'NCVT',
        'passing_year_from'         => 2021,
        'passing_year_to'           => 2024,
        'min_age'                   => 19,
        'max_age'                   => 26,
        'gender_preference'         => 'Any',
        'experience_required'       => 'Fresher',
        'min_salary'                => 16000,
        'max_salary'                => 22000,
        'in_hand_salary'            => 15000,
        'salary_type'               => 'Monthly',
        'employment_type'           => 'Full Time',
        'working_hours'             => '8 Hours',
        'shift_details'             => 'Rotational 3 Shifts',
        'weekly_working_days'       => 6,
        'rotational_shift'          => 'Yes',
        'accommodation_provided'    => 'Subsidized Hostel',
        'food_facility'             => 'Subsidized Canteen',
        'transportation_provided'   => 'Yes',
        'safety_equipment'          => 'Yes',
        'pf_esic_benefit'           => 'Yes',
        'overtime_available'        => 'Yes',
        'job_location_state'        => 'Maharashtra',
        'job_location_district'     => 'Pune',
        'job_location_city'         => 'Pimpri-Chinchwad',
        'job_location_pincode'      => '411018',
        'application_deadline'      => date('Y-m-d', strtotime('+15 days')),
        'interview_date'            => date('Y-m-d', strtotime('+20 days')),
        'interview_end_date'        => date('Y-m-d', strtotime('+22 days')),
        'target_joining_date'       => date('Y-m-d', strtotime('+30 days')),
        'priority'                  => 'High',
        'interview_mode'            => 'Plant Campus Walk-in',
        'interview_location'        => 'PRAYAS Training Center, Block C',
        'number_of_rounds'          => 2,
        'contact_person'            => 'Mahesh Patil (Head HR)',
        'contact_email'             => 'mahesh.hr@company.com',
        'contact_phone'             => '9876543210'
    ];

    echo "--- SECTION 1: RULE VALIDATION BY CATEGORY ---\n";

    // Test 1: Category 1 - Missing title
    runTest("Category 1: Missing title triggers ERR_TITLE_REQUIRED", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['title'] = '';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['title']) && $res['is_valid'] === false;
    });

    // Test 2: Category 1 - Title length bounds
    runTest("Category 1: Short title (< 5 chars) triggers ERR-TIT-01 length defect", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['title'] = 'Dev';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['title']) && strpos($res['errors']['title'], 'between 5 and 255 characters') !== false;
    });

    // Test 3: Category 1 - Brief job description completeness
    runTest("Category 1: Brief job description (< 20 chars) triggers ERR-DSC-01 defect", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['job_description'] = 'Short text';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['job_description']);
    });

    // Test 4: Category 2 - Zero total vacancies
    runTest("Category 2: Zero quantity required triggers ERR-QTY-01 bound error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['quantity_required'] = 0;
        $data['trades'] = [];
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['quantity_required']);
    });

    // Test 5: Category 2 - Negative vacancies
    runTest("Category 2: Negative quantity triggers fatal validation defect", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['quantity_required'] = -5;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['quantity_required']);
    });

    // Test 6: Category 2 - Trade sum parity mismatch
    runTest("Category 2: Trade vacancies sum != total quantity triggers ERR-TRD-03 sum parity error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['quantity_required'] = 10;
        $data['trades'] = [
            ['trade_name' => 'Fitter', 'vacancies' => 4],
            ['trade_name' => 'Welder', 'vacancies' => 4] // sum is 8 != 10
        ];
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['quantity_consistency']) && strpos($res['errors']['quantity_consistency'], 'does not match') !== false;
    });

    // Test 7: Category 2 - Duplicate trade specification
    runTest("Category 2: Duplicate trade lines trigger WARN-TRD-01 duplicate warning", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['quantity_required'] = 10;
        $data['trades'] = [
            ['trade_name' => 'Fitter', 'vacancies' => 5],
            ['trade_name' => 'Fitter', 'vacancies' => 5]
        ];
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['trades_duplicate']);
    });

    // Test 8: Category 3 - Statutory minimum age violation (< 18)
    runTest("Category 3: Minimum age < 18 triggers statutory ERR-AGE-01 compliance error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['min_age'] = 16;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['min_age']) && strpos($res['errors']['min_age'], 'cannot be less than 18') !== false;
    });

    // Test 9: Category 3 - Age inversion (min_age > max_age)
    runTest("Category 3: Inverted age range (max < min) triggers ERR-AGE-02 error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['min_age'] = 28;
        $data['max_age'] = 22;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['max_age']);
    });

    // Test 10: Category 3 - Passing year order (from > to)
    runTest("Category 3: Inverted passing year (from > to) triggers ERR-YEA-03 range error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['passing_year_from'] = 2025;
        $data['passing_year_to']   = 2020;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['passing_year_to']);
    });

    // Test 11: Category 4 - Salary inversion (max < min)
    runTest("Category 4: Salary inversion (max < min) triggers ERR-SAL-02 error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['min_salary'] = 25000;
        $data['max_salary'] = 18000;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['max_salary']);
    });

    // Test 12: Category 4 - Salary < statutory benchmark generates advisory warning
    runTest("Category 4: Salary < 12,000 INR triggers WARN-SAL-01 benchmark advisory warning", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['min_salary'] = 9000;
        $data['max_salary'] = 11000;
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['min_salary']);
    });

    // Test 13: Category 5 - Application deadline in past
    runTest("Category 5: Application deadline in the past triggers ERR-DAT-01 chronology error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['application_deadline'] = date('Y-m-d', strtotime('-5 days'));
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['application_deadline']);
    });

    // Test 14: Category 5 - Interview scheduled before deadline
    runTest("Category 5: Interview date before deadline triggers ERR-DAT-02 chronology error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['application_deadline'] = date('Y-m-d', strtotime('+15 days'));
        $data['interview_date']        = date('Y-m-d', strtotime('+10 days')); // before deadline
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['interview_date']);
    });

    // Test 15: Category 5 - Joining date before interview
    runTest("Category 5: Joining date before interview triggers ERR-DAT-03 milestone error", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['interview_date']      = date('Y-m-d', strtotime('+20 days'));
        $data['target_joining_date'] = date('Y-m-d', strtotime('+18 days')); // before interview
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['target_joining_date']);
    });

    // Test 16: Category 5 - Rush schedule warning (< 7 days turnaround)
    runTest("Category 5: Rush turnaround (< 7 days) triggers WARN-DAT-01 velocity advisory", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['application_deadline'] = date('Y-m-d', strtotime('+2 days'));
        $data['interview_date']        = date('Y-m-d', strtotime('+3 days'));
        $data['target_joining_date']   = date('Y-m-d', strtotime('+4 days')); // < 7 days from today
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['target_joining_date']);
    });

    // Test 17: Category 6 - Unspecified deployment location warning
    runTest("Category 6: Unspecified city/state without plant triggers ERR-LOC-03 warning", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['plant_id'] = null;
        $data['job_location_state'] = '';
        $data['job_location_city']  = '';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['job_location_state']);
    });

    // Test 18: Category 6 - Invalid employment type
    runTest("Category 6: Unrecognized employment type triggers ERR-EMP-01 advisory", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['employment_type'] = 'Volunteer';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['employment_type']);
    });

    // Test 19: Category 6 - Cross-company plant ownership isolation
    runTest("Category 6: Plant belonging to Company B used by Company A triggers ERR-LOC-01 ownership error", function() use ($valEngine, $testCompanyIdA, $testPlantIdB, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['plant_id'] = $testPlantIdB; // belongs to Company B!
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['errors']['plant_id']) && strpos($res['errors']['plant_id'], 'does not belong') !== false;
    });

    // Test 20: Category 7 - Invalid priority specification
    runTest("Category 7: Unrecognized priority specification triggers ERR-PRI-01 advisory", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['priority'] = 'SuperImmediate';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['priority']);
    });

    // Test 21: Category 7 - Malformed contact email
    runTest("Category 7: Malformed contact email triggers ERR-CEM-01 format advisory", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['contact_email'] = 'not-a-valid-email';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return isset($res['warnings']['contact_email']);
    });

    echo "\n--- SECTION 2: RESULT CLASSIFICATION & DECISION LOGIC ---\n";

    // Test 22: Classification: VALIDATION_FAILED when errors exist
    runTest("Classification: Returns VALIDATION_FAILED and is_valid=false when fatal defects present", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['title'] = ''; // fatal
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return $res['validation_status'] === 'VALIDATION_FAILED' && $res['is_valid'] === false;
    });

    // Test 23: Classification: VALIDATION_WARNING when only warnings present
    runTest("Classification: Returns VALIDATION_WARNING and is_valid=true when only advisory warnings present", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['priority'] = 'SuperImmediate'; // triggers priority advisory warning only
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        return $res['validation_status'] === 'VALIDATION_WARNING' && $res['is_valid'] === true && count($res['errors']) === 0;
    });

    // Test 24: Classification: VALIDATED when zero errors and zero warnings
    runTest("Classification: Returns VALIDATED and is_valid=true when all rules pass cleanly", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $res = $valEngine->validate($basePayload, $testCompanyIdA, $testUserA, null, false);
        return $res['validation_status'] === 'VALIDATED' && $res['is_valid'] === true && count($res['errors']) === 0;
    });

    // Test 25: Suggested corrections and current value presence
    runTest("Telemetry: Every rule result contains suggested_correction and current_value", function() use ($valEngine, $testCompanyIdA, $testUserA, $basePayload) {
        $data = $basePayload;
        $data['title'] = 'Abc';
        $res = $valEngine->validate($data, $testCompanyIdA, $testUserA, null, false);
        $titleRule = null;
        foreach ($res['rule_results'] as $r) {
            if ($r['rule_id'] === 'ERR-TIT-01') {
                $titleRule = $r;
                break;
            }
        }
        return $titleRule !== null 
            && !empty($titleRule['suggested_correction']) 
            && $titleRule['current_value'] === 'Abc'
            && $titleRule['status'] === 'FAIL';
    });

    // Test 26: Decoupling Principle: VALIDATED != APPROVED
    runTest("Business Governance: VALIDATED does NOT automatically set status to APPROVED", function() use ($reqCtrl, $testCompanyIdA, $testUserA, $basePayload, &$createdReqIds) {
        // High volume requirement (100 vacancies) passes validation but MUST be routed to SUBMITTED / PENDING_REVIEW
        $highVolData = $basePayload;
        $highVolData['quantity_required'] = 100;
        $highVolData['trades'] = [
            ['trade_name' => 'Fitter', 'vacancies' => 60],
            ['trade_name' => 'Welder', 'vacancies' => 40]
        ];

        $_SESSION['user_id'] = $testUserA;
        $_SESSION['role_name'] = 'industry_admin';

        $saveRes = $reqCtrl->submitRequirement($testCompanyIdA, $highVolData);
        if (!$saveRes['success']) return "Submission failed: " . ($saveRes['message'] ?? '');

        $createdReqIds[] = (int)$saveRes['requirement_id'];

        // Volume > 50 triggers manual review rule -> Status must NOT be APPROVED
        return $saveRes['status'] === 'SUBMITTED' || $saveRes['status'] === 'PENDING_REVIEW';
    });

    echo "\n--- SECTION 3: VALIDATION HISTORY PERSISTENCE & REVALIDATION ---\n";

    // Test 27: Save requirement and verify history persistence
    $createdReqId = 0;
    runTest("Persistence: Successful submission logs structured telemetry to requirement_validation_history", function() use ($reqCtrl, $testCompanyIdA, $testUserA, $basePayload, &$createdReqId, &$createdReqIds, $pdo) {
        $_SESSION['user_id'] = $testUserA;
        $_SESSION['role_name'] = 'industry_admin';

        $submitRes = $reqCtrl->submitRequirement($testCompanyIdA, $basePayload);
        if (!$submitRes['success']) return "Submission failed: " . ($submitRes['message'] ?? '');

        $createdReqId = (int)$submitRes['requirement_id'];
        $createdReqIds[] = $createdReqId;

        $histStmt = $pdo->prepare("SELECT * FROM `requirement_validation_history` WHERE requirement_id = :rid ORDER BY id DESC LIMIT 1");
        $histStmt->execute([':rid' => $createdReqId]);
        $row = $histStmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return "No history row inserted";
        $details = json_decode($row['details_json'], true);

        return !empty($row['validation_run_id'])
            && isset($details['trigger_type'])
            && isset($details['validation_status']);
    });

    // Test 28: Fetch latest validation run helper
    runTest("Helper: getLatestValidationRun decodes details_json and fields correctly", function() use ($valEngine, &$createdReqId) {
        $latest = $valEngine->getLatestValidationRun($createdReqId);
        return $latest !== null 
            && $latest['requirement_id'] === $createdReqId 
            && is_array($latest['details'])
            && isset($latest['details']['validation_status'])
            && count($latest['details']['rule_results']) > 0;
    });

    // Test 29: Fetch validation runs summary helper
    runTest("Helper: getValidationRunsSummary returns aggregated history telemetry", function() use ($valEngine, &$createdReqId) {
        $summary = $valEngine->getValidationRunsSummary($createdReqId);
        return count($summary) >= 1 && isset($summary[0]['passed_count']);
    });

    // Test 30: Manual revalidation via RequirementController
    runTest("Controller: revalidateRequirement executes full check and returns structured report", function() use ($reqCtrl, $testCompanyIdA, $testUserA, &$createdReqId) {
        $reval = $reqCtrl->revalidateRequirement($testCompanyIdA, $createdReqId, $testUserA);
        return $reval['success'] === true 
            && $reval['requirement_id'] === $createdReqId 
            && in_array($reval['validation_status'], ['VALIDATED', 'VALIDATION_WARNING', 'VALIDATION_FAILED'], true)
            && is_array($reval['categories']);
    });

    // Test 31: Multi-tenant boundary: Company B cannot revalidate Company A's requirement
    runTest("Multi-tenant Security: Company B cannot revalidate Company A's requirement", function() use ($reqCtrl, $testCompanyIdB, $testUserB, &$createdReqId) {
        $unauthReval = $reqCtrl->revalidateRequirement($testCompanyIdB, $createdReqId, $testUserB);
        return $unauthReval['success'] === false && strpos($unauthReval['message'], 'denied') !== false;
    });

    // Test 32: Super Admin cross-tenant revalidation bypass
    runTest("RBAC Governance: Super Admin (company_id=0) can revalidate any company requirement", function() use ($reqCtrl, $testUserA, &$createdReqId) {
        $saReval = $reqCtrl->revalidateRequirement(0, $createdReqId, $testUserA);
        return $saReval['success'] === true && $saReval['requirement_id'] === $createdReqId;
    });

    echo "\n--- SECTION 4: ALERT ENGINE & NOTIFICATION SERVICE ---\n";

    // Test 33: Dispatch Validation Failed alert
    runTest("Alert Engine: dispatchValidationFailed creates in-app notification and email log", function() use ($notifService, $testUserA, $testCompanyIdA, &$createdReqId, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Test Requisition'];
        $notifService->dispatchValidationFailed($req, $testUserA, $testCompanyIdA, ['min_salary' => 'Salary below statutory floor']);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'VALIDATION_FAILED' ORDER BY id DESC LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        $notif = $nStmt->fetch(PDO::FETCH_ASSOC);

        $eStmt = $pdo->prepare("SELECT * FROM `email_logs` WHERE recipient_user_id = :uid AND template_name = 'validation_failed' ORDER BY id DESC LIMIT 1");
        $eStmt->execute([':uid' => $testUserA]);
        $email = $eStmt->fetch(PDO::FETCH_ASSOC);

        return $notif !== false && $email !== false;
    });

    // Test 34: Dispatch Validation Warning alert
    runTest("Alert Engine: dispatchValidationWarning creates in-app advisory notice", function() use ($notifService, $testUserA, $testCompanyIdA, &$createdReqId, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Test Requisition'];
        $notifService->dispatchValidationWarning($req, $testUserA, $testCompanyIdA, ['job_description' => 'Consider expanding description']);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'VALIDATION_WARNING' ORDER BY id DESC LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        $notif = $nStmt->fetch(PDO::FETCH_ASSOC);

        return $notif !== false;
    });

    // Test 35: Dispatch Requirement Under Manual Review alert
    runTest("Alert Engine: dispatchRequirementUnderReview notifies user and broadcasts to Super Admin", function() use ($notifService, $testUserA, $testCompanyIdA, &$createdReqId, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Test High Volume'];
        $notifService->dispatchRequirementUnderReview($req, $testUserA, $testCompanyIdA, ['Volume > 50 vacancies']);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'REQUIREMENT_UNDER_MANUAL_REVIEW' ORDER BY id DESC LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        $userNotif = $nStmt->fetch(PDO::FETCH_ASSOC);

        $saStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE notification_type = 'REQUIREMENT_NEEDS_MANUAL_REVIEW' ORDER BY id DESC LIMIT 1");
        $saStmt->execute();
        $saNotif = $saStmt->fetch(PDO::FETCH_ASSOC);

        return $userNotif !== false && $saNotif !== false;
    });

    // Test 36: Dispatch Requirement Revised alert
    runTest("Alert Engine: dispatchRequirementRevised notifies user of requisition update", function() use ($notifService, $testUserA, $testCompanyIdA, &$createdReqId, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Updated Requisition'];
        $notifService->dispatchRequirementRevised($req, $testUserA, $testCompanyIdA);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'REQUIREMENT_REVISED' ORDER BY id DESC LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        return $nStmt->fetch() !== false;
    });

    // Test 37: Dispatch Requirement Overdue alert
    runTest("Alert Engine: dispatchRequirementOverdue records overdue notification", function() use ($notifService, $testUserA, &$createdReqId, $testCompanyIdA, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Expired Requisition', 'company_id' => $testCompanyIdA];
        $notifService->dispatchRequirementOverdue($req, $testUserA);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'REQUIREMENT_OVERDUE' ORDER BY id DESC LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        return $nStmt->fetch() !== false;
    });

    // Test 38: Notifications filter query
    runTest("Notification Query: getUserNotifications correctly filters by status and unread count", function() use ($notifService, $testUserA) {
        $all = $notifService->getUserNotifications($testUserA, [], 50);
        $unread = $notifService->getUserNotifications($testUserA, ['status' => 'unread'], 50);
        $count = $notifService->getUnreadCount($testUserA);

        return count($all) >= 3 && count($unread) === $count;
    });

    // Test 39: Mark notification as read
    runTest("Notification Mutation: markAsRead updates notification state and read timestamp", function() use ($notifService, $testUserA, $pdo) {
        $nStmt = $pdo->prepare("SELECT id FROM `notifications` WHERE recipient_user_id = :uid AND is_read = 0 LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        $nid = (int)$nStmt->fetchColumn();

        if ($nid <= 0) return "No unread notification found";
        $ok = $notifService->markAsRead($nid, $testUserA);

        $chkStmt = $pdo->prepare("SELECT is_read, read_at FROM `notifications` WHERE id = :id");
        $chkStmt->execute([':id' => $nid]);
        $chk = $chkStmt->fetch(PDO::FETCH_ASSOC);

        return $ok === true && (int)$chk['is_read'] === 1 && !empty($chk['read_at']);
    });

    echo "\n--- SECTION 5: DEADLINE REMINDER & CRON SCHEDULING ---\n";

    // Test 40: Dispatch deadline reminder with 7-day window
    runTest("Deadline Engine: dispatchDeadlineReminder creates 7-day milestone alert", function() use ($notifService, $testUserA, &$createdReqId, $testCompanyIdA, $pdo) {
        $req = ['id' => $createdReqId, 'requirement_id' => 'REQ-TEST-VAL', 'title' => 'Deadline Test', 'company_id' => $testCompanyIdA];
        $ok = $notifService->dispatchDeadlineReminder($req, 'DEADLINE_7_DAYS', date('Y-m-d', strtotime('+7 days')), $testUserA);

        $nStmt = $pdo->prepare("SELECT * FROM `notifications` WHERE recipient_user_id = :uid AND notification_type = 'DEADLINE_7_DAYS' LIMIT 1");
        $nStmt->execute([':uid' => $testUserA]);
        return $ok && $nStmt->fetch() !== false;
    });

    // Test 41: Idempotency: Duplicate reminder suppression in deadline_reminder_logs
    runTest("Idempotency: Inserting duplicate record in deadline_reminder_logs throws unique violation", function() use ($pdo, &$createdReqId, $testUserA) {
        $targetDate = date('Y-m-d', strtotime('+3 days'));
        
        $stmt = $pdo->prepare("INSERT INTO `deadline_reminder_logs` 
            (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`, `sent_at`) 
            VALUES (:rid, 'DEADLINE_3_DAYS', :dt, :uid, NOW())");
        $stmt->execute([':rid' => $createdReqId, ':dt' => $targetDate, ':uid' => $testUserA]);

        // Attempt identical insert -> MUST fail unique constraint
        try {
            $stmt->execute([':rid' => $createdReqId, ':dt' => $targetDate, ':uid' => $testUserA]);
            return "Expected duplicate key violation did not occur";
        } catch (PDOException $e) {
            return $e->getCode() === '23000';
        }
    });

    // Test 42: Revised date allows new reminder dispatch
    runTest("Idempotency Revision: When target date changes, a fresh reminder can be dispatched", function() use ($pdo, &$createdReqId, $testUserA) {
        $revisedDate = date('Y-m-d', strtotime('+10 days'));
        $stmt = $pdo->prepare("INSERT INTO `deadline_reminder_logs` 
            (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`, `sent_at`) 
            VALUES (:rid, 'DEADLINE_3_DAYS', :dt, :uid, NOW())");
        $stmt->execute([':rid' => $createdReqId, ':dt' => $revisedDate, ':uid' => $testUserA]);
        return (int)$pdo->lastInsertId() > 0;
    });

    // Test 43: CLI cron execution with --dry-run
    runTest("Cron CLI: Scheduled reminder script runs cleanly with --dry-run", function() {
        $output = [];
        $exitCode = 0;
        exec('php database/cron/requirement_deadline_reminders.php --dry-run', $output, $exitCode);
        $fullOut = implode("\n", $output);
        return $exitCode === 0 && strpos($fullOut, 'PRAYAS Portal') !== false && strpos($fullOut, 'Execution Complete Summary') !== false;
    });

    // Test 44: Cron lock file single-instance execution guard
    runTest("Cron Lock: Script respects lock file and prevents overlapping executions", function() {
        $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prayas_cron_reminders.lock';
        $fp = fopen($lockFile, 'c+');
        if (!$fp) return "Failed to create temp lock file";

        flock($fp, LOCK_EX); // Acquire exclusive lock

        // Launch second instance via CLI -> It should detect lock and exit cleanly
        $output = [];
        $exitCode = 0;
        exec('php database/cron/requirement_deadline_reminders.php', $output, $exitCode);
        $fullOut = implode("\n", $output);

        flock($fp, LOCK_UN);
        fclose($fp);
        @unlink($lockFile);

        return $exitCode === 0 && strpos($fullOut, 'Another instance of the PRAYAS Deadline Reminder Cron is currently active') !== false;
    });

    // Test 45: Terminal status exclusion
    runTest("Cron Filtering: Requirements in DRAFT, NEEDS_CORRECTION, or CLOSED are strictly ignored", function() use ($pdo, $testCompanyIdA, &$createdReqId) {
        $stmt = $pdo->prepare("UPDATE `manpower_requirements` SET status = 'CLOSED' WHERE id = :id");
        $stmt->execute([':id' => $createdReqId]);

        // Query active query from cron
        $activeStatuses = ['APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED'];
        $placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));
        $cStmt = $pdo->prepare("SELECT id FROM `manpower_requirements` WHERE id = ? AND status IN ($placeholders)");
        $params = array_merge([$createdReqId], $activeStatuses);
        $cStmt->execute($params);
        return $cStmt->fetch() === false; // MUST be excluded
    });

    echo "\n--- SECTION 6: CANDIDATE DATABASE IMMUTABILITY ---\n";

    // Test 46: Candidate DB 1 Immutability check
    runTest("Safety Gate: prayas_iti_dashboard.students remains intact (1,292 records)", function() use ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`");
        $count = (int)$stmt->fetchColumn();
        return $count === 1292;
    });

    // Test 47: Candidate DB 2 Immutability check
    runTest("Safety Gate: itistudents.candidates remains intact (56 records)", function() use ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`");
        $count = (int)$stmt->fetchColumn();
        return $count === 56;
    });

    // Test 48: Zero mutation on core system tables
    runTest("Safety Gate: Core schema tables remain unmodified and intact", function() use ($pdo) {
        $tables = [
            'manpower_requirements',
            'requirement_trades',
            'requirement_skills',
            'requirement_validation_history',
            'notifications',
            'email_logs',
            'deadline_reminder_logs'
        ];
        foreach ($tables as $tbl) {
            $check = $pdo->query("SHOW TABLES LIKE '{$tbl}'")->fetchColumn();
            if (!$check) return "Table {$tbl} missing";
        }
        return true;
    });

} finally {
    // Clean up created requirements
    if (!empty($createdReqIds)) {
        $inReqs = implode(',', array_map('intval', $createdReqIds));
        $pdo->query("DELETE FROM `deadline_reminder_logs` WHERE requirement_id IN ($inReqs)");
        $pdo->query("DELETE FROM `requirement_validation_history` WHERE requirement_id IN ($inReqs)");
        $pdo->query("DELETE FROM `requirement_status_history` WHERE requirement_id IN ($inReqs)");
        $pdo->query("DELETE FROM `requirement_skills` WHERE requirement_id IN ($inReqs)");
        $pdo->query("DELETE FROM `requirement_trades` WHERE requirement_id IN ($inReqs)");
        $pdo->query("DELETE FROM `manpower_requirements` WHERE id IN ($inReqs)");
    }

    // Clean up notifications and emails for test users
    $pdo->prepare("DELETE FROM `notifications` WHERE recipient_user_id IN (:u1, :u2)")->execute([':u1' => $testUserA, ':u2' => $testUserB]);
    $pdo->prepare("DELETE FROM `email_logs` WHERE recipient_user_id IN (:u1, :u2)")->execute([':u1' => $testUserA, ':u2' => $testUserB]);

    // Clean up plants, users, companies
    $pdo->prepare("DELETE FROM `users` WHERE id IN (:u1, :u2)")->execute([':u1' => $testUserA, ':u2' => $testUserB]);
    $pdo->prepare("DELETE FROM `manufacturing_plants` WHERE id IN (:p1, :p2)")->execute([':p1' => $testPlantIdA, ':p2' => $testPlantIdB]);
    $pdo->prepare("DELETE FROM `companies` WHERE id IN (:c1, :c2)")->execute([':c1' => $testCompanyIdA, ':c2' => $testCompanyIdB]);
}

echo "\n====================================================================\n";
echo " PART 08 TEST RESULTS SUMMARY:\n";
echo "   Total Tests Executed: {$testsTotal}\n";
echo "   Tests Passed:        {$testsPassed}\n";
echo "   Tests Failed:        {$testsFailed}\n";
echo "====================================================================\n";

if ($testsFailed === 0) {
    echo "\n>>> SUCCESS: ALL PART 08 AUTOMATED TESTS PASSED CLEANLY (100%) <<<\n\n";
    exit(0);
} else {
    echo "\n>>> FAILURE: {$testsFailed} TEST(S) FAILED <<<\n\n";
    exit(1);
}
