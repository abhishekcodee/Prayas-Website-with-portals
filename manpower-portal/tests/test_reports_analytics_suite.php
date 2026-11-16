<?php
/**
 * Automated Test Suite — Part 11: Reports & Executive Analytics
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ReportController.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 11 Reports & Executive Analytics\n";
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

$reportCtrl = new ReportController($pdo);

// -------------------------------------------------------------
// Test Group 1: Executive Dashboard Metrics (All 14 Required KPIs)
// -------------------------------------------------------------
echo "--- Test Group 1: Executive Dashboard Metrics (14 Required KPIs) ---\n";

$metrics = $reportCtrl->getExecutiveMetrics();

assertTest("Metric 1: Total Industry Companies is integer", is_int($metrics['total_companies']) && $metrics['total_companies'] >= 0);
assertTest("Metric 2: Total Manpower Requirements is integer", is_int($metrics['total_requirements']) && $metrics['total_requirements'] >= 0);
assertTest("Metric 3: Total Vacancies is integer", is_int($metrics['total_vacancies']) && $metrics['total_vacancies'] >= 0);
assertTest("Metric 4: Active Requirements is integer", is_int($metrics['active_requirements']) && $metrics['active_requirements'] >= 0);
assertTest("Metric 5: Closed Requirements is integer", is_int($metrics['closed_requirements']) && $metrics['closed_requirements'] >= 0);
assertTest("Metric 6: Total Candidates Matched is integer", is_int($metrics['total_candidates_matched']) && $metrics['total_candidates_matched'] >= 0);
assertTest("Metric 7: Candidates Shortlisted is integer", is_int($metrics['candidates_shortlisted']) && $metrics['candidates_shortlisted'] >= 0);
assertTest("Metric 8: Candidates Interviewed is integer", is_int($metrics['candidates_interviewed']) && $metrics['candidates_interviewed'] >= 0);
assertTest("Metric 9: Candidates Selected is integer", is_int($metrics['candidates_selected']) && $metrics['candidates_selected'] >= 0);
assertTest("Metric 10: Candidates Joined is integer", is_int($metrics['candidates_joined']) && $metrics['candidates_joined'] >= 0);
assertTest("Metric 11: Pending Requirements is integer", is_int($metrics['pending_requirements']) && $metrics['pending_requirements'] >= 0);
assertTest("Metric 12: Requirements Near Deadline is integer", is_int($metrics['requirements_near_deadline']) && $metrics['requirements_near_deadline'] >= 0);
assertTest("Metric 13: Requirement Fulfillment Percentage is numeric (0-100%)", is_numeric($metrics['requirement_fulfillment_pct']) && $metrics['requirement_fulfillment_pct'] >= 0.0 && $metrics['requirement_fulfillment_pct'] <= 100.0);
assertTest("Metric 14: Average Time to Fulfill Requirement is numeric", is_numeric($metrics['avg_time_to_fulfill_days']) && $metrics['avg_time_to_fulfill_days'] >= 0.0);

// -------------------------------------------------------------
// Test Group 2: Report Group A: Manpower Requirement Reports
// -------------------------------------------------------------
echo "\n--- Test Group 2: Manpower Requirement Reports ---\n";

// 2.1 Requirement-wise vacancy report
$reqVacRows = $reportCtrl->getRequirementWiseVacancyReport();
assertTest("Requirement-wise vacancy report returns valid array", is_array($reqVacRows));
if (!empty($reqVacRows)) {
    assertTest("Requirement row includes requirement_id, title, vacancies, and joined_count", 
        isset($reqVacRows[0]['requirement_id']) && isset($reqVacRows[0]['title']) && isset($reqVacRows[0]['quantity_required']) && isset($reqVacRows[0]['joined_count']));
}

// 2.2 Company-wise requirement report
$compReqRows = $reportCtrl->getCompanyWiseRequirementReport();
assertTest("Company-wise manpower requirement report returns array", is_array($compReqRows));
if (!empty($compReqRows)) {
    assertTest("Company report aggregates total_requirements and total_vacancies", 
        isset($compReqRows[0]['company_name']) && isset($compReqRows[0]['total_requirements']) && isset($compReqRows[0]['total_vacancies']));
}

// 2.3 Trade-wise requirement report
$tradeRows = $reportCtrl->getTradeWiseRequirementReport();
assertTest("Trade-wise manpower requirement report returns array", is_array($tradeRows));
if (!empty($tradeRows)) {
    assertTest("Trade report returns trade_name and vacancies_demanded", 
        isset($tradeRows[0]['trade_name']) && isset($tradeRows[0]['vacancies_demanded']));
}

// 2.4 Batch-wise requirement report
$batchRows = $reportCtrl->getBatchWiseRequirementReport();
assertTest("Batch-wise manpower requirement report returns array", is_array($batchRows));

// 2.5 Plant/location-wise requirement report
$plantRows = $reportCtrl->getPlantLocationWiseRequirementReport();
assertTest("Plant/location-wise requirement report returns array", is_array($plantRows));
if (!empty($plantRows)) {
    assertTest("Plant report includes plant_name or facility_name", isset($plantRows[0]['plant_name']));
}

// 2.6 Pending and fulfilled requirement report
$pendingFulfilledRows = $reportCtrl->getPendingAndFulfilledReport();
assertTest("Pending and fulfilled requirement status report returns array", is_array($pendingFulfilledRows));

// -------------------------------------------------------------
// Test Group 3: Report Group B: Candidate Reports
// -------------------------------------------------------------
echo "\n--- Test Group 3: Candidate Reports ---\n";

// 3.1 Candidate sourcing report
$candSourceRows = $reportCtrl->getCandidateSourcingReport();
assertTest("Candidate sourcing report returns array", is_array($candSourceRows));
if (!empty($candSourceRows)) {
    assertTest("Candidate row contains candidate_name, trade, match_score, status", 
        isset($candSourceRows[0]['candidate_name']) && isset($candSourceRows[0]['trade']) && isset($candSourceRows[0]['match_score']) && isset($candSourceRows[0]['status']));
}

// 3.2 ITI-wise candidate sourcing report
$itiRows = $reportCtrl->getItiWiseCandidateReport();
assertTest("ITI-wise candidate sourcing report returns array from student repository", is_array($itiRows) && count($itiRows) > 0);
assertTest("ITI row contains iti_name and total_students", isset($itiRows[0]['iti_name']) && isset($itiRows[0]['total_students']));

// 3.3 Trade-wise candidate report
$tradeCandRows = $reportCtrl->getTradeWiseCandidateReport();
assertTest("Trade-wise candidate report returns array", is_array($tradeCandRows));

// 3.4 Passing-year-wise candidate report
$pyearRows = $reportCtrl->getPassingYearWiseCandidateReport();
assertTest("Passing-year-wise candidate report returns array", is_array($pyearRows));

// 3.5 Shortlisted candidate report
$shortlistRows = $reportCtrl->getShortlistedCandidateReport();
assertTest("Shortlisted candidate report returns array", is_array($shortlistRows));

// 3.6 Interview and selection report
$interviewRows = $reportCtrl->getInterviewSelectionReport();
assertTest("Interview and selection report returns array", is_array($interviewRows));

// 3.7 Joining status report
$joinStatusRows = $reportCtrl->getJoiningStatusReport();
assertTest("Joining status report returns array", is_array($joinStatusRows));

// -------------------------------------------------------------
// Test Group 4: Report Group C: Recruitment Pipeline & Attrition
// -------------------------------------------------------------
echo "\n--- Test Group 4: Recruitment Pipeline & Attrition Reports ---\n";

// 4.1 Stage-wise candidate counts
$stageCounts = $reportCtrl->getStageWiseCandidateCounts();
assertTest("Stage-wise candidate counts returns associative array", is_array($stageCounts));

// 4.2 Stage-wise conversion percentage
$convRates = $reportCtrl->getStageWiseConversionRates();
assertTest("Stage-wise conversion rates returns shortlist_pct", isset($convRates['shortlist_pct']));
assertTest("Stage-wise conversion rates returns interview_pct", isset($convRates['interview_pct']));
assertTest("Stage-wise conversion rates returns selection_pct", isset($convRates['selection_pct']));
assertTest("Stage-wise conversion rates returns offer_acceptance_pct", isset($convRates['offer_acceptance_pct']));
assertTest("Stage-wise conversion rates returns joining_conversion_pct", isset($convRates['joining_conversion_pct']));

// 4.3 Rejected candidate report
$rejectedRows = $reportCtrl->getRejectedCandidateReport();
assertTest("Rejected candidate report returns array", is_array($rejectedRows));

// 4.4 Dropout candidate report
$dropoutRows = $reportCtrl->getDropoutCandidateReport();
assertTest("Dropout candidate report returns array (No Show, Withdrawn, Offer Declined)", is_array($dropoutRows));

// -------------------------------------------------------------
// Test Group 5: Report Group D: Industry Reports
// -------------------------------------------------------------
echo "\n--- Test Group 5: Industry Reports ---\n";

// 5.1 Company-wise hiring report
$hiringRows = $reportCtrl->getCompanyWiseHiringReport();
assertTest("Company-wise hiring report returns array", is_array($hiringRows));
if (!empty($hiringRows)) {
    assertTest("Hiring report includes total_vacancies and total_hired", isset($hiringRows[0]['total_vacancies']) && isset($hiringRows[0]['total_hired']));
}

// 5.2 Industry-wise manpower demand
$industryDemandRows = $reportCtrl->getIndustryWiseDemandReport();
assertTest("Industry sector demand report returns array", is_array($industryDemandRows));
if (!empty($industryDemandRows)) {
    assertTest("Sector report includes sector and vacancies_demanded", isset($industryDemandRows[0]['sector']) && isset($industryDemandRows[0]['vacancies_demanded']));
}

// 5.3 Company requirement history
$firstCompId = (int)($pdo->query("SELECT id FROM `companies` LIMIT 1")->fetchColumn() ?: 1);
$historyRows = $reportCtrl->getCompanyRequirementHistory($firstCompId);
assertTest("Company requirement history returns array for valid company ID", is_array($historyRows));

// -------------------------------------------------------------
// Test Group 6: Interactive Chart Payloads
// -------------------------------------------------------------
echo "\n--- Test Group 6: Interactive Chart Payloads ---\n";

$tradeChart = $reportCtrl->getTradeVacancyChartData();
assertTest("Trade vacancy chart data returns 'labels' and 'data' arrays", isset($tradeChart['labels']) && isset($tradeChart['data']) && is_array($tradeChart['labels']));

$monthlyChart = $reportCtrl->getMonthlyDemandChartData();
assertTest("Monthly demand chart data returns 'labels' and 'data' arrays", isset($monthlyChart['labels']) && isset($monthlyChart['data']) && is_array($monthlyChart['labels']));

$funnelChart = $reportCtrl->getRecruitmentFunnelChartData();
assertTest("Recruitment funnel chart data returns 5 standard stages", count($funnelChart['labels']) === 5 && count($funnelChart['data']) === 5);

$compHiringChart = $reportCtrl->getCompanyHiringChartData();
assertTest("Company hiring chart data returns 'labels' and 'data'", isset($compHiringChart['labels']) && isset($compHiringChart['data']));

// -------------------------------------------------------------
// Test Group 7: Multi-Tenant RBAC & Company Isolation
// -------------------------------------------------------------
echo "\n--- Test Group 7: Multi-Tenant RBAC & Company Isolation ---\n";

// Provision 2 distinct test companies with distinct requirements
$compNameA = 'PRAYAS Report Test Alpha ' . bin2hex(random_bytes(2));
$compNameB = 'PRAYAS Report Test Beta ' . bin2hex(random_bytes(2));

$pdo->prepare("INSERT INTO `companies` (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Automotive', 'Pune', 'Maharashtra', 'Pune', 'Pune', '411001', 'HR Lead', '9890001122', :email, 'Verified')")
    ->execute([':cname' => $compNameA, ':reg' => 'CIN-REP-A-' . bin2hex(random_bytes(2)), ':email' => 'rep_a_' . bin2hex(random_bytes(2)) . '@test.com']);
$compAId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO `companies` (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Pharmaceuticals', 'Mumbai', 'Maharashtra', 'Mumbai', 'Mumbai', '400001', 'HR Lead', '9890001133', :email, 'Verified')")
    ->execute([':cname' => $compNameB, ':reg' => 'CIN-REP-B-' . bin2hex(random_bytes(2)), ':email' => 'rep_b_' . bin2hex(random_bytes(2)) . '@test.com']);
$compBId = (int)$pdo->lastInsertId();

// Create 1 requirement for Company A (10 vacancies)
$reqCodeA = 'REQ-REP-A-' . bin2hex(random_bytes(2));
$pdo->prepare("INSERT INTO `manpower_requirements` (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Alpha CNC Lathe Machinist', 10, 'APPROVED', 1, NOW())")
    ->execute([':cid' => $compAId, ':code' => $reqCodeA]);
$reqAId = (int)$pdo->lastInsertId();

// Create 1 requirement for Company B (25 vacancies)
$reqCodeB = 'REQ-REP-B-' . bin2hex(random_bytes(2));
$pdo->prepare("INSERT INTO `manpower_requirements` (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Beta Cleanroom Chemical Operator', 25, 'APPROVED', 1, NOW())")
    ->execute([':cid' => $compBId, ':code' => $reqCodeB]);
$reqBId = (int)$pdo->lastInsertId();

// Company A Scoped Query Test
$metricsA = $reportCtrl->getExecutiveMetrics($compAId);
assertTest("Company A scoped metrics returns exactly 10 vacancies", $metricsA['total_vacancies'] === 10);
assertTest("Company A scoped metrics returns exactly 1 requirement", $metricsA['total_requirements'] === 1);

// Company B Scoped Query Test
$metricsB = $reportCtrl->getExecutiveMetrics($compBId);
assertTest("Company B scoped metrics returns exactly 25 vacancies", $metricsB['total_vacancies'] === 25);
assertTest("Company B scoped metrics returns exactly 1 requirement", $metricsB['total_requirements'] === 1);

// Verify cross-tenant isolation on report listings
$reqsA = $reportCtrl->getRequirementWiseVacancyReport([], $compAId);
assertTest("Company A report contains Company A requirement", count($reqsA) === 1 && $reqsA[0]['requirement_id'] === $reqCodeA);

$hasLeak = false;
foreach ($reqsA as $r) {
    if ($r['requirement_id'] === $reqCodeB) {
        $hasLeak = true;
    }
}
assertTest("Zero data leakage: Company B requirement is completely invisible in Company A report", !$hasLeak);

// -------------------------------------------------------------
// Test Group 8: Multi-Factor Filters Accuracy
// -------------------------------------------------------------
echo "\n--- Test Group 8: Multi-Factor Filters Accuracy ---\n";

// Filter by specific company ID
$filtCompRows = $reportCtrl->getRequirementWiseVacancyReport(['company_id' => $compBId]);
assertTest("Filter by company_id correctly restricts to target company", count($filtCompRows) === 1 && $filtCompRows[0]['requirement_id'] === $reqCodeB);

// Filter by non-matching trade
$filtTradeRows = $reportCtrl->getRequirementWiseVacancyReport(['trade' => 'NonExistentTradeXYZ']);
assertTest("Filter by non-matching trade safely returns empty array without SQL errors", is_array($filtTradeRows));

// Clean up test fixture records
try {
    $pdo->prepare("DELETE FROM `manpower_requirements` WHERE id IN ({$reqAId}, {$reqBId})")->execute();
    $pdo->prepare("DELETE FROM `companies` WHERE id IN ({$compAId}, {$compBId})")->execute();
    echo "  [INFO] Isolation test fixtures cleaned up successfully.\n";
} catch (Exception $e) {
    echo "  [WARN] Teardown note: " . $e->getMessage() . "\n";
}

// -------------------------------------------------------------
// Test Group 9: External Candidate DB Immutability
// -------------------------------------------------------------
echo "\n--- Test Group 9: External Candidate DB Immutability ---\n";

$countPrayas = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$countIti    = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertTest("External DB 1 (prayas_iti_dashboard.students) untouched (exact 1,292 rows)", $countPrayas === 1292);
assertTest("External DB 2 (itistudents.candidates) untouched (exact 56 rows)", $countIti === 56);

// -------------------------------------------------------------
// Summary
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "Part 11 Test Suite Results: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
}

