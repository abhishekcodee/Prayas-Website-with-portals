<?php
/**
 * Automated Test Suite — Part 10: Recruitment Pipeline Management (Screening to Joining)
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PipelineController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 10 Recruitment Pipeline Test Suite\n";
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

$pipelineCtrl = new PipelineController($pdo);

// -------------------------------------------------------------
// Test Group 1: Database Architecture & Schema Integrity
// -------------------------------------------------------------
echo "--- Test Group 1: Pipeline Database Schema & Architecture ---\n";

function checkTableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
    $stmt->execute([':t' => $tableName]);
    return (int)$stmt->fetchColumn() > 0;
}

assertTest("Table 'recruitment_stage_history' exists", checkTableExists($pdo, 'recruitment_stage_history'));
assertTest("Table 'candidate_screening' exists", checkTableExists($pdo, 'candidate_screening'));
assertTest("Table 'interviews' exists", checkTableExists($pdo, 'interviews'));
assertTest("Table 'interview_feedback' exists", checkTableExists($pdo, 'interview_feedback'));
assertTest("Table 'selections' exists", checkTableExists($pdo, 'selections'));
assertTest("Table 'offer_letters' exists", checkTableExists($pdo, 'offer_letters'));
assertTest("Table 'joinings' exists", checkTableExists($pdo, 'joinings'));

// Check candidate_matches status column type
$colStmt = $pdo->query("SHOW COLUMNS FROM `candidate_matches` LIKE 'status'");
$statusCol = $colStmt->fetch(PDO::FETCH_ASSOC);
assertTest("candidate_matches.status supports 'Screened'", str_contains($statusCol['Type'], "'Screened'"));
assertTest("candidate_matches.status supports 'Offer Released'", str_contains($statusCol['Type'], "'Offer Released'"));
assertTest("candidate_matches.status supports 'Offer Accepted'", str_contains($statusCol['Type'], "'Offer Accepted'"));
assertTest("candidate_matches.status supports 'Joining Pending'", str_contains($statusCol['Type'], "'Joining Pending'"));
assertTest("candidate_matches.status supports 'No Show'", str_contains($statusCol['Type'], "'No Show'"));

// -------------------------------------------------------------
// Test Group 2: Fixture Setup (Isolated Test Requisition & Candidate Match)
// -------------------------------------------------------------
echo "\n--- Test Group 2: Test Fixture Provisioning ---\n";

// 2.1 Fetch or create test recruiter user
$adminUser = $pdo->query("SELECT id FROM `users` WHERE `status` = 'Active' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$testUserId = (int)($adminUser['id'] ?? 1);

// 2.2 Create test company
$testCompName = 'PRAYAS Pipeline Test Tech ' . bin2hex(random_bytes(3));
$stmt = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, 
     `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Automation & Robotics', 'Chakan MIDC', 'Maharashtra', 'Pune', 'Pune', '410501', 
            'Rohit Sharma', '9876543299', :email, 'Verified')");
$stmt->execute([
    ':cname' => $testCompName,
    ':reg'   => 'U99999MH' . bin2hex(random_bytes(3)),
    ':email' => 'pipeline_' . bin2hex(random_bytes(3)) . '@prayas.com'
]);
$testCompId = (int)$pdo->lastInsertId();
assertTest("Test Company Provisioned (ID: {$testCompId})", $testCompId > 0);

// 2.3 Create test requisition
$reqCode = 'REQ-TEST-PIPE-' . bin2hex(random_bytes(3));
$pdo->prepare("INSERT INTO `manpower_requirements` 
    (`company_id`, `requirement_id`, `title`, `job_description`, `quantity_required`, `min_salary`, `max_salary`, `status`, `created_by`, `created_at`) 
    VALUES 
    (:cid, :code, 'Junior Robotics Assembly Technician', 'Hands-on assembly of robotic cells', 5, 18000, 24000, 'APPROVED', :uid, NOW())")
    ->execute([':cid' => $testCompId, ':code' => $reqCode, ':uid' => $testUserId]);
$testReqId = (int)$pdo->lastInsertId();
assertTest("Test Requisition Created: {$reqCode} (ID: {$testReqId})", $testReqId > 0);

// 2.4 Create candidate match fixture
$pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source_id`, `candidate_source`, `candidate_name`, `trade`, `passing_year`, `match_score`, `status`, `is_shared_with_industry`, `matched_at`) 
    VALUES 
    (:rid, 101, 'prayas_dashboard', 'Kishore K. Patil', 'Fitter', '2024', 88.5, 'Matched', 0, NOW())")
    ->execute([':rid' => $testReqId]);
$testMatchId = (int)$pdo->lastInsertId();
assertTest("Test Candidate Match Created (Match ID: {$testMatchId})", $testMatchId > 0);

// -------------------------------------------------------------
// Test Group 3: Candidate Screening (Stage 3)
// -------------------------------------------------------------
echo "\n--- Test Group 3: Candidate Screening Assessment ---\n";

$screeningData = [
    'screening_outcome'      => 'Passed',
    'willing_to_relocate'    => 'Yes',
    'candidate_availability' => 'Within 7 Days',
    'expected_salary'        => '20000',
    'verified_trade_skills'  => 1,
    'screening_notes'        => 'Strong grasp of mechanical fitting and blueprint reading.'
];

$screenRes = $pipelineCtrl->recordScreening($testMatchId, $screeningData, $testUserId);
assertTest("recordScreening() executes successfully", $screenRes['success'] === true);

// Verify candidate_matches status changed to Screened
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Screened'", $matchCheck['status'] === 'Screened');

// Verify candidate_screening record exists
$screenRow = $pdo->query("SELECT * FROM `candidate_screening` WHERE match_id = {$testMatchId} ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest("Screening row persisted with outcome 'Passed'", $screenRow !== false && $screenRow['screening_outcome'] === 'Passed');
assertTest("Screening records candidate relocation willingness", $screenRow['willing_to_relocate'] === 'Yes');

// Verify stage history audit log
$history = $pipelineCtrl->getStageHistory($testMatchId);
assertTest("Stage history logged first transition (Matched -> Screened)", count($history) >= 1 && $history[0]['new_stage'] === 'Screened');

// -------------------------------------------------------------
// Test Group 4: Interview Scheduling (Stage 4)
// -------------------------------------------------------------
echo "\n--- Test Group 4: Interview Scheduling ---\n";

// 4.1 Reject past dated interview
$pastInterview = [
    'interview_datetime'     => date('Y-m-d H:i:s', strtotime('-1 day')),
    'venue_or_link'          => 'Main Plant Gate 2',
    'interview_mode'         => 'In-Person',
    'interview_round'        => 'Round 1 - Technical Assessment'
];
$pastRes = $pipelineCtrl->scheduleInterview($testMatchId, $pastInterview, $testUserId);
assertTest("Rejects past-dated interview scheduling", $pastRes['success'] === false);

// 4.2 Schedule valid future interview
$futureDatetime = date('Y-m-d H:i:s', strtotime('+3 days 10:00:00'));
$validInterview = [
    'interview_datetime'      => $futureDatetime,
    'venue_or_link'           => 'Plant No. 4 Shopfloor, Chakan, Pune',
    'interview_mode'          => 'In-Person',
    'interview_round'         => 'Round 1 - Technical Assessment',
    'interviewer_name'        => 'Mahesh Deshmukh',
    'interviewer_designation' => 'Senior Production Lead',
    'interview_remarks'       => 'Practical test on milling machine'
];
$schedRes = $pipelineCtrl->scheduleInterview($testMatchId, $validInterview, $testUserId);
assertTest("scheduleInterview() executes successfully for future date", $schedRes['success'] === true);
$interviewId = (int)($schedRes['interview_id'] ?? 0);
assertTest("Interview ID returned ({$interviewId})", $interviewId > 0);

// Verify status updated to Scheduled
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Scheduled'", $matchCheck['status'] === 'Scheduled');

// Verify interviews record
$interviewRow = $pdo->query("SELECT * FROM `interviews` WHERE id = {$interviewId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Interview record persisted with status 'Scheduled'", $interviewRow !== false && $interviewRow['status'] === 'Scheduled');

// -------------------------------------------------------------
// Test Group 5: Interview Evaluation Feedback (Stage 5 & 6)
// -------------------------------------------------------------
echo "\n--- Test Group 5: Interview Evaluation Feedback ---\n";

$feedbackData = [
    'technical_rating'       => 5,
    'practical_rating'       => 4,
    'trade_knowledge_rating' => 5,
    'communication_rating'   => 4,
    'safety_rating'          => 5,
    'overall_rating'         => 5,
    'recommendation'         => 'Strongly Recommended',
    'feedback_comments'      => 'Outstanding practical skills; completed assembly test 10 minutes ahead of target.'
];

$fbRes = $pipelineCtrl->recordInterviewFeedback($interviewId, $feedbackData, $testUserId);
assertTest("recordInterviewFeedback() executes successfully", $fbRes['success'] === true);

// Verify interview status updated to Completed
$interviewCheck = $pdo->query("SELECT status FROM `interviews` WHERE id = {$interviewId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Interview status updated to 'Completed'", $interviewCheck['status'] === 'Completed');

// Verify candidate_matches stage updated to Completed
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Completed'", $matchCheck['status'] === 'Completed');

// Verify feedback table
$fbRow = $pdo->query("SELECT * FROM `interview_feedback` WHERE interview_id = {$interviewId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Interview feedback persisted with rating 5/5", $fbRow !== false && (int)$fbRow['overall_rating'] === 5);
assertTest("Interview feedback recommendation is 'Strongly Recommended'", $fbRow['recommendation'] === 'Strongly Recommended');

// -------------------------------------------------------------
// Test Group 6: Official Selection Decision (Stage 7)
// -------------------------------------------------------------
echo "\n--- Test Group 6: Candidate Selection Decision ---\n";

$selectionData = [
    'selection_status'    => 'Selected',
    'offered_designation' => 'Robotics Assembly Specialist',
    'offered_ctc'         => 22000.00,
    'offer_date'          => date('Y-m-d'),
    'target_joining_date' => date('Y-m-d', strtotime('+10 days')),
    'decision_remarks'    => 'Approved by Plant General Manager.'
];

$selRes = $pipelineCtrl->recordSelectionDecision($testMatchId, $selectionData, $testUserId);
assertTest("recordSelectionDecision() executes successfully", $selRes['success'] === true);

// Verify candidate_matches stage updated to Selected
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Selected'", $matchCheck['status'] === 'Selected');

// Verify selections row
$selRow = $pdo->query("SELECT * FROM `selections` WHERE match_id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Selection row exists with designation 'Robotics Assembly Specialist'", $selRow !== false && $selRow['offered_designation'] === 'Robotics Assembly Specialist');
$selectionId = (int)$selRow['id'];
assertTest("Selection ID retrieved ({$selectionId})", $selectionId > 0);

// -------------------------------------------------------------
// Test Group 7: Offer Letter Release (Stage 8)
// -------------------------------------------------------------
echo "\n--- Test Group 7: Offer Letter Generation & Release ---\n";

$offerData = [
    'job_designation'       => 'Robotics Assembly Specialist',
    'salary_offered'        => 22000.00,
    'salary_type'           => 'Monthly',
    'benefits_summary'      => 'PF, ESIC, Industrial Transport, Subsidized Canteen',
    'job_location'          => 'Chakan Plant Unit 4',
    'validity_date'         => date('Y-m-d', strtotime('+5 days')),
    'proposed_joining_date' => date('Y-m-d', strtotime('+10 days')),
    'offer_remarks'         => 'Formal offer letter dispatched'
];

$offerRes = $pipelineCtrl->createAndReleaseOffer($selectionId, $offerData, $testUserId);
assertTest("createAndReleaseOffer() executes successfully", $offerRes['success'] === true);
assertTest("Offer letter reference number generated", !empty($offerRes['reference_no']) && str_starts_with($offerRes['reference_no'], 'OFFER-'));

// Verify candidate_matches stage updated to Offer Released
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Offer Released'", $matchCheck['status'] === 'Offer Released');

// Verify offer_letters row
$offerRow = $pdo->query("SELECT * FROM `offer_letters` WHERE selection_id = {$selectionId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Offer letter stored with status 'Released'", $offerRow !== false && $offerRow['status'] === 'Released');
$offerId = (int)$offerRow['id'];

// -------------------------------------------------------------
// Test Group 8: Candidate Offer Response (Stage 9)
// -------------------------------------------------------------
echo "\n--- Test Group 8: Candidate Offer Acceptance ---\n";

$respRes = $pipelineCtrl->recordOfferResponse($offerId, 'Accepted', 'Candidate accepted terms telephonically and signed copy', $testUserId);
assertTest("recordOfferResponse() executes successfully with 'Accepted'", $respRes['success'] === true);

// Verify candidate_matches stage updated to Offer Accepted
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Offer Accepted'", $matchCheck['status'] === 'Offer Accepted');

// Verify offer_letters row updated
$offerRowCheck = $pdo->query("SELECT status FROM `offer_letters` WHERE id = {$offerId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Offer letter status updated to 'Accepted'", $offerRowCheck['status'] === 'Accepted');

// -------------------------------------------------------------
// Test Group 9: Ground Physical Joining Confirmation (Stage 10)
// -------------------------------------------------------------
echo "\n--- Test Group 9: Physical Joining Confirmation ---\n";

$joiningData = [
    'joining_status'       => 'Joined',
    'actual_joining_date'  => date('Y-m-d'),
    'plant_reporting_unit' => 'Robotics Assembly Cell B',
    'onboarding_remarks'   => 'Safety induction completed, biometric punch registered.'
];

$joinRes = $pipelineCtrl->confirmJoining($selectionId, $joiningData, $testUserId);
assertTest("confirmJoining() executes successfully", $joinRes['success'] === true);

// Verify candidate_matches stage updated to Joined
$matchCheck = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Candidate Match Stage updated to 'Joined'", $matchCheck['status'] === 'Joined');

// Verify joinings table
$joinRow = $pdo->query("SELECT * FROM `joinings` WHERE selection_id = {$selectionId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Joining row created with status 'Joined'", $joinRow !== false && $joinRow['joining_status'] === 'Joined');
assertTest("Joining records reporting unit 'Robotics Assembly Cell B'", $joinRow['plant_reporting_unit'] === 'Robotics Assembly Cell B');

// -------------------------------------------------------------
// Test Group 10: Complete Audit History Integrity
// -------------------------------------------------------------
echo "\n--- Test Group 10: Complete Stage Audit Trail Integrity ---\n";

$fullHistory = $pipelineCtrl->getStageHistory($testMatchId);
assertTest("Stage history contains all lifecycle steps (>= 6 transitions)", count($fullHistory) >= 6);

$stagesRecorded = array_column($fullHistory, 'new_stage');
assertTest("Audit history contains 'Screened'", in_array('Screened', $stagesRecorded, true));
assertTest("Audit history contains 'Scheduled'", in_array('Scheduled', $stagesRecorded, true));
assertTest("Audit history contains 'Completed'", in_array('Completed', $stagesRecorded, true));
assertTest("Audit history contains 'Selected'", in_array('Selected', $stagesRecorded, true));
assertTest("Audit history contains 'Offer Released'", in_array('Offer Released', $stagesRecorded, true));
assertTest("Audit history contains 'Offer Accepted'", in_array('Offer Accepted', $stagesRecorded, true));
assertTest("Audit history contains 'Joined'", in_array('Joined', $stagesRecorded, true));

// -------------------------------------------------------------
// Test Group 11: Funnel Analytics & Conversion Reports
// -------------------------------------------------------------
echo "\n--- Test Group 11: Funnel Analytics & Requisition Breakdown ---\n";

$metrics = $pipelineCtrl->getPipelineFunnelMetrics($testReqId);
assertTest("Funnel metrics calculates at least 1 total sourced", (int)$metrics['total_sourced'] >= 1);
assertTest("Funnel metrics reflects joined count >= 1", (int)$metrics['joined'] >= 1);

$reports = $pipelineCtrl->generateRecruitmentReport(['requirement_id' => $testReqId]);
assertTest("Report includes conversion_rates array", isset($reports['conversion_rates']));
assertTest("Report includes joining_conv_rate", isset($reports['conversion_rates']['joining_conv_rate']));

$breakdown = $pipelineCtrl->getRequisitionBreakdown($testCompId);
assertTest("Requisition breakdown returns test company requisitions", count($breakdown) >= 1);
assertTest("Requisition breakdown tracks demand and fulfillment", isset($breakdown[0]['quantity_required']) && isset($breakdown[0]['joined_count']));

// -------------------------------------------------------------
// Test Group 12: External Candidate DB Immutability
// -------------------------------------------------------------
echo "\n--- Test Group 12: External Candidate DB Immutability ---\n";

$countPrayas = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$countIti    = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertTest("External DB 1 (prayas_iti_dashboard.students) untouched (exact 1,292 rows)", $countPrayas === 1292);
assertTest("External DB 2 (itistudents.candidates) untouched (exact 56 rows)", $countIti === 56);

// -------------------------------------------------------------
// Teardown Test Fixtures
// -------------------------------------------------------------
echo "\n--- Teardown: Clean Test Fixtures ---\n";

try {
    $pdo->prepare("DELETE FROM `joinings` WHERE selection_id = {$selectionId}")->execute();
    $pdo->prepare("DELETE FROM `offer_letters` WHERE selection_id = {$selectionId}")->execute();
    $pdo->prepare("DELETE FROM `selections` WHERE id = {$selectionId}")->execute();
    $pdo->prepare("DELETE FROM `interview_feedback` WHERE interview_id = {$interviewId}")->execute();
    $pdo->prepare("DELETE FROM `interviews` WHERE id = {$interviewId}")->execute();
    $pdo->prepare("DELETE FROM `candidate_screening` WHERE match_id = {$testMatchId}")->execute();
    $pdo->prepare("DELETE FROM `recruitment_stage_history` WHERE match_id = {$testMatchId}")->execute();
    $pdo->prepare("DELETE FROM `candidate_matches` WHERE id = {$testMatchId}")->execute();
    $pdo->prepare("DELETE FROM `manpower_requirements` WHERE id = {$testReqId}")->execute();
    echo "  [INFO] Test fixtures safely cleaned up.\n";
} catch (Exception $e) {
    echo "  [WARN] Teardown note: " . $e->getMessage() . "\n";
}

// -------------------------------------------------------------
// Summary
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "Test Suite Results: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
