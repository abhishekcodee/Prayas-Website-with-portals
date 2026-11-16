<?php
/**
 * Automated Performance & Benchmark Testing Suite — Part 12
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: PHP CLI + MySQL + PDO
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../models/CandidateMatchingModel.php';
require_once __DIR__ . '/../models/CandidateSourceModel.php';

$pdo = Database::getConnection();

echo "========================================================\n";
echo "PRAYAS Portal - Part 12 Performance & Benchmark Tests\n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertBenchmark(string $description, float $elapsedMs, float $thresholdMs): void {
    global $testsPassed, $testsFailed;
    $status = $elapsedMs <= $thresholdMs ? "PASS" : "FAIL";
    $formattedElapsed = number_format($elapsedMs, 2);
    $formattedThreshold = number_format($thresholdMs, 2);
    
    if ($elapsedMs <= $thresholdMs) {
        echo "  [PASS] {$description} — {$formattedElapsed}ms (Threshold: < {$formattedThreshold}ms)\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$description} — Exceeded threshold: {$formattedElapsed}ms > {$formattedThreshold}ms\n";
        $testsFailed++;
    }
}

$reportCtrl = new ReportController($pdo);
$matchingModel = new CandidateMatchingModel($pdo);

// -------------------------------------------------------------
// Benchmark 1: Executive Analytics Aggregation
// -------------------------------------------------------------
echo "--- Benchmark 1: Executive Dashboard Metrics Aggregation ---\n";

$t0 = microtime(true);
$metrics = $reportCtrl->getExecutiveMetrics();
$t1 = microtime(true);
$elapsedMetrics = ($t1 - $t0) * 1000.0;

assertBenchmark("Consolidated Executive Metrics (14 KPIs across DB)", $elapsedMetrics, 200.0);

// -------------------------------------------------------------
// Benchmark 2: Algorithmic Candidate Matching (1,348 Records)
// -------------------------------------------------------------
echo "\n--- Benchmark 2: Sourcing & Algorithmic Matching (1,348 Candidates) ---\n";

// Fetch first approved requirement
$firstReq = $pdo->query("SELECT id FROM `manpower_requirements` WHERE `status` = 'APPROVED' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$reqId = (int)($firstReq['id'] ?? 1);

$t0 = microtime(true);
$matchRun = $matchingModel->executeMatchingRun($reqId, 1);
$t1 = microtime(true);
$elapsedMatching = ($t1 - $t0) * 1000.0;

assertBenchmark("Full Algorithmic Sourcing Scan (1,348 Candidates + 100-Pt Scoring)", $elapsedMatching, 1800.0);
echo "       Scanned: {$matchRun['total_candidates_scanned']} candidates | Eligible: {$matchRun['eligible_candidates_found']}\n";

// -------------------------------------------------------------
// Benchmark 3: Large Report Query Latency
// -------------------------------------------------------------
echo "\n--- Benchmark 3: Reporting Queries Execution Latency ---\n";

// 3.1 Requirement-wise Vacancy Report
$t0 = microtime(true);
$reqRows = $reportCtrl->getRequirementWiseVacancyReport();
$t1 = microtime(true);
assertBenchmark("Requirement-Wise Vacancy Report Query", ($t1 - $t0) * 1000.0, 150.0);

// 3.2 Company-Wise Consolidation Report
$t0 = microtime(true);
$compRows = $reportCtrl->getCompanyWiseRequirementReport();
$t1 = microtime(true);
assertBenchmark("Company-Wise Consolidation Report Query", ($t1 - $t0) * 1000.0, 150.0);

// 3.3 Candidate Sourcing Report (Limit 500)
$t0 = microtime(true);
$candRows = $reportCtrl->getCandidateSourcingReport();
$t1 = microtime(true);
assertBenchmark("Candidate Sourcing Report Query (500 records)", ($t1 - $t0) * 1000.0, 150.0);

// -------------------------------------------------------------
// Benchmark 4: Pagination & Offset Scaling
// -------------------------------------------------------------
echo "\n--- Benchmark 4: Pagination & Offset Scaling ---\n";

// Page 1
$t0 = microtime(true);
$p1 = $pdo->query("SELECT * FROM `candidate_matches` ORDER BY id DESC LIMIT 0, 20")->fetchAll();
$t1 = microtime(true);
assertBenchmark("Pagination Query: Page 1 (Offset 0)", ($t1 - $t0) * 1000.0, 50.0);

// Page 20 (Deep Offset)
$t0 = microtime(true);
$p20 = $pdo->query("SELECT * FROM `candidate_matches` ORDER BY id DESC LIMIT 400, 20")->fetchAll();
$t1 = microtime(true);
assertBenchmark("Pagination Query: Page 20 (Offset 400)", ($t1 - $t0) * 1000.0, 50.0);

// -------------------------------------------------------------
// Benchmark 5: Audit Log Ledger Insertion
// -------------------------------------------------------------
echo "\n--- Benchmark 5: Audit Log Ledger Write Throughput ---\n";

$t0 = microtime(true);
for ($i = 0; $i < 5; $i++) {
    $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`, `created_at`) 
        VALUES (1, 'BENCHMARK_TEST', '127.0.0.1', NOW())");
    $stmt->execute();
}
$t1 = microtime(true);
$elapsedAudit = ($t1 - $t0) * 1000.0;
assertBenchmark("5 Consecutive Audit Log Writes", $elapsedAudit, 100.0);

// Cleanup benchmark audits
$pdo->query("DELETE FROM `audit_logs` WHERE `action` = 'BENCHMARK_TEST'");

// -------------------------------------------------------------
// Benchmark Summary
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "Performance Benchmark Results: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
}

