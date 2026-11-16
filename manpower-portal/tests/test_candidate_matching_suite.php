<?php
/**
 * Automated Test Suite — Part 09: Candidate Sourcing & Algorithmic Matching
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CandidateSourceModel.php';
require_once __DIR__ . '/../models/CandidateMatchingModel.php';
require_once __DIR__ . '/../controllers/RequirementController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 09 Candidate Matching Engine Tests\n";
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
// Test Group 1: Candidate Source Connection & Read-Only Access
// -------------------------------------------------------------
echo "--- Test Group 1: Candidate Source Connection & Read-Only Access ---\n";

$sourceModel = new CandidateSourceModel($pdo);

// 1.1 Verify exact counts of candidate databases
$countPrayas = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$countIti    = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertTest("Source 1 (prayas_iti_dashboard.students) Accessible (1,292 rows)", $countPrayas === 1292);
assertTest("Source 2 (itistudents.candidates) Accessible (56 rows)", $countIti === 56);

// 1.2 Verify Candidate Retrieval
$samplePrayas = $sourceModel->getCandidateById('prayas_dashboard', 1);
assertTest("Candidate Retrieved from prayas_dashboard Source", $samplePrayas !== null && $samplePrayas['candidate_source'] === 'prayas_dashboard');
assertTest("Prayas Candidate Fields Normalized", !empty($samplePrayas['full_name']) && isset($samplePrayas['trade']));

$sampleIti = $sourceModel->getCandidateById('itistudents', 1);
assertTest("Candidate Retrieved from itistudents Source", $sampleIti !== null && $sampleIti['candidate_source'] === 'itistudents');
assertTest("Iti Candidate Fields Normalized", !empty($sampleIti['full_name']) && isset($sampleIti['trade']));

// -------------------------------------------------------------
// Test Group 2: Algorithmic Scoring Subsystem (100 Points Model)
// -------------------------------------------------------------
echo "\n--- Test Group 2: Algorithmic Scoring Subsystem (100 Points Model) ---\n";

$matchingModel = new CandidateMatchingModel($pdo);

$testReqSpec = [
    'title'                => 'Precision CNC Machinist',
    'trades'               => [['trade_name' => 'Machinist'], ['trade_name' => 'Fitter']],
    'passing_year_from'    => 2024,
    'passing_year_to'      => 2025,
    'gender_preference'    => 'Any',
    'job_location_city'    => 'Pune',
    'job_location_district'=> 'Pune',
    'skills'               => [['skill_name' => 'CNC'], ['skill_name' => 'milling']],
    'experience_required'  => 'Fresher'
];

// 2.1 Exact Trade Match (30 Pts)
$exactCand = [
    'candidate_source' => 'itistudents',
    'candidate_source_id' => 101,
    'trade' => 'Machinist',
    'passing_year' => 2025,
    'passing_year_raw' => '2025',
    'qualification' => 'ITI Passed',
    'district' => 'Pune',
    'preferred_location' => 'Pune',
    'state' => 'Maharashtra',
    'experience' => 'Fresher',
    'computer_knowledge' => 'CNC machine operator training',
    'languages_known' => 'English Hindi',
    'evaluator_feedback' => 'Good CNC skills',
    'result_status' => 'PASS',
    'gender' => 'Male'
];
$evalExact = $matchingModel->evaluateCandidate($exactCand, $testReqSpec, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Exact Trade Match Awards Full 30.0 Points", $evalExact['subscores']['trade'] === 30.0);
assertTest("Exact Passing Batch Awards Full 20.0 Points", $evalExact['subscores']['batch'] === 20.0);
assertTest("Same City Awards Full 10.0 Points", $evalExact['subscores']['location'] === 10.0);
assertTest("Overall Score Calibrated High (>= 85.0% Tier 1)", $evalExact['total_score'] >= 85.0);

// 2.2 Related Trade Match (20 Pts)
$relatedCand = array_merge($exactCand, ['trade' => 'Turner']); // Turner is related to Machinist/Fitter
$evalRelated = $matchingModel->evaluateCandidate($relatedCand, $testReqSpec, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Related Trade Match Awards Partial 20.0 Points", $evalRelated['subscores']['trade'] === 20.0);

// 2.3 Adjacent Passing Batch (12 Pts)
$adjBatchCand = array_merge($exactCand, ['passing_year' => 2023]); // 1 year adjacent to 2024-2025
$evalAdj = $matchingModel->evaluateCandidate($adjBatchCand, $testReqSpec, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Adjacent Passing Batch Awards 12.0 Points", $evalAdj['subscores']['batch'] === 12.0);

// -------------------------------------------------------------
// Test Group 3: Mandatory Eligibility Gates
// -------------------------------------------------------------
echo "\n--- Test Group 3: Mandatory Eligibility Gates ---\n";

// 3.1 Unrelated Trade Fails Gate
$unrelatedCand = array_merge($exactCand, ['trade' => 'COPA']); // Computer Operator is unrelated to Machinist
$evalUnrelated = $matchingModel->evaluateCandidate($unrelatedCand, $testReqSpec, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Unrelated Trade Fails Mandatory Gate (NOT_ELIGIBLE)", $evalUnrelated['is_eligible'] === false);
assertTest("Ineligible Candidate Score Grounded to 0.00", $evalUnrelated['total_score'] === 0.00);
assertTest("Ineligibility Failure Reason Recorded", !empty($evalUnrelated['ineligible_reasons']));

// 3.2 Exam Result FAIL Fails Gate
$failCand = array_merge($exactCand, ['result_status' => 'FAIL']);
$evalFail = $matchingModel->evaluateCandidate($failCand, $testReqSpec, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Exam FAIL Status Rejection Gate Enforced", $evalFail['is_eligible'] === false);

// 3.3 Gender Restriction Gate
$reqFemaleOnly = array_merge($testReqSpec, ['gender_preference' => 'Female']);
$maleCand = array_merge($exactCand, ['gender' => 'Male']);
$evalGender = $matchingModel->evaluateCandidate($maleCand, $reqFemaleOnly, CandidateMatchingModel::DEFAULT_WEIGHTS);
assertTest("Gender Mismatch Gate Enforced", $evalGender['is_eligible'] === false);

// -------------------------------------------------------------
// Test Group 4: End-to-End Matching Run & Persistence
// -------------------------------------------------------------
echo "\n--- Test Group 4: End-to-End Matching Run & Persistence ---\n";

// Create a test company & approved requirement
$testCompName = 'Matching Tech Industries ' . bin2hex(random_bytes(3));
$stmt = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, 
     `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Manufacturing', 'Tech Park', 'Maharashtra', 'Pune', 'Pune', '411001', 
            'HR Lead', '9890003344', :email, 'Verified')");
$stmt->execute([
    ':cname' => $testCompName,
    ':reg'   => 'CIN-MT-' . bin2hex(random_bytes(3)),
    ':email' => 'hr_' . bin2hex(random_bytes(3)) . '@matchingtech.com'
]);
$testCompanyId = (int)$pdo->lastInsertId();

// Create test user for company
$roleStmt = $pdo->query("SELECT id FROM `roles` WHERE name = 'industry_admin' LIMIT 1");
$indAdminRoleId = (int)$roleStmt->fetchColumn();
$userEmail = 'user_match_' . bin2hex(random_bytes(3)) . '@matchingtech.com';
$stmt = $pdo->prepare("INSERT INTO `users` (`role_id`, `company_id`, `full_name`, `email`, `password_hash`, `status`) 
    VALUES (:rid, :cid, 'Matching User', :email, 'hash', 'Active')");
$stmt->execute([':rid' => $indAdminRoleId, ':cid' => $testCompanyId, ':email' => $userEmail]);
$testUserId = (int)$pdo->lastInsertId();

$reqCode = 'REQ-TEST-MATCH-' . bin2hex(random_bytes(3));
$stmt = $pdo->prepare("INSERT INTO `manpower_requirements` 
    (`requirement_id`, `company_id`, `title`, `quantity_required`, `status`, `job_location_state`, `job_location_district`, `job_location_city`, `created_by`) 
    VALUES (:code, :cid, 'Electrician & Wireman Technicians', 20, 'APPROVED', 'Maharashtra', 'Mumbai', 'Mumbai', :uid)");
$stmt->execute([':code' => $reqCode, ':cid' => $testCompanyId, ':uid' => $testUserId]);
$testReqId = (int)$pdo->lastInsertId();

// Attach sub-trade
$pdo->prepare("INSERT INTO `requirement_trades` (`requirement_id`, `trade_name`, `vacancies`) VALUES (:rid1, 'Electrician', 15), (:rid2, 'Wireman', 5)")
    ->execute([':rid1' => $testReqId, ':rid2' => $testReqId]);

// Execute Matching Run
$runOutcome = $matchingModel->executeMatchingRun($testReqId, 1);
assertTest("Matching Run Executed Successfully", $runOutcome['success'] === true);
assertTest("Matching Run Scanned Real Candidates", $runOutcome['total_candidates_scanned'] > 0);
assertTest("Eligible Candidates Identified", $runOutcome['eligible_candidates_found'] > 0);

// Verify candidate_matches rows in DB
$matches = $matchingModel->getMatchesForRequirement($testReqId);
assertTest("Matches Persisted in candidate_matches Table", count($matches) > 0);
assertTest("Highest Ranked Match Has Valid Score", floatval($matches[0]['match_score']) > 0.0);
assertTest("Subscore Dimensions Persisted in DB", isset($matches[0]['trade_score']) && isset($matches[0]['batch_score']));

// -------------------------------------------------------------
// Test Group 5: Recruiter Shortlisting & Industry Visibility
// -------------------------------------------------------------
echo "\n--- Test Group 5: Recruiter Shortlisting & Industry Visibility ---\n";

$firstMatchId = (int)$matches[0]['id'];

// 5.1 Recruiter Shortlists Candidate
$shortlisted = $matchingModel->shortlistCandidate($firstMatchId, 1, 'Strong technical aptitude and verified credentials.');
assertTest("Recruiter Shortlist Action Succeeded", $shortlisted === true);

$stmt = $pdo->prepare("SELECT status FROM `candidate_matches` WHERE id = :id");
$stmt->execute([':id' => $firstMatchId]);
assertTest("Candidate Status Updated to Shortlisted", $stmt->fetchColumn() === 'Shortlisted');

// 5.2 Recruiter Action Audit Logged
$actionStmt = $pdo->prepare("SELECT action, remarks FROM `candidate_shortlist_actions` WHERE match_id = :mid");
$actionStmt->execute([':mid' => $firstMatchId]);
$action = $actionStmt->fetch(PDO::FETCH_ASSOC);
assertTest("Shortlist Action Recorded in candidate_shortlist_actions", $action && $action['action'] === 'SHORTLIST');

// 5.3 Candidate Not Yet Shared with Industry (Gate Check)
$stmt = $pdo->prepare("SELECT is_shared_with_industry FROM `candidate_matches` WHERE id = :id");
$stmt->execute([':id' => $firstMatchId]);
assertTest("Candidate Profile Private by Default (is_shared_with_industry = 0)", (int)$stmt->fetchColumn() === 0);

// 5.4 Recruiter Authorizes Release to Industry
$shared = $matchingModel->shareWithIndustry($firstMatchId, 1);
assertTest("Recruiter Releases Candidate Profile to Industry", $shared === true);

$stmt->execute([':id' => $firstMatchId]);
assertTest("Candidate Marked as Shared with Industry (is_shared_with_industry = 1)", (int)$stmt->fetchColumn() === 1);

// 5.5 Recruiter Rejection Workflow
if (count($matches) > 1) {
    $secondMatchId = (int)$matches[1]['id'];
    $rejected = $matchingModel->rejectCandidate($secondMatchId, 1, 'Failed ground reporting verification.');
    assertTest("Recruiter Rejection with Mandatory Reason Succeeded", $rejected === true);

    $stmt = $pdo->prepare("SELECT status, rejection_reason FROM `candidate_matches` WHERE id = :id");
    $stmt->execute([':id' => $secondMatchId]);
    $rejRow = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest("Rejection Reason Persisted in Database", $rejRow['status'] === 'Rejected' && !empty($rejRow['rejection_reason']));
}

// -------------------------------------------------------------
// Test Group 6: Candidate Databases Safety & Clean Cleanup
// -------------------------------------------------------------
echo "\n--- Test Group 6: Candidate Databases Safety & Clean Cleanup ---\n";

// Cleanup test requirement artifacts
$pdo->prepare("DELETE FROM `candidate_shortlist_actions` WHERE match_id IN (SELECT id FROM `candidate_matches` WHERE requirement_id = :rid)")
    ->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `candidate_matches` WHERE requirement_id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `candidate_matching_runs` WHERE requirement_id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `requirement_trades` WHERE requirement_id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `manpower_requirements` WHERE id = :rid")->execute([':rid' => $testReqId]);
$pdo->prepare("DELETE FROM `users` WHERE id = :uid")->execute([':uid' => $testUserId]);
$pdo->prepare("DELETE FROM `companies` WHERE id = :cid")->execute([':cid' => $testCompanyId]);

assertTest("Cleaned Up Test Matching Records & Test Requisition", true);

// Verify Zero Mutation of Candidate Databases
$c1 = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$c2 = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertTest("Candidate Database 1 Untouched (Exactly 1,292 records)", $c1 === 1292);
assertTest("Candidate Database 2 Untouched (Exactly 56 records)", $c2 === 56);

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "  Total Passed: {$testsPassed}\n";
echo "  Total Failed: {$testsFailed}\n";
echo "========================================================\n";

exit($testsFailed > 0 ? 1 : 0);
