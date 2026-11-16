<?php
/**
 * PART 09 Automated Test Suite: ITI Candidate Sourcing & Matching Engine
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP CLI + PDO + MySQL
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../models/CandidateSourceModel.php';
require_once __DIR__ . '/../models/CandidateMatchingModel.php';
require_once __DIR__ . '/../controllers/IndustryController.php';

$pdo = Database::getConnection();

echo "====================================================================\n";
echo " PRAYAS PORTAL — PART 09 TEST SUITE: CANDIDATE SOURCING & MATCHING\n";
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
// PRE-FLIGHT CHECK: External Database Immutability Baseline
// -----------------------------------------------------------------------------
$initPrayasCount = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$initCandidatesCount = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

echo "External Candidate Repositories Baseline:\n";
echo " - prayas_iti_dashboard.students: {$initPrayasCount} rows\n";
echo " - itistudents.candidates:        {$initCandidatesCount} rows\n\n";

// -----------------------------------------------------------------------------
// SETUP ISOLATED TEST FIXTURES
// -----------------------------------------------------------------------------
$uniqueSuffix = bin2hex(random_bytes(4));

// 1. Create Test Companies (Company A and Company B for Multi-tenant Isolation)
$compStmt = $pdo->prepare("INSERT INTO `companies` 
    (`company_name`, `registration_number`, `industry_type`, `hr_contact_email`, `verification_status`, `created_at`) 
    VALUES (:name, :code, 'Automotive & Heavy Engineering', :email, 'Verified', NOW())");

$compStmt->execute([
    ':name'  => "Part 09 Test Co A {$uniqueSuffix}",
    ':code'  => "P09-COA-{$uniqueSuffix}",
    ':email' => "coa_{$uniqueSuffix}@example.com"
]);
$companyAId = (int)$pdo->lastInsertId();

$compStmt->execute([
    ':name'  => "Part 09 Test Co B {$uniqueSuffix}",
    ':code'  => "P09-COB-{$uniqueSuffix}",
    ':email' => "cob_{$uniqueSuffix}@example.com"
]);
$companyBId = (int)$pdo->lastInsertId();

// 2. Create Test Users (Recruiter, Industry HR A, Industry HR B)
$userStmt = $pdo->prepare("INSERT INTO `users` 
    (`company_id`, `role_id`, `full_name`, `email`, `password_hash`, `status`, `created_at`) 
    VALUES (:cid, :role, :name, :email, :hash, 'Active', NOW())");
$dummyHash = password_hash('Secret@123', PASSWORD_BCRYPT);

$userStmt->execute([
    ':cid'   => null,
    ':role'  => 4, // recruiter
    ':name'  => "Recruiter {$uniqueSuffix}",
    ':email' => "recruiter_{$uniqueSuffix}@prayas.org",
    ':hash'  => $dummyHash
]);
$recruiterId = (int)$pdo->lastInsertId();

$userStmt->execute([
    ':cid'   => $companyAId,
    ':role'  => 2, // industry_admin
    ':name'  => "Industry HR A {$uniqueSuffix}",
    ':email' => "hra_{$uniqueSuffix}@example.com",
    ':hash'  => $dummyHash
]);
$userAId = (int)$pdo->lastInsertId();

$userStmt->execute([
    ':cid'   => $companyBId,
    ':role'  => 2, // industry_admin
    ':name'  => "Industry HR B {$uniqueSuffix}",
    ':email' => "hrb_{$uniqueSuffix}@example.com",
    ':hash'  => $dummyHash
]);
$userBId = (int)$pdo->lastInsertId();

// 3. Create Test Manufacturing Plants
$plantStmt = $pdo->prepare("INSERT INTO `manufacturing_plants`
    (`company_id`, `plant_name`, `plant_code`, `state`, `district`, `city`, `pincode`, `status`, `created_at`)
    VALUES (:cid, :pname, :pcode, 'Maharashtra', 'Pune', 'Chakan', '410501', 'Active', NOW())");

$plantStmt->execute([
    ':cid'   => $companyAId,
    ':pname' => "Chakan Assembly Plant {$uniqueSuffix}",
    ':pcode' => "PLT-CHK-{$uniqueSuffix}"
]);
$plantAId = (int)$pdo->lastInsertId();

$plantStmt->execute([
    ':cid'   => $companyBId,
    ':pname' => "Nagpur Precision Plant {$uniqueSuffix}",
    ':pcode' => "PLT-NGP-{$uniqueSuffix}"
]);
$plantBId = (int)$pdo->lastInsertId();

// 4. Create Requisitions with various statuses for Company A
$reqStmt = $pdo->prepare("INSERT INTO `manpower_requirements` 
    (`company_id`, `plant_id`, `requirement_id`, `title`, `qualification`, `passing_year_from`, `passing_year_to`,
     `quantity_required`, `experience_required`, `job_location_state`, `job_location_district`, `job_location_city`, 
     `status`, `gender_preference`, `created_by`, `created_at`, `application_deadline`)
    VALUES (:cid, :pid, :rid, :title, :qual, :yfrom, :yto, :qty, :exp, :state, :dist, :city, :status, :gender, :uid, NOW(), DATE_ADD(CURDATE(), INTERVAL 15 DAY))");

$tradeInsertStmt = $pdo->prepare("INSERT INTO `requirement_trades` (`requirement_id`, `trade_name`, `vacancies`) VALUES (:rid, :trade, :qty)");
$skillInsertStmt = $pdo->prepare("INSERT INTO `requirement_skills` (`requirement_id`, `skill_name`) VALUES (:rid, :skill)");

// Req 1: Approved Fitter Requirement (Eligible for matching)
$reqStmt->execute([
    ':cid'    => $companyAId,
    ':pid'    => $plantAId,
    ':rid'    => "REQ-FIT-{$uniqueSuffix}",
    ':title'  => "Junior ITI Fitter Operators",
    ':qual'   => "ITI Passed",
    ':yfrom'  => 2024,
    ':yto'    => 2024,
    ':qty'    => 10,
    ':exp'    => "Fresher (0-1 Years)",
    ':state'  => "Maharashtra",
    ':dist'   => "Pune",
    ':city'   => "Chakan",
    ':status' => "APPROVED",
    ':gender' => "Any",
    ':uid'    => $userAId
]);
$reqA1_Id = (int)$pdo->lastInsertId();
$tradeInsertStmt->execute([':rid' => $reqA1_Id, ':trade' => 'Fitter', ':qty' => 10]);
$skillInsertStmt->execute([':rid' => $reqA1_Id, ':skill' => 'CNC']);
$skillInsertStmt->execute([':rid' => $reqA1_Id, ':skill' => 'Lathe']);
$skillInsertStmt->execute([':rid' => $reqA1_Id, ':skill' => 'Blueprint Reading']);

// Req 2: Draft Electrician Requirement (Should be gated out by active requirement check)
$reqStmt->execute([
    ':cid'    => $companyAId,
    ':pid'    => $plantAId,
    ':rid'    => "REQ-DFT-{$uniqueSuffix}",
    ':title'  => "Maintenance Electricians (Draft)",
    ':qual'   => "ITI Passed",
    ':yfrom'  => 2023,
    ':yto'    => 2023,
    ':qty'    => 5,
    ':exp'    => "1-2 Years",
    ':state'  => "Maharashtra",
    ':dist'   => "Pune",
    ':city'   => "Chakan",
    ':status' => "DRAFT",
    ':gender' => "Any",
    ':uid'    => $userAId
]);
$reqA2_DraftId = (int)$pdo->lastInsertId();
$tradeInsertStmt->execute([':rid' => $reqA2_DraftId, ':trade' => 'Electrician', ':qty' => 5]);

// Req 3: Closed Welder Requirement (Should be gated out by active requirement check)
$reqStmt->execute([
    ':cid'    => $companyAId,
    ':pid'    => $plantAId,
    ':rid'    => "REQ-CLO-{$uniqueSuffix}",
    ':title'  => "MIG Welders (Closed)",
    ':qual'   => "ITI Passed",
    ':yfrom'  => 2022,
    ':yto'    => 2022,
    ':qty'    => 2,
    ':exp'    => "2+ Years",
    ':state'  => "Maharashtra",
    ':dist'   => "Pune",
    ':city'   => "Chakan",
    ':status' => "CLOSED",
    ':gender' => "Any",
    ':uid'    => $userAId
]);
$reqA3_ClosedId = (int)$pdo->lastInsertId();
$tradeInsertStmt->execute([':rid' => $reqA3_ClosedId, ':trade' => 'Welder', ':qty' => 2]);

// Req 4: Company B Requisition (For multi-tenant isolation testing)
$reqStmt->execute([
    ':cid'    => $companyBId,
    ':pid'    => $plantBId,
    ':rid'    => "REQ-COB-{$uniqueSuffix}",
    ':title'  => "Company B Machinist Requirement",
    ':qual'   => "ITI Passed",
    ':yfrom'  => 2024,
    ':yto'    => 2024,
    ':qty'    => 4,
    ':exp'    => "Fresher (0-1 Years)",
    ':state'  => "Maharashtra",
    ':dist'   => "Nagpur",
    ':city'   => "Nagpur",
    ':status' => "APPROVED",
    ':gender' => "Any",
    ':uid'    => $userBId
]);
$reqB1_Id = (int)$pdo->lastInsertId();
$tradeInsertStmt->execute([':rid' => $reqB1_Id, ':trade' => 'Machinist', ':qty' => 4]);

// Instantiate Models
$sourceModel   = new CandidateSourceModel($pdo);
$matchingModel = new CandidateMatchingModel($pdo);

// =============================================================================
// TEST SUITE EXECUTION
// =============================================================================

echo "--- SECTION 1: CANDIDATE SOURCE MODEL & DATA PROJECTION ---\n";

runTest("CandidateSourceModel instantiation succeeds", function() use ($sourceModel) {
    return ($sourceModel instanceof CandidateSourceModel);
});

runTest("CandidateSourceModel returns non-empty list of available candidate sources", function() use ($sourceModel) {
    $sources = $sourceModel->getAvailableSources();
    return (count($sources) >= 2 && isset($sources['prayas_dashboard']) && isset($sources['itistudents']));
});

runTest("CandidateSourceModel counts available candidates across both external repositories", function() use ($sourceModel, $initPrayasCount, $initCandidatesCount) {
    $counts = $sourceModel->getTotalCandidateCounts();
    return ($counts['prayas_dashboard'] === $initPrayasCount && 
            $counts['itistudents'] === $initCandidatesCount &&
            $counts['total'] === ($initPrayasCount + $initCandidatesCount));
});

runTest("CandidateSourceModel fetchCandidates returns projected candidates with privacy fields stripped", function() use ($sourceModel) {
    $sample = $sourceModel->fetchCandidates('prayas_dashboard', ['limit' => 5]);
    if (empty($sample)) return "No candidates returned from prayas_dashboard";
    $first = $sample[0];
    
    // Privacy checks: Sensitive personal identifiers MUST NOT be present in candidate object
    $hasAadhaar = isset($first['aadhaar_no']) || isset($first['aadhaar_number']);
    $hasPan     = isset($first['pan_no']) || isset($first['pan_card']);
    $hasAddress = isset($first['permanent_address']) || isset($first['full_address']);

    // Standardized fields MUST be present
    $hasStd = isset($first['source']) && isset($first['source_id']) && isset($first['candidate_name']) && isset($first['trade']);

    return (!$hasAadhaar && !$hasPan && !$hasAddress && $hasStd);
});

runTest("CandidateSourceModel standardizes trades correctly (e.g. Fitter, Electrician, Welder)", function() use ($sourceModel) {
    $sample = $sourceModel->fetchCandidates('prayas_dashboard', ['trade' => 'Fitter', 'limit' => 5]);
    if (empty($sample)) return "No Fitter candidates found";
    foreach ($sample as $c) {
        if (stripos($c['trade'], 'Fitter') === false) return "Trade not standardized to Fitter: " . $c['trade'];
    }
    return true;
});

echo "\n--- SECTION 2: SCORING ENGINE & ALGORITHM WEIGHTS ---\n";

runTest("CandidateMatchingModel has approved 7-factor weights totaling exactly 100.0%", function() use ($matchingModel) {
    $weights = $matchingModel->getScoringWeights();
    $expected = [
        'trade'        => 30.0,
        'qual'         => 15.0,
        'batch'        => 15.0,
        'skills'       => 20.0,
        'location'     => 10.0,
        'experience'   => 5.0,
        'availability' => 5.0
    ];
    $sum = array_sum($weights);
    if (abs($sum - 100.0) > 0.001) return "Sum is {$sum}, expected 100.0";
    foreach ($expected as $k => $v) {
        if (!isset($weights[$k]) || abs($weights[$k] - $v) > 0.001) {
            return "Weight {$k} is {$weights[$k]}, expected {$v}";
        }
    }
    return true;
});

runTest("CandidateMatchingModel classifies scores into 4 distinct quality tiers correctly", function() use ($matchingModel) {
    $t1 = $matchingModel->classifyScore(95.0);
    $t2 = $matchingModel->classifyScore(82.5);
    $t3 = $matchingModel->classifyScore(65.0);
    $t4 = $matchingModel->classifyScore(45.0);
    $t0 = $matchingModel->classifyScore(0.0);

    return ($t1 === 'Excellent Match' &&
            $t2 === 'Strong Match' &&
            $t3 === 'Possible Match' &&
            $t4 === 'Low Match' &&
            $t0 === 'Not Eligible');
});

runTest("Scoring Engine calculates exact trade match awarded full 30.0 points", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, CNC', 'work_location' => 'Pune, Maharashtra', 'experience_required' => 'Fresher (0-1 Years)'];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, CNC', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => 'Fresher', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return (abs($eval['trade_score'] - 30.0) < 0.001);
});

runTest("Scoring Engine calculates related trade cross-walk awarded partial points (20.0 pts)", function() use ($matchingModel) {
    // Machinist is related to Fitter/Turner
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => 'Pune', 'experience_required' => ''];
    $cand = ['trade' => 'Machinist', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => 'Fresher', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return (abs($eval['trade_score'] - 20.0) < 0.001);
});

runTest("Scoring Engine calculates exact passing batch year awarded full 15.0 points", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => '', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => '', 'state' => '', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return (abs($eval['batch_score'] - 15.0) < 0.001);
});

runTest("Scoring Engine calculates adjacent passing year (+/-1 year) awarded 10.0 points", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => '', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2023', 'skills' => '', 'district' => '', 'state' => '', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return (abs($eval['batch_score'] - 10.0) < 0.001);
});

runTest("Scoring Engine calculates technical skills matching up to 20.0 points", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, Milling, CNC, Blueprint', 'work_location' => '', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, Milling, CNC, Blueprint', 'district' => '', 'state' => '', 'experience' => '', 'availability' => '', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return (abs($eval['skills_score'] - 20.0) < 0.001);
});

runTest("Scoring Engine calculates location proximity (same district 10.0 pts, same state 6.0 pts)", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => 'Pune, Maharashtra', 'experience_required' => ''];
    
    // District match
    $candDist = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => '', 'availability' => '', 'is_passed' => 1];
    $evalDist = $matchingModel->evaluateMatch($candDist, $req);
    
    // State match only
    $candState = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => 'Nagpur', 'state' => 'Maharashtra', 'experience' => '', 'availability' => '', 'is_passed' => 1];
    $evalState = $matchingModel->evaluateMatch($candState, $req);

    return (abs($evalDist['location_score'] - 10.0) < 0.001 && abs($evalState['location_score'] - 6.0) < 0.001);
});

echo "\n--- SECTION 3: MANDATORY ELIGIBILITY GATES ---\n";

runTest("Gate 1: Completely unrelated trade triggers NOT_ELIGIBLE and forces match score to 0.00", function() use ($matchingModel) {
    $req = ['trade' => 'Electrician', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Wiring', 'work_location' => 'Pune', 'experience_required' => ''];
    $cand = ['trade' => 'COPA', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Typing', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return ($eval['eligibility_status'] === 'NOT_ELIGIBLE' && abs($eval['total_score'] - 0.0) < 0.001);
});

runTest("Gate 2: Candidate with exam status FAIL triggers NOT_ELIGIBLE and forces match score to 0.00", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => '', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => '', 'state' => '', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 0];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return ($eval['eligibility_status'] === 'NOT_ELIGIBLE' && abs($eval['total_score'] - 0.0) < 0.001 && stripos($eval['eligibility_reasons'], 'Exam') !== false);
});

runTest("Gate 3: Gender preference mismatch triggers NOT_ELIGIBLE (e.g. Female Only requirement)", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'work_location' => '', 'experience_required' => '', 'gender_preference' => 'Female Only'];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => '', 'state' => '', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 1, 'gender' => 'Male'];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return ($eval['eligibility_status'] === 'NOT_ELIGIBLE' && abs($eval['total_score'] - 0.0) < 0.001 && stripos($eval['eligibility_reasons'], 'Gender') !== false);
});

runTest("Gate 4: Mandatory Diploma requirement gates out candidates with ITI only", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'Diploma / Polytechnic', 'passing_year' => '2024', 'skills' => '', 'work_location' => '', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => '', 'district' => '', 'state' => '', 'experience' => '', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return ($eval['eligibility_status'] === 'NOT_ELIGIBLE' && abs($eval['total_score'] - 0.0) < 0.001 && stripos($eval['eligibility_reasons'], 'Qualification') !== false);
});

runTest("Requirement Gate: Active Requirement Check rejects DRAFT requisitions", function() use ($matchingModel, $reqA2_DraftId, $recruiterId) {
    $res = $matchingModel->executeMatchingRun($reqA2_DraftId, $recruiterId);
    return ($res['success'] === false && stripos($res['message'], 'not eligible for automated matching') !== false);
});

runTest("Requirement Gate: Active Requirement Check rejects CLOSED requisitions", function() use ($matchingModel, $reqA3_ClosedId, $recruiterId) {
    $res = $matchingModel->executeMatchingRun($reqA3_ClosedId, $recruiterId);
    return ($res['success'] === false && stripos($res['message'], 'not eligible for automated matching') !== false);
});

echo "\n--- SECTION 4: MATCHING EXECUTION & AUDIT LOGGING ---\n";

runTest("executeMatchingRun on APPROVED requisition runs successfully", function() use ($matchingModel, $reqA1_Id, $recruiterId) {
    $res = $matchingModel->executeMatchingRun($reqA1_Id, $recruiterId);
    return ($res['success'] === true && $res['total_scanned'] > 0);
});

runTest("executeMatchingRun logs execution entry in candidate_matching_runs table", function() use ($pdo, $reqA1_Id) {
    $stmt = $pdo->prepare("SELECT * FROM `candidate_matching_runs` WHERE `requirement_id` = :rid ORDER BY `id` DESC LIMIT 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($run !== false && (int)$run['total_candidates_scanned'] > 0 && !empty($run['weights_json']));
});

runTest("executeMatchingRun populates candidate_matches table with ranked records", function() use ($pdo, $reqA1_Id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `candidate_matches` WHERE `requirement_id` = :rid");
    $stmt->execute([':rid' => $reqA1_Id]);
    $count = (int)$stmt->fetchColumn();
    return ($count > 0);
});

runTest("candidate_matches records contain transparent 7-dimension score fields", function() use ($pdo, $reqA1_Id) {
    $stmt = $pdo->prepare("SELECT * FROM `candidate_matches` WHERE `requirement_id` = :rid AND `eligibility_status` = 'ELIGIBLE' LIMIT 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) return "No eligible match found";

    $hasScores = isset($m['trade_score']) && isset($m['batch_score']) && isset($m['qual_score']) &&
                 isset($m['skills_score']) && isset($m['location_score']) && isset($m['exp_score']) && isset($m['avail_score']);
    $hasJson   = !empty($m['match_details_json']);

    return ($hasScores && $hasJson);
});

runTest("getMatchingMetrics calculates summary KPIs accurately", function() use ($matchingModel, $reqA1_Id) {
    $m = $matchingModel->getMatchingMetrics($reqA1_Id);
    return (isset($m['total_matched']) && isset($m['eligible_count']) && 
            isset($m['shortlisted_count']) && isset($m['tier1_count']) &&
            $m['total_matched'] > 0);
});

echo "\n--- SECTION 5: DEDUPLICATION & RE-RUN SAFETY ---\n";

runTest("Unique Compound Constraint uk_req_candidate prevents duplicates on re-run", function() use ($pdo, $matchingModel, $reqA1_Id, $recruiterId) {
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM `candidate_matches` WHERE `requirement_id` = :rid");
    $stmt1->execute([':rid' => $reqA1_Id]);
    $countBefore = (int)$stmt1->fetchColumn();

    // Re-run matching
    $matchingModel->executeMatchingRun($reqA1_Id, $recruiterId);

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM `candidate_matches` WHERE `requirement_id` = :rid");
    $stmt2->execute([':rid' => $reqA1_Id]);
    $countAfter = (int)$stmt2->fetchColumn();

    return ($countBefore === $countAfter && $countAfter > 0);
});

runTest("Cross-source deduplication removes duplicate student records by NCVT registration/Name", function() use ($pdo, $matchingModel, $reqA1_Id) {
    // Check that no duplicate candidate_name appears with identical trade in candidate_matches
    $stmt = $pdo->prepare("SELECT candidate_name, trade, COUNT(*) as cnt 
                           FROM `candidate_matches` 
                           WHERE `requirement_id` = :rid 
                           GROUP BY candidate_name, trade 
                           HAVING cnt > 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return (count($dups) === 0);
});

echo "\n--- SECTION 6: RECRUITER WORKFLOW ACTIONS ---\n";

$firstMatchId = 0;
runTest("Identify first matched candidate for workflow testing", function() use ($pdo, $reqA1_Id, &$firstMatchId) {
    $stmt = $pdo->prepare("SELECT id FROM `candidate_matches` WHERE `requirement_id` = :rid AND `eligibility_status` = 'ELIGIBLE' ORDER BY `match_score` DESC LIMIT 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $firstMatchId = (int)$stmt->fetchColumn();
    return ($firstMatchId > 0);
});

runTest("Recruiter Action: shortlistCandidate updates status and logs shortlist action", function() use ($matchingModel, $pdo, &$firstMatchId, $recruiterId) {
    $ok = $matchingModel->shortlistCandidate($firstMatchId, $recruiterId, 'Candidate verified by phone screen');
    if (!$ok) return "shortlistCandidate returned false";

    $stmt = $pdo->prepare("SELECT status FROM `candidate_matches` WHERE id = :id");
    $stmt->execute([':id' => $firstMatchId]);
    $status = $stmt->fetchColumn();

    $logStmt = $pdo->prepare("SELECT COUNT(*) FROM `candidate_shortlist_actions` WHERE match_id = :id AND action = 'SHORTLIST'");
    $logStmt->execute([':id' => $firstMatchId]);
    $logCount = (int)$logStmt->fetchColumn();

    return ($status === 'Shortlisted' && $logCount > 0);
});

runTest("Recruiter Action: shareWithIndustry authorizes candidate release to industry portal", function() use ($matchingModel, $pdo, &$firstMatchId, $recruiterId) {
    $ok = $matchingModel->shareWithIndustry($firstMatchId, $recruiterId);
    if (!$ok) return "shareWithIndustry returned false";

    $stmt = $pdo->prepare("SELECT is_shared_with_industry, shared_at FROM `candidate_matches` WHERE id = :id");
    $stmt->execute([':id' => $firstMatchId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return ((int)$row['is_shared_with_industry'] === 1 && !empty($row['shared_at']));
});

runTest("Recruiter Action: rejectCandidate requires non-empty reason and updates status", function() use ($matchingModel, $pdo, $reqA1_Id, $recruiterId) {
    // Get another match to reject
    $stmt = $pdo->prepare("SELECT id FROM `candidate_matches` WHERE `requirement_id` = :rid AND status != 'Shortlisted' LIMIT 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $rejectId = (int)$stmt->fetchColumn();
    if (!$rejectId) return "No candidate available to reject";

    // Test rejection with empty rationale fails
    $failTry = $matchingModel->rejectCandidate($rejectId, $recruiterId, '');
    if ($failTry === true) return "Rejection succeeded with empty rationale (should fail)";

    // Test rejection with rationale succeeds
    $ok = $matchingModel->rejectCandidate($rejectId, $recruiterId, 'Candidate relocated out of Maharashtra state');
    if (!$ok) return "Rejection failed with valid rationale";

    $checkStmt = $pdo->prepare("SELECT status, rejection_reason FROM `candidate_matches` WHERE id = :id");
    $checkStmt->execute([':id' => $rejectId]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

    return ($row['status'] === 'Rejected' && !empty($row['rejection_reason']));
});

echo "\n--- SECTION 7: MULTI-TENANT ISOLATION & DATA LEAKAGE PREVENTION ---\n";

runTest("Company A Industry Controller getCandidateMatches returns matches for Company A requirement", function() use ($pdo, $companyAId, $reqA1_Id) {
    $ctrl = new IndustryController($pdo);
    $res = $ctrl->getCandidateMatches($companyAId, ['requirement_id' => $reqA1_Id]);
    return (isset($res['data']) && count($res['data']) > 0);
});

runTest("Cross-Company Security Gate: Company B Industry Controller CANNOT view Company A matches", function() use ($pdo, $companyBId, $reqA1_Id) {
    $ctrlB = new IndustryController($pdo);
    $res = $ctrlB->getCandidateMatches($companyBId, ['requirement_id' => $reqA1_Id]);
    return (isset($res['data']) && count($res['data']) === 0);
});

runTest("executeMatchingRun verifies Company Boundary isolation before execution", function() use ($matchingModel, $companyBId, $reqA1_Id, $recruiterId) {
    // If companyId is passed explicitly as Company B, attempting to run on Company A's req must fail
    $res = $matchingModel->executeMatchingRun($reqA1_Id, $recruiterId, $companyBId);
    return ($res['success'] === false && stripos($res['message'], 'Access denied') !== false);
});

echo "\n--- SECTION 8: CANDIDATE PRIVACY & DPDP COMPLIANCE ---\n";

runTest("Industry Portal projection redacts Aadhaar number, phone numbers, and full addresses", function() use ($pdo, $companyAId, $reqA1_Id) {
    $ctrl = new IndustryController($pdo);
    $res = $ctrl->getCandidateMatches($companyAId, ['requirement_id' => $reqA1_Id]);
    if (empty($res['data'])) return "No matches found in industry query";

    foreach ($res['data'] as $m) {
        if (isset($m['aadhaar_no']) || isset($m['aadhaar_number'])) return "Aadhaar leaked to industry portal";
        if (isset($m['mobile_number']) || isset($m['phone_number'])) return "Phone number leaked to industry portal";
        if (isset($m['permanent_address']) || isset($m['full_address'])) return "Full domestic address leaked";
    }
    return true;
});

runTest("Industry Portal candidate matching modal payload preserves 7-dimension diagnostic breakdown", function() use ($pdo, $companyAId, $reqA1_Id) {
    $ctrl = new IndustryController($pdo);
    $res = $ctrl->getCandidateMatches($companyAId, ['requirement_id' => $reqA1_Id]);
    if (empty($res['data'])) return "No matches found in industry query";
    $first = $res['data'][0];

    return (isset($first['trade_score']) &&
            isset($first['batch_score']) &&
            isset($first['qual_score']) &&
            isset($first['skills_score']) &&
            isset($first['location_score']) &&
            isset($first['exp_score']) &&
            isset($first['avail_score']));
});

echo "\n--- SECTION 9: DATABASE IMMUTABILITY INTEGRITY VERIFICATION ---\n";

runTest("External prayas_iti_dashboard.students remains 100% immutable (1,292 rows)", function() use ($pdo, $initPrayasCount) {
    $afterCount = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
    return ($afterCount === $initPrayasCount && $afterCount === 1292);
});

runTest("External itistudents.candidates remains 100% immutable (56 rows)", function() use ($pdo, $initCandidatesCount) {
    $afterCount = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();
    return ($afterCount === $initCandidatesCount && $afterCount === 56);
});

echo "\n--- SECTION 10: ADVANCED RECRUITER SEARCH, FILTERING & AUDITING ---\n";

runTest("Score Tier: Threshold >= 90.0 maps strictly to Excellent Match", function() use ($matchingModel) {
    return ($matchingModel->classifyScore(90.0) === 'Excellent Match' &&
            $matchingModel->classifyScore(100.0) === 'Excellent Match');
});

runTest("Score Tier: Threshold 75.0 - 89.9 maps strictly to Strong Match", function() use ($matchingModel) {
    return ($matchingModel->classifyScore(75.0) === 'Strong Match' &&
            $matchingModel->classifyScore(89.9) === 'Strong Match');
});

runTest("Score Tier: Threshold 60.0 - 74.9 maps strictly to Possible Match", function() use ($matchingModel) {
    return ($matchingModel->classifyScore(60.0) === 'Possible Match' &&
            $matchingModel->classifyScore(74.9) === 'Possible Match');
});

runTest("Score Tier: Threshold < 60.0 maps strictly to Low Match", function() use ($matchingModel) {
    return ($matchingModel->classifyScore(59.9) === 'Low Match' &&
            $matchingModel->classifyScore(15.0) === 'Low Match');
});

runTest("Zero Score Guarantee: Ineligible candidate receives 0.00 score despite high skill/location matches", function() use ($matchingModel) {
    // COPA candidate (trade mismatch) with perfect skills, location, batch, etc.
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, CNC', 'work_location' => 'Pune', 'experience_required' => ''];
    $cand = ['trade' => 'COPA', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'Lathe, CNC', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => 'Fresher', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    return ($eval['is_eligible'] === false && abs($eval['total_score'] - 0.0) < 0.001);
});

runTest("Recruiter Action: holdCandidate updates status to On Hold and logs audit event", function() use ($matchingModel, $pdo, $reqA1_Id, $recruiterId) {
    $stmt = $pdo->prepare("SELECT id FROM `candidate_matches` WHERE `requirement_id` = :rid AND status != 'Shortlisted' AND status != 'Rejected' LIMIT 1");
    $stmt->execute([':rid' => $reqA1_Id]);
    $holdId = (int)$stmt->fetchColumn();
    if (!$holdId) return "No candidate available to hold";

    $ok = $matchingModel->holdCandidate($holdId, $recruiterId, 'Candidate reviewing relocation options');
    if (!$ok) return "holdCandidate returned false";

    $checkStmt = $pdo->prepare("SELECT status FROM `candidate_matches` WHERE id = :id");
    $checkStmt->execute([':id' => $holdId]);
    $status = $checkStmt->fetchColumn();

    $logStmt = $pdo->prepare("SELECT COUNT(*) FROM `candidate_shortlist_actions` WHERE match_id = :id AND action = 'HOLD'");
    $logStmt->execute([':id' => $holdId]);
    $logCount = (int)$logStmt->fetchColumn();

    return ($status === 'On Hold' && $logCount > 0);
});

runTest("Recruiter Console: getMatchesForRequirement enforces pagination limit and offset", function() use ($matchingModel, $reqA1_Id) {
    $p1 = $matchingModel->getMatchesForRequirement($reqA1_Id, [], 5, 0);
    $p2 = $matchingModel->getMatchesForRequirement($reqA1_Id, [], 5, 5);

    return (count($p1) > 0 && count($p2) > 0 && $p1[0]['id'] !== $p2[0]['id']);
});

runTest("Recruiter Console: getMatchesForRequirement filters accurately by status = 'Shortlisted'", function() use ($matchingModel, $reqA1_Id) {
    $filtered = $matchingModel->getMatchesForRequirement($reqA1_Id, ['status' => 'Shortlisted']);
    if (empty($filtered)) return "No shortlisted candidates found in test";
    foreach ($filtered as $row) {
        if ($row['status'] !== 'Shortlisted') return "Unexpected status: " . $row['status'];
    }
    return true;
});

runTest("Recruiter Console: getMatchesForRequirement filters accurately by score tier = 'tier1'", function() use ($matchingModel, $reqA1_Id) {
    $filtered = $matchingModel->getMatchesForRequirement($reqA1_Id, ['score_tier' => 'tier1']);
    foreach ($filtered as $row) {
        if ((float)$row['match_score'] < 85.0) return "Score below 85 in tier1: " . $row['match_score'];
    }
    return true;
});

runTest("Recruiter Console: getMatchesForRequirement search by candidate name returns matching subset", function() use ($matchingModel, $reqA1_Id, &$firstMatchId, $pdo) {
    $nameStmt = $pdo->prepare("SELECT candidate_name FROM `candidate_matches` WHERE id = :id");
    $nameStmt->execute([':id' => $firstMatchId]);
    $name = (string)$nameStmt->fetchColumn();
    $keyword = substr($name, 0, 4);

    $results = $matchingModel->getMatchesForRequirement($reqA1_Id, ['search' => $keyword]);
    return (count($results) > 0);
});

runTest("Recruiter Console: getMatchesForRequirement search by trade returns trade subset", function() use ($matchingModel, $reqA1_Id) {
    $results = $matchingModel->getMatchesForRequirement($reqA1_Id, ['search' => 'Fitter']);
    return (count($results) > 0);
});

runTest("Re-running executeMatchingRun preserves existing Shortlisted candidates without resetting to Matched", function() use ($matchingModel, $pdo, $reqA1_Id, $recruiterId, &$firstMatchId) {
    // Re-run matching
    $matchingModel->executeMatchingRun($reqA1_Id, $recruiterId);

    // Verify status remains Shortlisted
    $checkStmt = $pdo->prepare("SELECT status FROM `candidate_matches` WHERE id = :id");
    $checkStmt->execute([':id' => $firstMatchId]);
    $status = $checkStmt->fetchColumn();

    return ($status === 'Shortlisted');
});

runTest("Transparent Scoring Explanation contains complete 7-dimension diagnostic structure", function() use ($matchingModel) {
    $req = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'CNC', 'work_location' => 'Pune', 'experience_required' => ''];
    $cand = ['trade' => 'Fitter', 'qualification' => 'ITI Passed', 'passing_year' => '2024', 'skills' => 'CNC', 'district' => 'Pune', 'state' => 'Maharashtra', 'experience' => 'Fresher', 'availability' => 'Immediate', 'is_passed' => 1];
    $eval = $matchingModel->evaluateMatch($cand, $req);
    $exp = $eval['explanation'];

    return (isset($exp['trade']) && isset($exp['batch']) && isset($exp['qual']) &&
            isset($exp['skills']) && isset($exp['location']) && isset($exp['experience']) && isset($exp['availability']) &&
            isset($exp['category']) && isset($exp['total_score']));
});

runTest("Verification that candidate tables in external DBs had zero schema modifications", function() use ($pdo) {
    // Check column count of students table
    $stmt1 = $pdo->query("DESCRIBE `prayas_iti_dashboard`.`students`");
    $cols1 = $stmt1->fetchAll();
    
    // Check column count of candidates table
    $stmt2 = $pdo->query("DESCRIBE `itistudents`.`candidates`");
    $cols2 = $stmt2->fetchAll();

    return (count($cols1) === 33 && count($cols2) === 32);
});

// -----------------------------------------------------------------------------
// TEARDOWN TEST FIXTURES
// -----------------------------------------------------------------------------
echo "\n--- TEARDOWN TEST FIXTURES ---\n";
try {
    $pdo->prepare("DELETE FROM `candidate_shortlist_actions` WHERE match_id IN (SELECT id FROM `candidate_matches` WHERE requirement_id IN (?, ?, ?, ?))")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `candidate_matches` WHERE requirement_id IN (?, ?, ?, ?)")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `candidate_matching_runs` WHERE requirement_id IN (?, ?, ?, ?)")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `requirement_skills` WHERE requirement_id IN (?, ?, ?, ?)")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `requirement_trades` WHERE requirement_id IN (?, ?, ?, ?)")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `manpower_requirements` WHERE id IN (?, ?, ?, ?)")->execute([$reqA1_Id, $reqA2_DraftId, $reqA3_ClosedId, $reqB1_Id]);
    $pdo->prepare("DELETE FROM `manufacturing_plants` WHERE id IN (?, ?)")->execute([$plantAId, $plantBId]);
    $pdo->prepare("DELETE FROM `users` WHERE id IN (?, ?, ?)")->execute([$recruiterId, $userAId, $userBId]);
    $pdo->prepare("DELETE FROM `companies` WHERE id IN (?, ?)")->execute([$companyAId, $companyBId]);
    echo "  [CLEANUP] Successfully cleaned up all test fixtures.\n";
} catch (Throwable $e) {
    echo "  [WARN] Teardown issue: " . $e->getMessage() . "\n";
}

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo " PART 09 CANDIDATE SOURCING & MATCHING TEST SUMMARY\n";
echo " Total Tests:  {$testsTotal}\n";
echo " Passed:       {$testsPassed}\n";
echo " Failed:       {$testsFailed}\n";
echo " Pass Rate:    " . round(($testsPassed / max(1, $testsTotal)) * 100, 2) . "%\n";
echo " Finished at:  " . date('Y-m-d H:i:s') . "\n";
echo "====================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
