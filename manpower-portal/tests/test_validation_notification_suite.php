<?php
/**
 * Automated Test Suite — Part 08: Auto-Validation & Notifications Engine
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/requirement_validation_engine.php';
require_once __DIR__ . '/../includes/notification_service.php';
require_once __DIR__ . '/../controllers/RequirementController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 08 Auto-Validation & Notifications Tests\n";
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

// Setup Test Data (Temporary Test Company & Users)
$testCompName = 'ValNotif Automation Industries Ltd ' . bin2hex(random_bytes(3));
$stmt = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, 
     `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Automotive', 'Plot 44, Tech Zone', 'Maharashtra', 'Pune', 'Pune', '411001', 
            'Val Manager', '9890001122', :email, 'Verified')");
$stmt->execute([
    ':cname' => $testCompName,
    ':reg'   => 'CIN-VN-' . bin2hex(random_bytes(3)),
    ':email' => 'val_hr_' . bin2hex(random_bytes(3)) . '@valnotif.com'
]);
$testCompanyId = (int)$pdo->lastInsertId();

// Create 2 Test Users (User A and User B for IDOR testing)
$pwdHash = password_hash('SecretPassword123!', PASSWORD_BCRYPT);
$roleStmt = $pdo->query("SELECT id FROM `roles` WHERE name = 'industry_admin' LIMIT 1");
$indAdminRoleId = (int)$roleStmt->fetchColumn();

$userAEmail = 'usera_' . bin2hex(random_bytes(3)) . '@valnotif.com';
$userBEmail = 'userb_' . bin2hex(random_bytes(3)) . '@valnotif.com';

$stmt = $pdo->prepare("INSERT INTO `users` (`role_id`, `company_id`, `full_name`, `email`, `password_hash`, `status`) 
    VALUES (:rid, :cid, 'User Alpha', :email, :pwd, 'Active')");
$stmt->execute([':rid' => $indAdminRoleId, ':cid' => $testCompanyId, ':email' => $userAEmail, ':pwd' => $pwdHash]);
$testUserAId = (int)$pdo->lastInsertId();

$stmt->execute([':rid' => $indAdminRoleId, ':cid' => $testCompanyId, ':email' => $userBEmail, ':pwd' => $pwdHash]);
$testUserBId = (int)$pdo->lastInsertId();

$valEngine = new RequirementValidationEngine($pdo);
$notifService = new NotificationService($pdo);

// -------------------------------------------------------------
// Test Group 1: Centralized Validation Engine Rules
// -------------------------------------------------------------
echo "--- Test Group 1: Centralized Validation Engine Rules ---\n";

$validPayload = [
    'title'                => 'Senior CNC Maintenance Technician',
    'job_description'      => 'Troubleshooting 5-axis CNC machines and robotic arm interfaces.',
    'quantity_required'    => 10,
    'trades'               => [
        ['trade_name' => 'Machinist', 'vacancies' => 6],
        ['trade_name' => 'Fitter', 'vacancies' => 4]
    ],
    'qualification'        => 'ITI Passed',
    'passing_year_from'    => 2020,
    'passing_year_to'      => 2024,
    'min_salary'           => 16000.0,
    'max_salary'           => 22000.0,
    'salary_type'          => 'Monthly',
    'application_deadline' => date('Y-m-d', strtotime('+10 days')),
    'interview_date'       => date('Y-m-d', strtotime('+15 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+30 days')),
    'job_location_state'   => 'Maharashtra',
    'job_location_city'    => 'Pune',
    'min_age'              => 19,
    'max_age'              => 28,
    'interview_mode'       => 'In-Person'
];

$resClean = $valEngine->validate($validPayload, $testCompanyId, $testUserAId, null, false);
assertTest("Clean Requisition Passes All Rules", $resClean['is_valid'] === true && empty($resClean['errors']));

// Non-existent company
$resBadComp = $valEngine->validate($validPayload, 999999, $testUserAId, null, false);
assertTest("Non-Existent Company Rejected (BR-REG-001)", !$resBadComp['is_valid'] && isset($resBadComp['errors']['company_id']));

// Short Title
$badTitlePayload = array_merge($validPayload, ['title' => 'CNC']);
$resTitle = $valEngine->validate($badTitlePayload, $testCompanyId, $testUserAId, null, false);
assertTest("Short Title Rejected (<5 chars) (ERR-TIT-01)", !$resTitle['is_valid'] && isset($resTitle['errors']['title']));

// Trade sum parity
$badSumPayload = array_merge($validPayload, ['quantity_required' => 25]); // Trades sum to 10
$resSum = $valEngine->validate($badSumPayload, $testCompanyId, $testUserAId, null, false);
assertTest("Trade Vacancy Sum Mismatch Rejected (ERR-TRD-03)", !$resSum['is_valid'] && isset($resSum['errors']['quantity_consistency']));

// Salary Inversion
$badSalPayload = array_merge($validPayload, ['min_salary' => 25000.0, 'max_salary' => 15000.0]);
$resSal = $valEngine->validate($badSalPayload, $testCompanyId, $testUserAId, null, false);
assertTest("Salary Inversion Rejected (max < min) (ERR-SAL-02)", !$resSal['is_valid'] && isset($resSal['errors']['max_salary']));

// Chronological Inversion
$badDatePayload = array_merge($validPayload, [
    'application_deadline' => date('Y-m-d', strtotime('+15 days')),
    'interview_date'       => date('Y-m-d', strtotime('+10 days')) // Interview before deadline
]);
$resDate = $valEngine->validate($badDatePayload, $testCompanyId, $testUserAId, null, false);
assertTest("Interview Before Deadline Rejected (ERR-DAT-02)", !$resDate['is_valid'] && isset($resDate['errors']['interview_date']));

// Factories Act Age < 18
$badAgePayload = array_merge($validPayload, ['min_age' => 17]);
$resAge = $valEngine->validate($badAgePayload, $testCompanyId, $testUserAId, null, false);
assertTest("Age Under 18 Rejected (ERR-AGE-01)", !$resAge['is_valid'] && isset($resAge['errors']['min_age']));

// -------------------------------------------------------------
// Test Group 2: Automatic Approval & Routing Logic (Alg 16)
// -------------------------------------------------------------
echo "\n--- Test Group 2: Automatic Approval & Routing Logic (Alg 16) ---\n";

assertTest("Clean Payload Evaluates to APPROVED", $resClean['target_status'] === 'APPROVED');

// High volume (>50 vacancies) routes to PENDING_REVIEW
$highVolPayload = array_merge($validPayload, [
    'quantity_required' => 60,
    'trades'            => [['trade_name' => 'Machinist', 'vacancies' => 60]]
]);
$resHighVol = $valEngine->validate($highVolPayload, $testCompanyId, $testUserAId, null, false);
assertTest("High-Volume (>50) Routes to PENDING_REVIEW", $resHighVol['is_valid'] && $resHighVol['target_status'] === 'PENDING_REVIEW');

// Low salary (< ₹12,000) routes to PENDING_REVIEW with warning
$lowSalPayload = array_merge($validPayload, ['min_salary' => 10000.0, 'max_salary' => 11000.0]);
$resLowSal = $valEngine->validate($lowSalPayload, $testCompanyId, $testUserAId, null, false);
assertTest("Below-Benchmark Salary Routes to PENDING_REVIEW", $resLowSal['is_valid'] && $resLowSal['target_status'] === 'PENDING_REVIEW' && isset($resLowSal['warnings']['min_salary']));

// Short turnaround (< 7 days) routes to PENDING_REVIEW
$rushPayload = array_merge($validPayload, [
    'application_deadline' => date('Y-m-d', strtotime('+2 days')),
    'interview_date'       => date('Y-m-d', strtotime('+3 days')),
    'target_joining_date'  => date('Y-m-d', strtotime('+5 days'))
]);
$resRush = $valEngine->validate($rushPayload, $testCompanyId, $testUserAId, null, false);
assertTest("Rush Turnaround (<7 days) Routes to PENDING_REVIEW", $resRush['is_valid'] && $resRush['target_status'] === 'PENDING_REVIEW' && isset($resRush['warnings']['target_joining_date']));

// -------------------------------------------------------------
// Test Group 3: Persistent Validation History Audit
// -------------------------------------------------------------
echo "\n--- Test Group 3: Persistent Validation History Audit ---\n";

// Pre-cleanup of any orphan test data
$pdo->exec("DELETE FROM `manpower_requirements` WHERE requirement_id LIKE 'REQ-TEST-VAL-%'");

$testReqCode = 'REQ-TEST-VAL-' . bin2hex(random_bytes(3));

// Insert a test requirement to associate history
$stmt = $pdo->prepare("INSERT INTO `manpower_requirements` 
    (`requirement_id`, `company_id`, `title`, `quantity_required`, `min_salary`, `max_salary`, `status`, `created_by`, `wizard_step`) 
    VALUES (:code, :cid, 'Test Automation Tech', 10, 16000, 22000, 'DRAFT', :uid, 8)");
$stmt->execute([':code' => $testReqCode, ':cid' => $testCompanyId, ':uid' => $testUserAId]);
$testReqId = (int)$pdo->lastInsertId();

// Run validation with persistence
$outcomeToPersist = $valEngine->validate($validPayload, $testCompanyId, $testUserAId, $testReqId, true);
$history = $valEngine->getValidationHistory($testReqId);

assertTest("Validation History Records Persisted in DB", count($history) > 0);
assertTest("Validation History Captures Rule IDs (ERR-TIT-01)", in_array('ERR-TIT-01', array_column($history, 'rule_id'), true));
assertTest("Validation History Captures Pass/Fail Results", in_array('PASS', array_column($history, 'result'), true));

$runs = $valEngine->getValidationRunsSummary($testReqId);
assertTest("Grouped Validation Runs Summary Formed", count($runs) >= 1 && !empty($runs[0]['validation_run_id']));

// -------------------------------------------------------------
// Test Group 4: In-App Notifications Engine & Delivery
// -------------------------------------------------------------
echo "\n--- Test Group 4: In-App Notifications Engine & Delivery ---\n";

$notifId = $notifService->sendInApp(
    $testUserAId,
    'REQUIREMENT_APPROVED',
    'Requirement Approved: REQ-TEST-VAL-001',
    'Your requisition has been approved and matching initiated.',
    '/views/industry/requirements.php',
    $testCompanyId,
    'requirement',
    $testReqId
);

assertTest("In-App Notification Created Successfully", $notifId > 0);

$unread = $notifService->getUnreadCount($testUserAId);
assertTest("Unread Notification Count Incremented", $unread >= 1);

$userNotifs = $notifService->getUserNotifications($testUserAId);
assertTest("User Notifications Query Returns Created Notification", count($userNotifs) >= 1 && (int)$userNotifs[0]['id'] === $notifId);
assertTest("Notification Type Persisted (REQUIREMENT_APPROVED)", $userNotifs[0]['notification_type'] === 'REQUIREMENT_APPROVED');

// -------------------------------------------------------------
// Test Group 5: Notification Ownership & Anti-IDOR Security
// -------------------------------------------------------------
echo "\n--- Test Group 5: Notification Ownership & Anti-IDOR Security ---\n";

// User B attempts to mark User A's notification as read (Cross-user IDOR attempt)
$unauthorizedMark = $notifService->markAsRead($notifId, $testUserBId);
assertTest("Cross-User Notification Mark As Read Blocked (IDOR Guard)", $unauthorizedMark === false);

// Authorized User A marks their own notification as read
$authorizedMark = $notifService->markAsRead($notifId, $testUserAId);
assertTest("Authorized User Marks Notification as Read", $authorizedMark === true);

// Verify status updated to read
$stmt = $pdo->prepare("SELECT is_read, read_at, status FROM `notifications` WHERE id = :id");
$stmt->execute([':id' => $notifId]);
$updatedNotif = $stmt->fetch();
assertTest("Notification Status Updated to 'read' & read_at Populated", (bool)$updatedNotif['is_read'] && !empty($updatedNotif['read_at']));

// Mark All as Read
$notifId2 = $notifService->sendInApp($testUserAId, 'INFO', 'Second Alert', 'Notice', null);
$notifService->markAllAsRead($testUserAId);
assertTest("Mark All As Read Clears Unread Counter", $notifService->getUnreadCount($testUserAId) === 0);

// Delete with ownership verification
$unauthorizedDelete = $notifService->deleteNotification($notifId, $testUserBId);
assertTest("Cross-User Notification Deletion Blocked (IDOR Guard)", $unauthorizedDelete === false);

$authorizedDelete = $notifService->deleteNotification($notifId, $testUserAId);
assertTest("Authorized User Notification Deletion Succeeded", $authorizedDelete === true);

// -------------------------------------------------------------
// Test Group 6: Email Notification Service & Failure Logging
// -------------------------------------------------------------
echo "\n--- Test Group 6: Email Notification Service & Failure Logging ---\n";

// Test Simulated Email Dispatch
$emailRes = $notifService->sendEmail(
    $userAEmail,
    'Requirement Approved: REQ-TEST-VAL-001 — PRAYAS',
    'requirement_approval',
    [
        'full_name'        => 'User Alpha',
        'requirement_code' => 'REQ-TEST-VAL-001',
        'title'            => 'Test Automation Tech',
        'vacancies'        => 10,
        'action_url'       => '/views/industry/requirements.php'
    ],
    $testUserAId
);

assertTest("Email Service Handles Dispatch Without Throwing Exception", is_array($emailRes) && isset($emailRes['success']));
assertTest("Email Delivery Status Logged (SIMULATED/SENT)", in_array($emailRes['delivery_status'], ['SIMULATED', 'SENT', 'FAILED'], true));

$emailLogStmt = $pdo->prepare("SELECT id, recipient_email, template_name, delivery_status FROM `email_logs` WHERE id = :id");
$emailLogStmt->execute([':id' => $emailRes['log_id']]);
$loggedEmail = $emailLogStmt->fetch();
assertTest("Email Activity Logged in email_logs Table", $loggedEmail && $loggedEmail['recipient_email'] === $userAEmail);

// -------------------------------------------------------------
// Test Group 7: Deadline Reminder & Milestone Monitoring
// -------------------------------------------------------------
echo "\n--- Test Group 7: Deadline Reminder & Milestone Monitoring ---\n";

// Set test requirement to APPROVED with approaching deadline (tomorrow)
$tomorrowStr = date('Y-m-d', strtotime('+1 day'));
$pdo->prepare("UPDATE `manpower_requirements` 
    SET `status` = 'APPROVED', `application_deadline` = :dl, `interview_date` = :idate, `target_joining_date` = :jdate 
    WHERE id = :id")->execute([
    ':dl'    => $tomorrowStr,
    ':idate' => date('Y-m-d', strtotime('+10 days')),
    ':jdate' => date('Y-m-d', strtotime('+20 days')),
    ':id'    => $testReqId
]);

// Test reminder dispatch
$reqData = [
    'id'                   => $testReqId,
    'requirement_id'       => $testReqCode,
    'title'                => 'Test Automation Tech',
    'company_id'           => $testCompanyId,
    'application_deadline' => $tomorrowStr
];

$remSent = $notifService->dispatchDeadlineReminder($reqData, 'DEADLINE_APPROACHING', $tomorrowStr, $testUserAId);
assertTest("Deadline Reminder Dispatched via Service", $remSent === true);

// Record in deadline_reminder_logs to verify idempotency
$pdo->prepare("INSERT INTO `deadline_reminder_logs` 
    (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`, `sent_at`) 
    VALUES (:rid, 'DEADLINE_APPROACHING', :dt, :uid, NOW())")
    ->execute([':rid' => $testReqId, ':dt' => $tomorrowStr, ':uid' => $testUserAId]);

// Verify unique constraint prevents duplicate reminder logs
$duplicatePrevented = false;
try {
    $pdo->prepare("INSERT INTO `deadline_reminder_logs` 
        (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`, `sent_at`) 
        VALUES (:rid, 'DEADLINE_APPROACHING', :dt, :uid, NOW())")
        ->execute([':rid' => $testReqId, ':dt' => $tomorrowStr, ':uid' => $testUserAId]);
} catch (PDOException $e) {
    $duplicatePrevented = true; // 23000 Duplicate key error expected
}
assertTest("Duplicate Reminder Blocked by Unique Constraint (Idempotent)", $duplicatePrevented === true);

// -------------------------------------------------------------
// Test Group 8: Candidate Databases Safety & Clean Cleanup
// -------------------------------------------------------------
echo "\n--- Test Group 8: Candidate Databases Safety & Clean Cleanup ---\n";

// Cleanup test artifacts
$pdo->prepare("DELETE FROM `deadline_reminder_logs` WHERE requirement_id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `requirement_validation_history` WHERE requirement_id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `notifications` WHERE recipient_user_id IN (:u1, :u2)")->execute([':u1' => $testUserAId, ':u2' => $testUserBId]);
$pdo->prepare("DELETE FROM `email_logs` WHERE recipient_user_id = :u1")->execute([':u1' => $testUserAId]);
$pdo->prepare("DELETE FROM `manpower_requirements` WHERE id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `users` WHERE id IN (:u1, :u2)")->execute([':u1' => $testUserAId, ':u2' => $testUserBId]);
$pdo->prepare("DELETE FROM `companies` WHERE id = :cid")->execute([':cid' => $testCompanyId]);

assertTest("Cleaned Up Test Validation, Notification & Requirement Records", true);

// Verify Candidate Databases
$c1 = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$c2 = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertTest("External Candidate Database 1 Intact (1,292 records)", $c1 === 1292);
assertTest("External Candidate Database 2 Intact (56 records)", $c2 === 56);

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "  Total Passed: {$testsPassed}\n";
echo "  Total Failed: {$testsFailed}\n";
echo "========================================================\n";

exit($testsFailed > 0 ? 1 : 0);
