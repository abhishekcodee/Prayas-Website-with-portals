<?php
/**
 * Part 11: Reports & Executive Analytics Comprehensive Verification Suite
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 * 
 * Verifies all Part 11 capabilities:
 * 1. Executive Dashboard (14 Database-Backed KPI Statistics)
 * 2. 10 Interactive Chart.js Datasets
 * 3. Comprehensive Manpower Demand Reports
 * 4. Candidate Sourcing & Matching Reports (College, District, Quality Tiers)
 * 5. Recruitment Pipeline Funnel & Conversion Rates
 * 6. Screening, Assessment & Interview Reports
 * 7. Selection, Offer & Joining Reports
 * 8. Operational Efficiency & Stage Turnaround Times (TAT)
 * 9. Pending & Overdue Requirements Audit
 * 10. Formula Injection Defense & DPDP PII Masking
 * 11. Multi-Tenant RBAC & Company Isolation
 * 12. External Candidate Database Immutability
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ReportController.php';

$pdo = Database::getConnection();
$reportCtrl = new ReportController($pdo);

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $msg, ?string $detail = null): void {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$msg}" . ($detail ? " ({$detail})" : "") . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "PRAYAS Portal - Part 11 Reports & Executive Analytics Test Suite\n";
echo "====================================================================\n\n";

// =========================================================================
// SECTION 1: 14 DATABASE-BACKED KPI STATISTICS
// =========================================================================
echo "--- Section 1: Executive Dashboard (14 Database-Backed KPI Statistics) ---\n";
$metrics = $reportCtrl->getComprehensiveDashboardMetrics();

assertCondition(is_int($metrics['total_companies']) && $metrics['total_companies'] >= 0, "KPI 1: Total Companies is valid non-negative integer (" . $metrics['total_companies'] . ")");
assertCondition(is_int($metrics['total_requirements']) && $metrics['total_requirements'] >= 0, "KPI 2: Total Requirements is valid integer (" . $metrics['total_requirements'] . ")");
assertCondition(is_int($metrics['active_requirements']) && $metrics['active_requirements'] >= 0, "KPI 3: Active Requirements is valid integer (" . $metrics['active_requirements'] . ")");
assertCondition(is_int($metrics['pending_requirements']) && $metrics['pending_requirements'] >= 0, "KPI 4: Pending Approval Requirements is valid integer (" . $metrics['pending_requirements'] . ")");
assertCondition(is_int($metrics['total_vacancies']) && $metrics['total_vacancies'] >= 0, "KPI 5: Total Vacancies Demanded is valid integer (" . $metrics['total_vacancies'] . ")");
assertCondition(is_int($metrics['active_vacancies']) && $metrics['active_vacancies'] >= 0, "KPI 6: Active Vacancies is valid integer (" . $metrics['active_vacancies'] . ")");
assertCondition(is_int($metrics['candidates_joined']) && $metrics['candidates_joined'] >= 0, "KPI 7: Candidates Joined / Fulfilled is valid integer (" . $metrics['candidates_joined'] . ")");
assertCondition(is_int($metrics['total_candidates_matched']) && $metrics['total_candidates_matched'] >= 0, "KPI 8: Total Candidates Sourced / Matched is valid integer (" . $metrics['total_candidates_matched'] . ")");
assertCondition(is_int($metrics['candidates_shortlisted']) && $metrics['candidates_shortlisted'] >= 0, "KPI 9: Candidates Shortlisted is valid integer (" . $metrics['candidates_shortlisted'] . ")");
assertCondition(is_int($metrics['candidates_interviewed']) && $metrics['candidates_interviewed'] >= 0, "KPI 10: Candidates Interviewed is valid integer (" . $metrics['candidates_interviewed'] . ")");
assertCondition(is_int($metrics['candidates_selected']) && $metrics['candidates_selected'] >= 0, "KPI 11: Candidates Selected is valid integer (" . $metrics['candidates_selected'] . ")");
assertCondition(is_int($metrics['offers_released']) && is_int($metrics['offers_accepted']), "KPI 12: Offers Released & Accepted are valid integers (" . $metrics['offers_accepted'] . "/" . $metrics['offers_released'] . ")");
assertCondition(is_numeric($metrics['requirement_fulfillment_pct']) && $metrics['requirement_fulfillment_pct'] >= 0.0 && $metrics['requirement_fulfillment_pct'] <= 100.0, "KPI 13: Requirement Fulfillment Rate is valid percentage (" . $metrics['requirement_fulfillment_pct'] . "%)");
assertCondition(is_numeric($metrics['avg_time_to_fulfill_days']) && $metrics['avg_time_to_fulfill_days'] >= 0.0, "KPI 14: Average Time to Fulfill (TAT) is valid numeric days (" . $metrics['avg_time_to_fulfill_days'] . " days)");

// =========================================================================
// SECTION 2: 10 INTERACTIVE CHART DATASETS
// =========================================================================
echo "\n--- Section 2: 10 Interactive Chart.js Datasets ---\n";
// Chart 1
$ch1 = $reportCtrl->getMonthlyDemandChartData();
assertCondition(isset($ch1['labels'], $ch1['data']) && is_array($ch1['labels']) && is_array($ch1['data']), "Chart 1: Monthly Demand Trend returns valid labels and data arrays");

// Chart 2
$ch2 = $reportCtrl->getTradeVacancyChartData();
assertCondition(isset($ch2['labels'], $ch2['data']) && is_array($ch2['labels']), "Chart 2: Trade-Wise Vacancy Distribution returns valid labels and data");

// Chart 3
$ch3 = $reportCtrl->getIndustryDistributionChartData();
assertCondition(isset($ch3['labels'], $ch3['data']) && is_array($ch3['labels']), "Chart 3: Industry Sector Distribution returns valid labels and data");

// Chart 4
$ch4 = $reportCtrl->getPlantDemandChartData();
assertCondition(isset($ch4['labels'], $ch4['data']) && is_array($ch4['labels']), "Chart 4: Plant-Wise Manpower Demand returns valid labels and data");

// Chart 5
$ch5 = $reportCtrl->getRequirementStatusChartData();
assertCondition(isset($ch5['labels'], $ch5['data']) && is_array($ch5['labels']), "Chart 5: Requirement Status Breakdown returns valid labels and data");

// Chart 6
$ch6 = $reportCtrl->getCandidateMatchingScoreChartData();
assertCondition(isset($ch6['labels'], $ch6['data']) && is_array($ch6['labels']), "Chart 6: Candidate Matching Score Bands returns valid score tiers");

// Chart 7
$ch7 = $reportCtrl->getRecruitmentFunnelChartData();
assertCondition(isset($ch7['labels'], $ch7['data']) && count($ch7['labels']) === 5, "Chart 7: Recruitment Pipeline Funnel contains 5 core stages");
$ch7b = $reportCtrl->get16StageRecruitmentFunnelChartData();
assertCondition(isset($ch7b['labels'], $ch7b['data']) && count($ch7b['labels']) === 16, "Chart 7b: Full 16-Stage Recruitment Funnel contains all 16 taxonomy stages");

// Chart 8
$ch8 = $reportCtrl->getMonthlyJoiningChartData();
assertCondition(isset($ch8['labels'], $ch8['data']) && is_array($ch8['labels']), "Chart 8: Monthly Joining Trend returns valid labels and data");

// Chart 9
$ch9 = $reportCtrl->getOfferAcceptanceChartData();
assertCondition(isset($ch9['labels'], $ch9['released'], $ch9['accepted']), "Chart 9: Offer Release vs Acceptance Trend returns parallel arrays");

// Chart 10
$ch10 = $reportCtrl->getVacancyFulfillmentTrendChartData();
assertCondition(isset($ch10['labels'], $ch10['demands'], $ch10['joinings'], $ch10['fulfillment_pct']), "Chart 10: Vacancy Fulfillment Trend returns demand, joining, and percentage series");

// =========================================================================
// SECTION 3: COMPREHENSIVE MANPOWER DEMAND REPORTS
// =========================================================================
echo "\n--- Section 3: Comprehensive Manpower Demand Reports ---\n";
$reqVacRows = $reportCtrl->getRequirementWiseVacancyReport();
assertCondition(is_array($reqVacRows), "Requirement-Wise Vacancy Report returns valid array");
if (!empty($reqVacRows)) {
    $first = $reqVacRows[0];
    assertCondition(isset($first['requirement_id'], $first['title'], $first['quantity_required'], $first['status']), "Requirement row contains requirement_id, title, quantity_required, status");
}

$compReqRows = $reportCtrl->getCompanyWiseRequirementReport();
assertCondition(is_array($compReqRows), "Company-Wise Consolidation Report returns valid array");
if (!empty($compReqRows)) {
    $first = $compReqRows[0];
    assertCondition(isset($first['company_name'], $first['total_requirements'], $first['total_vacancies']), "Company consolidation row aggregates requirements and vacancies");
}

$tradeReqRows = $reportCtrl->getTradeWiseRequirementReport();
assertCondition(is_array($tradeReqRows), "Trade-Wise Demand Report returns valid array");
if (!empty($tradeReqRows)) {
    $first = $tradeReqRows[0];
    assertCondition(isset($first['trade_name'], $first['vacancies_demanded']), "Trade row contains trade_name and vacancies_demanded");
}

$plantReqRows = $reportCtrl->getPlantLocationWiseRequirementReport();
assertCondition(is_array($plantReqRows), "Plant & Location Demand Report returns valid array");
if (!empty($plantReqRows)) {
    $first = $plantReqRows[0];
    assertCondition(isset($first['plant_name'], $first['vacancies_demanded']), "Plant row contains plant_name and vacancies_demanded");
}

$pendingFulfilledRows = $reportCtrl->getPendingAndFulfilledReport();
assertCondition(is_array($pendingFulfilledRows), "Pending vs Fulfilled Breakdown returns valid array");

// =========================================================================
// SECTION 4: CANDIDATE SOURCING & MATCHING REPORTS
// =========================================================================
echo "\n--- Section 4: Candidate Sourcing & Matching Reports ---\n";
$candSourcing = $reportCtrl->getCandidateSourcingReport();
assertCondition(is_array($candSourcing), "Candidate Sourcing Report returns valid array");
if (!empty($candSourcing)) {
    $first = $candSourcing[0];
    assertCondition(isset($first['candidate_name'], $first['trade'], $first['match_score'], $first['status']), "Candidate row contains candidate_name, trade, match_score, status");
}

$itiSourcing = $reportCtrl->getItiWiseCandidateReport();
assertCondition(is_array($itiSourcing) && !empty($itiSourcing), "ITI / College-Wise Sourcing Report returns valid institute list");
assertCondition(isset($itiSourcing[0]['iti_name'], $itiSourcing[0]['total_students']), "ITI row contains iti_name and student count");

$districtSourcing = $reportCtrl->getDistrictWiseSourcingReport();
assertCondition(is_array($districtSourcing), "District-Wise Candidate Sourcing returns valid array");

$qualityTiers = $reportCtrl->getCandidateQualityTiersReport();
assertCondition(is_array($qualityTiers), "Candidate Quality Tiers Report returns array of score bands");
if (!empty($qualityTiers)) {
    assertCondition(isset($qualityTiers[0]['quality_tier'], $qualityTiers[0]['candidate_count'], $qualityTiers[0]['avg_score']), "Quality Tier row contains tier label, count, and avg_score");
}

$batchRows = $reportCtrl->getBatchWiseRequirementReport();
assertCondition(is_array($batchRows), "Batch / Passing-Year Wise Report returns valid array");

$pyearCandRows = $reportCtrl->getPassingYearWiseCandidateReport();
assertCondition(is_array($pyearCandRows), "Candidate Passing-Year Distribution returns valid array");

// =========================================================================
// SECTION 5: RECRUITMENT PIPELINE FUNNEL & CONVERSION RATES
// =========================================================================
echo "\n--- Section 5: Recruitment Pipeline Funnel & Conversion Rates ---\n";
$stageCounts = $reportCtrl->getStageWiseCandidateCounts();
assertCondition(is_array($stageCounts), "Stage-wise candidate counts returns associative array");

$conversionRates = $reportCtrl->getStageWiseConversionRates();
assertCondition(isset($conversionRates['shortlist_pct'], $conversionRates['interview_pct'], $conversionRates['selection_pct'], $conversionRates['offer_acceptance_pct'], $conversionRates['joining_conversion_pct']), "Stage-wise conversion rates returns all 5 percentage ratios");
assertCondition($conversionRates['shortlist_pct'] >= 0.0 && $conversionRates['shortlist_pct'] <= 100.0, "Shortlist conversion rate is between 0% and 100%");
assertCondition($conversionRates['joining_conversion_pct'] >= 0.0 && $conversionRates['joining_conversion_pct'] <= 100.0, "Joining conversion rate is between 0% and 100%");

$rejectedRows = $reportCtrl->getRejectedCandidateReport();
assertCondition(is_array($rejectedRows), "Rejected Candidate Report returns array");

$dropoutRows = $reportCtrl->getDropoutCandidateReport();
assertCondition(is_array($dropoutRows), "Dropout & No-Show Candidate Report returns array");

// =========================================================================
// SECTION 6: SCREENING, ASSESSMENT & INTERVIEW REPORTS
// =========================================================================
echo "\n--- Section 6: Screening, Assessment & Interview Reports ---\n";
$screeningRows = $reportCtrl->getScreeningAssessmentInterviewReport();
assertCondition(is_array($screeningRows), "Screening, Assessment & Interview Report returns array");
if (!empty($screeningRows)) {
    $first = $screeningRows[0];
    assertCondition(isset($first['candidate_name'], $first['req_code'], $first['current_stage']), "Screening row contains candidate, req_code, and current_stage");
}

$interviewRows = $reportCtrl->getInterviewSelectionReport();
assertCondition(is_array($interviewRows), "Interview & Selection Consolidated Report returns array");
if (!empty($interviewRows)) {
    $first = $interviewRows[0];
    assertCondition(isset($first['candidate_name'], $first['interview_datetime'], $first['overall_rating']), "Interview row contains candidate, interview_datetime, and rating");
}

// =========================================================================
// SECTION 7: SELECTION, OFFER & JOINING REPORTS
// =========================================================================
echo "\n--- Section 7: Selection, Offer & Joining Reports ---\n";
$joiningRows = $reportCtrl->getJoiningStatusReport();
assertCondition(is_array($joiningRows), "Joining Status & Onboarding Report returns array");
if (!empty($joiningRows)) {
    $first = $joiningRows[0];
    assertCondition(isset($first['candidate_name'], $first['joining_status'], $first['actual_joining_date']), "Joining row contains candidate, joining_status, and actual_joining_date");
}

// =========================================================================
// SECTION 8: OPERATIONAL EFFICIENCY & STAGE TURNAROUND TIMES (TAT)
// =========================================================================
echo "\n--- Section 8: Operational Efficiency & Stage Turnaround Times (TAT) ---\n";
$efficiency = $reportCtrl->getRecruitmentEfficiencyMetrics();
assertCondition(isset($efficiency['sourced_to_shortlist_days']) && is_numeric($efficiency['sourced_to_shortlist_days']), "TAT 1: Sourced to Shortlist duration is numeric (" . $efficiency['sourced_to_shortlist_days'] . " days)");
assertCondition(isset($efficiency['shortlist_to_interview_days']) && is_numeric($efficiency['shortlist_to_interview_days']), "TAT 2: Shortlist to Interview duration is numeric (" . $efficiency['shortlist_to_interview_days'] . " days)");
assertCondition(isset($efficiency['interview_to_selection_days']) && is_numeric($efficiency['interview_to_selection_days']), "TAT 3: Interview to Selection duration is numeric (" . $efficiency['interview_to_selection_days'] . " days)");
assertCondition(isset($efficiency['selection_to_offer_days']) && is_numeric($efficiency['selection_to_offer_days']), "TAT 4: Selection to Offer duration is numeric (" . $efficiency['selection_to_offer_days'] . " days)");
assertCondition(isset($efficiency['offer_to_joining_days']) && is_numeric($efficiency['offer_to_joining_days']), "TAT 5: Offer to Joining duration is numeric (" . $efficiency['offer_to_joining_days'] . " days)");
assertCondition(isset($efficiency['total_time_to_hire_days']) && $efficiency['total_time_to_hire_days'] >= 0.0, "Total End-to-End Time to Hire is calculated (" . $efficiency['total_time_to_hire_days'] . " days)");

// =========================================================================
// SECTION 9: PENDING & OVERDUE REQUIREMENTS AUDIT
// =========================================================================
echo "\n--- Section 9: Pending & Overdue Requirements Audit ---\n";
$audit = $reportCtrl->getPendingAndOverdueRequirementsReport();
assertCondition(isset($audit['approaching_deadline']) && is_array($audit['approaching_deadline']), "Audit returns approaching_deadline list (<= 7 days)");
assertCondition(isset($audit['overdue']) && is_array($audit['overdue']), "Audit returns overdue requirements list");
assertCondition(isset($audit['zero_matches']) && is_array($audit['zero_matches']), "Audit returns zero_matches active requirements list");

// =========================================================================
// SECTION 10: FORMULA INJECTION DEFENSE & DPDP PII MASKING
// =========================================================================
echo "\n--- Section 10: Formula Injection Defense & DPDP PII Masking ---\n";
$formula1 = "=1+1";
$sanitized1 = ReportController::sanitizeExportCell($formula1);
assertCondition($sanitized1 === "'=1+1", "Formula Injection '=' neutralized with single quote prefix");

$formula2 = "+cmd|' /C calc'!A0";
$sanitized2 = ReportController::sanitizeExportCell($formula2);
assertCondition($sanitized2 === "'+cmd|' /C calc'!A0", "Formula Injection '+' neutralized with single quote prefix");

$formula3 = "-5+5";
$sanitized3 = ReportController::sanitizeExportCell($formula3);
assertCondition($sanitized3 === "'-5+5", "Formula Injection '-' neutralized with single quote prefix");

$formula4 = "@SUM(1,2)";
$sanitized4 = ReportController::sanitizeExportCell($formula4);
assertCondition($sanitized4 === "'@SUM(1,2)", "Formula Injection '@' neutralized with single quote prefix");

$safeText = "PRAYAS Machinist Requirement";
$sanitizedSafe = ReportController::sanitizeExportCell($safeText);
assertCondition($sanitizedSafe === $safeText, "Benign plain text is preserved unmodified");

$masked = ReportController::maskCandidateName("Amit Verma", 108);
assertCondition($masked === "PRAYAS-ITI-0108", "Candidate PII full name is masked as standardized reference 'PRAYAS-ITI-0108'");

// =========================================================================
// SECTION 11: MULTI-TENANT RBAC & COMPANY DATA ISOLATION
// =========================================================================
echo "\n--- Section 11: Multi-Tenant RBAC & Company Data Isolation ---\n";
$compNameA = 'P11 Test Company Alpha ' . bin2hex(random_bytes(3));
$compNameB = 'P11 Test Company Beta ' . bin2hex(random_bytes(3));
$reqCodeA  = 'REQ-P11-A-' . bin2hex(random_bytes(3));
$reqCodeB  = 'REQ-P11-B-' . bin2hex(random_bytes(3));

$pdo->prepare("INSERT INTO `companies` (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Automotive', 'MIDC Pune', 'Maharashtra', 'Pune', 'Pune', '411018', 'HR Alpha', '9876543210', :email, 'VERIFIED')")
    ->execute([':cname' => $compNameA, ':reg' => 'CIN-P11-A-' . bin2hex(random_bytes(2)), ':email' => 'p11_a_' . bin2hex(random_bytes(2)) . '@test.com']);
$compAId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO `companies` (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
    VALUES (:cname, :reg, 'Pharmaceuticals', 'MIDC Kurkumbh', 'Maharashtra', 'Pune', 'Daund', '413802', 'HR Beta', '9876543211', :email, 'VERIFIED')")
    ->execute([':cname' => $compNameB, ':reg' => 'CIN-P11-B-' . bin2hex(random_bytes(2)), ':email' => 'p11_b_' . bin2hex(random_bytes(2)) . '@test.com']);
$compBId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO `manpower_requirements` (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Alpha CNC Operator', 15, 'APPROVED', 1, NOW())")
    ->execute([':cid' => $compAId, ':code' => $reqCodeA]);
$reqAId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO `manpower_requirements` (`company_id`, `requirement_id`, `title`, `quantity_required`, `status`, `created_by`, `created_at`) 
    VALUES (:cid, :code, 'Beta Chemical Technician', 30, 'APPROVED', 1, NOW())")
    ->execute([':cid' => $compBId, ':code' => $reqCodeB]);
$reqBId = (int)$pdo->lastInsertId();

$metricsA = $reportCtrl->getComprehensiveDashboardMetrics($compAId);
$metricsB = $reportCtrl->getComprehensiveDashboardMetrics($compBId);

assertCondition($metricsA['total_vacancies'] === 15, "Company Alpha scoped query returns exactly 15 vacancies");
assertCondition($metricsA['total_requirements'] === 1, "Company Alpha scoped query returns exactly 1 requirement");
assertCondition($metricsB['total_vacancies'] === 30, "Company Beta scoped query returns exactly 30 vacancies");
assertCondition($metricsB['total_requirements'] === 1, "Company Beta scoped query returns exactly 1 requirement");

$reqsA = $reportCtrl->getRequirementWiseVacancyReport([], $compAId);
$foundAInA = false;
$foundBInA = false;
foreach ($reqsA as $r) {
    if ($r['requirement_id'] === $reqCodeA) $foundAInA = true;
    if ($r['requirement_id'] === $reqCodeB) $foundBInA = true;
}
assertCondition($foundAInA === true, "Company Alpha report contains Alpha requirement");
assertCondition($foundBInA === false, "Zero cross-tenant leakage: Company Beta requirement completely invisible to Company Alpha");

// Cleanup fixtures
try {
    $pdo->prepare("DELETE FROM `manpower_requirements` WHERE id IN ({$reqAId}, {$reqBId})")->execute();
    $pdo->prepare("DELETE FROM `companies` WHERE id IN ({$compAId}, {$compBId})")->execute();
    assertCondition(true, "Multi-tenant isolation test fixtures safely cleaned up");
} catch (Exception $e) {
    echo "  [WARN] Cleanup notice: " . $e->getMessage() . "\n";
}

// =========================================================================
// SECTION 12: EXTERNAL CANDIDATE DATABASE IMMUTABILITY
// =========================================================================
echo "\n--- Section 12: External Candidate Database Immutability ---\n";
$countPrayas = (int)$pdo->query("SELECT COUNT(*) FROM `prayas_iti_dashboard`.`students`")->fetchColumn();
$countIti    = (int)$pdo->query("SELECT COUNT(*) FROM `itistudents`.`candidates`")->fetchColumn();

assertCondition($countPrayas === 1292, "External Database 1 (prayas_iti_dashboard.students) untouched: exact 1,292 rows preserved");
assertCondition($countIti === 56, "External Database 2 (itistudents.candidates) untouched: exact 56 rows preserved");

// =========================================================================
// FINAL TALLY
// =========================================================================
echo "\n====================================================================\n";
echo "Part 11 Test Suite Summary: {$passed} Passed, {$failed} Failed\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
