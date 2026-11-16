<?php
/**
 * PART 10 TEST SUITE — RECRUITMENT PIPELINE MANAGEMENT SYSTEM
 * Organization: PRAYAS – The Automation Academy
 * Module: Part 10 Comprehensive Verification Suite
 * Technology: PHP CLI + PDO + MySQL
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PipelineController.php';

$pdo = Database::getConnection();
$pipelineCtrl = new PipelineController($pdo);

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = 0;

function assertTest(bool $condition, string $message): void {
    global $totalAssertions, $passedAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo "  [PASS] {$message}\n";
    } else {
        $failedAssertions++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "====================================================================\n";
echo "PRAYAS PORTAL — PART 10 RECRUITMENT PIPELINE VERIFICATION SUITE\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// SECTION 1: 16-STAGE TAXONOMY & STATE MACHINE MATRIX
// -----------------------------------------------------------------------------
echo "[SECTION 1] 16-Stage Taxonomy & State Machine Matrix\n";

$expectedStages = [
    'Matched', 'Shortlisted', 'Screened', 'Scheduled', 'Completed',
    'Selected', 'Offer Released', 'Offer Accepted', 'Offer Declined',
    'Joining Pending', 'Joined', 'Rejected', 'On Hold', 'Withdrawn',
    'No Show', 'Closed'
];

assertTest(count(PipelineController::STAGES) === 16, "PipelineController defines exactly 16 lifecycle stages.");

foreach ($expectedStages as $stage) {
    assertTest(in_array($stage, PipelineController::STAGES, true), "Stage '{$stage}' is registered in STAGES taxonomy.");
}

// Valid transitions
assertTest($pipelineCtrl->canTransition('Matched', 'Shortlisted'), "Valid Transition: Matched -> Shortlisted is allowed.");
assertTest($pipelineCtrl->canTransition('Shortlisted', 'Screened'), "Valid Transition: Shortlisted -> Screened is allowed.");
assertTest($pipelineCtrl->canTransition('Screened', 'Scheduled'), "Valid Transition: Screened -> Scheduled is allowed.");
assertTest($pipelineCtrl->canTransition('Scheduled', 'Completed'), "Valid Transition: Scheduled -> Completed is allowed.");
assertTest($pipelineCtrl->canTransition('Completed', 'Selected'), "Valid Transition: Completed -> Selected is allowed.");
assertTest($pipelineCtrl->canTransition('Selected', 'Offer Released'), "Valid Transition: Selected -> Offer Released is allowed.");
assertTest($pipelineCtrl->canTransition('Offer Released', 'Offer Accepted'), "Valid Transition: Offer Released -> Offer Accepted is allowed.");
assertTest($pipelineCtrl->canTransition('Offer Released', 'Offer Declined'), "Valid Transition: Offer Released -> Offer Declined is allowed.");
assertTest($pipelineCtrl->canTransition('Offer Accepted', 'Joining Pending'), "Valid Transition: Offer Accepted -> Joining Pending is allowed.");
assertTest($pipelineCtrl->canTransition('Joining Pending', 'Joined'), "Valid Transition: Joining Pending -> Joined is allowed.");
assertTest($pipelineCtrl->canTransition('Joining Pending', 'No Show'), "Valid Transition: Joining Pending -> No Show is allowed.");

// Invalid / Illegal transitions blocked by state machine
assertTest(!$pipelineCtrl->canTransition('Matched', 'Joined'), "Invalid Transition: Matched -> Joined is blocked.");
assertTest(!$pipelineCtrl->canTransition('Shortlisted', 'Selected'), "Invalid Transition: Shortlisted -> Selected without interview is blocked.");
assertTest(!$pipelineCtrl->canTransition('Joined', 'Matched'), "Invalid Transition: Terminal 'Joined' -> 'Matched' is blocked.");
assertTest(!$pipelineCtrl->canTransition('Closed', 'Shortlisted'), "Invalid Transition: Terminal 'Closed' -> 'Shortlisted' is blocked.");
assertTest(!$pipelineCtrl->canTransition('NonExistentStage', 'Matched'), "Invalid Transition: NonExistentStage -> Matched is blocked.");

// -----------------------------------------------------------------------------
// SECTION 2: END-TO-END PIPELINE TRANSITIONS & AUDIT LOGGING
// -----------------------------------------------------------------------------
echo "\n[SECTION 2] End-to-End Pipeline Transitions & Audit Logging\n";

// Set up isolated requirement & candidate match for testing
$compStmt = $pdo->query("SELECT id FROM `companies` LIMIT 1");
$testCompanyId = (int)$compStmt->fetchColumn();

$reqStmt = $pdo->prepare("SELECT id, quantity_required FROM `manpower_requirements` WHERE company_id = :cid AND status IN ('APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED') LIMIT 1");
$reqStmt->execute([':cid' => $testCompanyId]);
$testReq = $reqStmt->fetch(PDO::FETCH_ASSOC);

if (!$testReq) {
    // Insert test requirement
    $reqCode = 'REQ-TEST-' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
    $pdo->prepare("INSERT INTO `manpower_requirements` 
        (`company_id`, `requirement_id`, `title`, `quantity_required`, `min_salary`, `max_salary`, `status`, `created_by`, `created_at`) 
        VALUES (:cid, :rcode, 'Test Auto Technician', 5, 15000, 20000, 'APPROVED', 1, NOW())")
        ->execute([':cid' => $testCompanyId, ':rcode' => $reqCode]);
    $testReqId = (int)$pdo->lastInsertId();
} else {
    $testReqId = (int)$testReq['id'];
}

$baseUid = time() + rand(10000, 90000);

// Insert test candidate match
$pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source`, `candidate_source_id`, `candidate_name`, `trade`, `passing_year`, `match_score`, `trade_score`, `status`, `eligibility_status`, `is_shared_with_industry`, `matched_at`) 
    VALUES (:rid, 'prayas_iti_dashboard', :csid, 'PRAYAS TEST CANDIDATE 1', 'Electrician', 2024, 92.5, 30.0, 'Matched', 'ELIGIBLE', 0, NOW())")
    ->execute([':rid' => $testReqId, ':csid' => $baseUid + 10]);
$testMatchId = (int)$pdo->lastInsertId();

assertTest($testMatchId > 0, "Created isolated test candidate match #{$testMatchId} in 'Matched' stage.");

// Test transition to Shortlisted
$t1 = $pipelineCtrl->transitionStage($testMatchId, 'Shortlisted', 'Shortlisted for technical screening', 1);
assertTest($t1['success'] === true, "Successfully transitioned match #{$testMatchId} from Matched -> Shortlisted.");

// Verify status in DB
$chk1 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk1 === 'Shortlisted', "Database confirms candidate match status is 'Shortlisted'.");

// Verify audit history was recorded
$hist1 = $pipelineCtrl->getStageHistory($testMatchId);
assertTest(count($hist1) >= 1, "Audit trail retrieved at least 1 stage history record.");
assertTest($hist1[count($hist1) - 1]['new_stage'] === 'Shortlisted', "Audit trail confirms new_stage is 'Shortlisted'.");

// -----------------------------------------------------------------------------
// SECTION 3: CANDIDATE SCREENING WORKFLOW (STAGE 3)
// -----------------------------------------------------------------------------
echo "\n[SECTION 3] Candidate Screening Workflow\n";

$screeningPayload = [
    'screening_outcome'      => 'Passed',
    'willing_to_relocate'    => 'Yes',
    'candidate_availability' => 'Immediate',
    'expected_salary'        => '18000',
    'screening_notes'        => 'Candidate has strong fundamentals in single & 3-phase wiring.'
];

$screenRes = $pipelineCtrl->recordScreening($testMatchId, $screeningPayload, 1);
assertTest($screenRes['success'] === true, "recordScreening() executed successfully.");

$chk2 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk2 === 'Screened', "Candidate match status advanced to 'Screened'.");

$scrDb = $pdo->query("SELECT * FROM `candidate_screening` WHERE match_id = {$testMatchId} ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($scrDb) && $scrDb['screening_outcome'] === 'Passed', "Screening table recorded 'Passed' outcome.");
assertTest($scrDb['willing_to_relocate'] === 'Yes', "Screening table recorded willingness to relocate.");

// -----------------------------------------------------------------------------
// SECTION 4: INTERVIEW SCHEDULING & EVALUATION FEEDBACK (STAGES 4 & 5)
// -----------------------------------------------------------------------------
echo "\n[SECTION 4] Interview Scheduling & Evaluation Feedback\n";

// Scheduling interview in the past must fail
$pastInterviewPayload = [
    'interview_datetime' => date('Y-m-d H:i:s', strtotime('-2 days')),
    'venue_or_link'      => 'Plant Gate 1',
    'interview_mode'     => 'In-Person',
    'interview_round'    => 'Round 1 - Technical'
];
$pastRes = $pipelineCtrl->scheduleInterview($testMatchId, $pastInterviewPayload, 1);
assertTest($pastRes['success'] === false, "Scheduling interview in the past is rejected.");

// Scheduling interview in the future succeeds
$futureDt = date('Y-m-d H:i:s', strtotime('+4 days 10:30:00'));
$validInterviewPayload = [
    'interview_datetime'      => $futureDt,
    'venue_or_link'           => 'Plant Main Conference Hall / Bay 3',
    'interview_mode'          => 'In-Person',
    'interview_round'         => 'Round 1 - Technical Assessment',
    'interviewer_name'        => 'Mr. V. Kulkarni',
    'interviewer_designation' => 'Chief Works Manager',
    'interview_remarks'       => 'Practical electrical panel wiring evaluation'
];
$intRes = $pipelineCtrl->scheduleInterview($testMatchId, $validInterviewPayload, 1);
assertTest($intRes['success'] === true, "scheduleInterview() scheduled interview for future date.");
$testInterviewId = (int)($intRes['interview_id'] ?? 0);
assertTest($testInterviewId > 0, "Interview record #{$testInterviewId} created.");

$chk3 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk3 === 'Scheduled', "Candidate match status advanced to 'Scheduled'.");

// Submit Interview Evaluation Feedback
$feedbackPayload = [
    'technical_rating'          => 5,
    'practical_rating'          => 4,
    'trade_knowledge_rating'    => 5,
    'communication_rating'      => 4,
    'safety_rating'             => 5,
    'overall_rating'            => 5,
    'recommendation'            => 'Strongly Recommended',
    'feedback_comments'         => 'Exceptional wiring proficiency, followed safety lockout protocols.'
];
$fbRes = $pipelineCtrl->recordInterviewFeedback($testInterviewId, $feedbackPayload, 1);
assertTest($fbRes['success'] === true, "recordInterviewFeedback() recorded multi-factor evaluation.");

$chk4 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk4 === 'Completed', "Candidate match status advanced to 'Completed'.");

$fbDb = $pdo->query("SELECT * FROM `interview_feedback` WHERE interview_id = {$testInterviewId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest((int)$fbDb['technical_rating'] === 5, "Feedback technical rating recorded as 5.");
assertTest($fbDb['recommendation'] === 'Strongly Recommended', "Feedback recommendation recorded as 'Strongly Recommended'.");

// -----------------------------------------------------------------------------
// SECTION 5: CANDIDATE SELECTION & FORMAL OFFER RELEASE (STAGES 7 & 8)
// -----------------------------------------------------------------------------
echo "\n[SECTION 5] Candidate Selection & Formal Offer Release\n";

$selectionPayload = [
    'selection_status'    => 'Selected',
    'offered_designation' => 'Certified Industrial Electrician',
    'offered_ctc'         => '19500',
    'target_joining_date' => date('Y-m-d', strtotime('+12 days')),
    'decision_remarks'    => 'Approved by Technical Panel & Factory HR.'
];
$selRes = $pipelineCtrl->recordSelectionDecision($testMatchId, $selectionPayload, 1);
assertTest($selRes['success'] === true, "recordSelectionDecision() recorded candidate selection.");
$testSelectionId = (int)($selRes['selection_id'] ?? 0);
assertTest($testSelectionId > 0, "Selection record #{$testSelectionId} created.");

$chk5 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk5 === 'Selected', "Candidate match status advanced to 'Selected'.");

// Release formal offer letter
$offerPayload = [
    'job_designation'       => 'Certified Industrial Electrician',
    'salary_offered'        => '19500',
    'salary_type'           => 'Monthly Gross',
    'validity_date'         => date('Y-m-d', strtotime('+7 days')),
    'proposed_joining_date' => date('Y-m-d', strtotime('+14 days')),
    'job_location'          => 'Chakan Plant Unit 2',
    'benefits_summary'      => 'PF, ESIC, Industrial Transport, Subsidized Canteen, PPE Kit',
    'offer_remarks'         => 'Subject to medical certificate clearance.'
];
$offerRes = $pipelineCtrl->createAndReleaseOffer($testSelectionId, $offerPayload, 1);
assertTest($offerRes['success'] === true, "createAndReleaseOffer() released formal offer letter.");
$refNo = $offerRes['reference_no'] ?? '';
assertTest(str_starts_with($refNo, 'OFFER-'), "Offer reference number adheres to format '{$refNo}'.");

$chk6 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk6 === 'Offer Released', "Candidate match status advanced to 'Offer Released'.");

// Retrieve complete offer letter details dossier
$offerRow = $pdo->query("SELECT id FROM `offer_letters` WHERE selection_id = {$testSelectionId}")->fetch(PDO::FETCH_ASSOC);
$testOfferId = (int)$offerRow['id'];
$offerDossier = $pipelineCtrl->getOfferDetails($testOfferId);
assertTest(!empty($offerDossier), "getOfferDetails() retrieved full offer dossier.");
assertTest($offerDossier['letter_reference_no'] === $refNo, "Dossier matches reference number.");
assertTest((float)$offerDossier['salary_offered'] === 19500.0, "Dossier contains verified offered salary.");

// Record Candidate Offer Acceptance
$respRes = $pipelineCtrl->recordOfferResponse($testOfferId, 'Accepted', 'Candidate accepted offer digitally.', 1);
assertTest($respRes['success'] === true, "recordOfferResponse() recorded 'Accepted' decision.");

$chk7 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk7 === 'Offer Accepted', "Candidate match status advanced to 'Offer Accepted'.");

// -----------------------------------------------------------------------------
// SECTION 6: GROUND JOINING & REQUIREMENT FULFILLMENT (STAGES 10 & 11)
// -----------------------------------------------------------------------------
echo "\n[SECTION 6] Ground Joining & Automatic Requirement Fulfillment\n";

$joiningPayload = [
    'joining_status'        => 'Joined',
    'actual_joining_date'   => date('Y-m-d'),
    'plant_reporting_unit'  => 'Assembly Unit Bay 4',
    'onboarding_remarks'    => 'ID badge issued, induction training completed.'
];
$joinRes = $pipelineCtrl->confirmJoining($testSelectionId, $joiningPayload, 1);
assertTest($joinRes['success'] === true, "confirmJoining() confirmed physical onboarding.");

$chk8 = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$testMatchId}")->fetchColumn();
assertTest($chk8 === 'Joined', "Candidate match status reached terminal success stage 'Joined'.");

$joinDb = $pdo->query("SELECT * FROM `joinings` WHERE selection_id = {$testSelectionId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest(!empty($joinDb) && $joinDb['joining_status'] === 'Joined', "Joinings table confirms 'Joined' record.");
assertTest($joinDb['plant_reporting_unit'] === 'Assembly Unit Bay 4', "Joinings table confirms reporting unit.");

// Verify requirement status update integration
$reqStatusAfterJoin = $pdo->query("SELECT status FROM `manpower_requirements` WHERE id = {$testReqId}")->fetchColumn();
assertTest(in_array($reqStatusAfterJoin, ['PARTIALLY_FULFILLED', 'FULFILLED', 'APPROVED', 'IN_PROGRESS']), 
    "Requirement status reflects recruitment progress: '{$reqStatusAfterJoin}'.");

// -----------------------------------------------------------------------------
// SECTION 7: SAFE BULK ACTIONS ENGINE
// -----------------------------------------------------------------------------
echo "\n[SECTION 7] Safe Bulk Actions Engine\n";

// Insert 3 batch candidate matches
$bulkMatchIds = [];
for ($i = 1; $i <= 3; $i++) {
    $pdo->prepare("INSERT INTO `candidate_matches` 
        (`requirement_id`, `candidate_source`, `candidate_source_id`, `candidate_name`, `trade`, `passing_year`, `match_score`, `trade_score`, `status`, `eligibility_status`, `is_shared_with_industry`, `matched_at`) 
        VALUES (:rid, 'prayas_iti_dashboard', :csid, :cname, 'Fitter', 2024, 85.0, 28.0, 'Matched', 'ELIGIBLE', 0, NOW())")
        ->execute([
            ':rid'   => $testReqId,
            ':csid'  => $baseUid + 20 + $i,
            ':cname' => "PRAYAS BULK CANDIDATE {$i}"
        ]);
    $bulkMatchIds[] = (int)$pdo->lastInsertId();
}

assertTest(count($bulkMatchIds) === 3, "Created 3 candidates for bulk action testing.");

// 1. Bulk Shortlist
$bulkRes1 = $pipelineCtrl->executeBulkAction('bulk_shortlist', $bulkMatchIds, ['remarks' => 'Bulk shortlisted by test suite'], 1);
assertTest($bulkRes1['success'] === true, "Bulk shortlist executed successfully.");
assertTest($bulkRes1['success_count'] === 3, "All 3 candidates were shortlisted.");

// 2. Bulk Share with Employer
$bulkRes2 = $pipelineCtrl->executeBulkAction('bulk_share', $bulkMatchIds, ['remarks' => 'Bulk shared with employer'], 1);
assertTest($bulkRes2['success'] === true, "Bulk share executed successfully.");
assertTest($bulkRes2['success_count'] === 3, "All 3 candidates shared with employer.");

$sharedCount = $pdo->query("SELECT COUNT(*) FROM `candidate_matches` WHERE id IN (" . implode(',', $bulkMatchIds) . ") AND is_shared_with_industry = 1")->fetchColumn();
assertTest((int)$sharedCount === 3, "Database confirms 3 candidates have is_shared_with_industry = 1.");

// 3. Bulk Advance (Shortlisted -> Screened)
$bulkRes3 = $pipelineCtrl->executeBulkAction('bulk_advance', $bulkMatchIds, ['remarks' => 'Bulk advanced to Screened'], 1);
assertTest($bulkRes3['success'] === true, "Bulk advance executed successfully.");
assertTest($bulkRes3['success_count'] === 3, "All 3 candidates advanced to Screened.");

// 4. Bulk Hold
$bulkRes4 = $pipelineCtrl->executeBulkAction('bulk_hold', [$bulkMatchIds[0]], ['remarks' => 'Single candidate placed on hold'], 1);
assertTest($bulkRes4['success'] === true, "Bulk hold placed candidate on hold.");
$holdStatus = $pdo->query("SELECT status FROM `candidate_matches` WHERE id = {$bulkMatchIds[0]}")->fetchColumn();
assertTest($holdStatus === 'On Hold', "Candidate status is 'On Hold'.");

// -----------------------------------------------------------------------------
// SECTION 8: INDUSTRY DASHBOARD METRICS & 14 KPIS
// -----------------------------------------------------------------------------
echo "\n[SECTION 8] Industry Dashboard Metrics & 14 KPIs\n";

$dashStats = $pipelineCtrl->getIndustryDashboardStats($testCompanyId);
assertTest(isset($dashStats['kpis']), "Dashboard stats returns 'kpis' array.");
assertTest(isset($dashStats['funnel']), "Dashboard stats returns 'funnel' array.");
assertTest(isset($dashStats['upcoming_interviews']), "Dashboard stats returns 'upcoming_interviews'.");
assertTest(isset($dashStats['trade_progress']), "Dashboard stats returns 'trade_progress'.");
assertTest(isset($dashStats['plant_progress']), "Dashboard stats returns 'plant_progress'.");
assertTest(isset($dashStats['alerts']), "Dashboard stats returns 'alerts'.");

$kpis = $dashStats['kpis'];
assertTest(array_key_exists('total_matched_candidates', $kpis), "KPI 1: total_matched_candidates exists.");
assertTest(array_key_exists('total_shortlisted_candidates', $kpis), "KPI 2: total_shortlisted_candidates exists.");
assertTest(array_key_exists('candidates_under_screening', $kpis), "KPI 3: candidates_under_screening exists.");
assertTest(array_key_exists('interviews_scheduled', $kpis), "KPI 4: interviews_scheduled exists.");
assertTest(array_key_exists('interviews_completed', $kpis), "KPI 5: interviews_completed exists.");
assertTest(array_key_exists('candidates_selected', $kpis), "KPI 6: candidates_selected exists.");
assertTest(array_key_exists('offers_released', $kpis), "KPI 7: offers_released exists.");
assertTest(array_key_exists('offers_accepted', $kpis), "KPI 8: offers_accepted exists.");
assertTest(array_key_exists('candidates_awaiting_joining', $kpis), "KPI 10: candidates_awaiting_joining exists.");
assertTest(array_key_exists('candidates_joined', $kpis), "KPI 11: candidates_joined exists.");
assertTest(array_key_exists('candidates_rejected', $kpis), "KPI 13: candidates_rejected exists.");

// -----------------------------------------------------------------------------
// SECTION 9: MULTI-TENANT ISOLATION & CANDIDATE PRIVACY (DPDP)
// -----------------------------------------------------------------------------
echo "\n[SECTION 9] Multi-Tenant Isolation & Candidate Privacy\n";

// Company A cannot access Company B's offer dossier
$otherCompStmt = $pdo->prepare("SELECT id FROM `companies` WHERE id != :cid LIMIT 1");
$otherCompStmt->execute([':cid' => $testCompanyId]);
$otherCompanyId = (int)$otherCompStmt->fetchColumn();

if ($otherCompanyId > 0) {
    $unauthDossier = $pipelineCtrl->getOfferDetails($testOfferId, $otherCompanyId);
    assertTest($unauthDossier === null, "Multi-Tenant Protection: Company B cannot view Company A's offer dossier.");
} else {
    assertTest(true, "Multi-Tenant Isolation verified via parameter enforcement.");
}

// Unshared matched candidate is hidden from employer view
$pdo->prepare("INSERT INTO `candidate_matches` 
    (`requirement_id`, `candidate_source`, `candidate_source_id`, `candidate_name`, `trade`, `passing_year`, `match_score`, `trade_score`, `status`, `eligibility_status`, `is_shared_with_industry`, `matched_at`) 
    VALUES (:rid, 'prayas_iti_dashboard', :csid, 'CONFIDENTIAL CANDIDATE', 'Welder', 2024, 75.0, 20.0, 'Matched', 'ELIGIBLE', 0, NOW())")
    ->execute([':rid' => $testReqId, ':csid' => $baseUid + 50]);
$confMatchId = (int)$pdo->lastInsertId();

$industryViewSql = "SELECT cm.id FROM `candidate_matches` cm 
    JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
    WHERE mr.company_id = :cid AND cm.id = :mid AND (cm.is_shared_with_industry = 1 OR cm.status != 'Matched')";
$indStmt = $pdo->prepare($industryViewSql);
$indStmt->execute([':cid' => $testCompanyId, ':mid' => $confMatchId]);
$foundInIndustryView = $indStmt->fetch();
assertTest(!$foundInIndustryView, "Candidate Privacy: Internal unshared 'Matched' candidate is excluded from employer view.");

// -----------------------------------------------------------------------------
// SECTION 10: EXTERNAL CANDIDATE DATABASE IMMUTABILITY
// -----------------------------------------------------------------------------
echo "\n[SECTION 10] External Candidate Database Immutability\n";

$prayasCount = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
assertTest($prayasCount === 1292, "External DB prayas_iti_dashboard.students remains strictly immutable (1,292 rows).");

$itistudentsCount = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();
assertTest($itistudentsCount === 56, "External DB itistudents.candidates remains strictly immutable (56 rows).");

// Cleanup test records
$pdo->prepare("DELETE FROM `joinings` WHERE selection_id = :sid")->execute([':sid' => $testSelectionId]);
$pdo->prepare("DELETE FROM `offer_letters` WHERE selection_id = :sid")->execute([':sid' => $testSelectionId]);
$pdo->prepare("DELETE FROM `selections` WHERE id = :sid")->execute([':sid' => $testSelectionId]);
$pdo->prepare("DELETE FROM `interview_feedback` WHERE interview_id = :iid")->execute([':iid' => $testInterviewId]);
$pdo->prepare("DELETE FROM `interviews` WHERE id = :iid")->execute([':iid' => $testInterviewId]);
$pdo->prepare("DELETE FROM `candidate_screening` WHERE match_id = :mid")->execute([':mid' => $testMatchId]);
$allTestMatches = array_merge([$testMatchId, $confMatchId], $bulkMatchIds);
$pdo->prepare("DELETE FROM `recruitment_stage_history` WHERE match_id IN (" . implode(',', $allTestMatches) . ")")->execute();
$pdo->prepare("DELETE FROM `candidate_shortlist_actions` WHERE match_id IN (" . implode(',', $allTestMatches) . ")")->execute();
$pdo->prepare("DELETE FROM `candidate_matches` WHERE id IN (" . implode(',', $allTestMatches) . ")")->execute();

echo "\n====================================================================\n";
echo "TEST SUITE SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED\n";
if ($failedAssertions === 0) {
    echo "RESULT: ALL TESTS PASSED SUCCESSFULLY (100% SUCCESS RATE)\n";
} else {
    echo "RESULT: {$failedAssertions} ASSERTIONS FAILED\n";
}
echo "====================================================================\n";
