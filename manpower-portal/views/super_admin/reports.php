<?php
/**
 * Master Executive Analytics & Comprehensive Reporting Console
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL + Bootstrap 5 + Chart.js
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../controllers/PipelineController.php';

requireRole(['super_admin', 'recruiter', 'recruitment_admin']);

$pdo = Database::getConnection();
$reportCtrl = new ReportController($pdo);
$role = getRoleName();

// -----------------------------------------------------------------------------
// FILTER SANITIZATION
// -----------------------------------------------------------------------------
$activeReport = trim((string)($_GET['report'] ?? 'executive'));
$filters = [
    'date_from'    => !empty($_GET['date_from']) ? trim((string)$_GET['date_from']) : null,
    'date_to'      => !empty($_GET['date_to']) ? trim((string)$_GET['date_to']) : null,
    'company_id'   => !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null,
    'trade'        => !empty($_GET['trade']) ? trim((string)$_GET['trade']) : null,
    'status'       => !empty($_GET['status']) ? trim((string)$_GET['status']) : null,
    'stage'        => !empty($_GET['stage']) ? trim((string)$_GET['stage']) : null,
    'district'     => !empty($_GET['district']) ? trim((string)$_GET['district']) : null,
    'passing_year' => !empty($_GET['passing_year']) ? trim((string)$_GET['passing_year']) : null
];

// -----------------------------------------------------------------------------
// EXPORT HANDLER (CSV, EXCEL, PRINT) WITH FORMULA INJECTION DEFENSE
// -----------------------------------------------------------------------------
if (!empty($_GET['export'])) {
    $exportFormat = $_GET['export']; // 'csv' or 'excel'
    $exportReport = $_GET['report'] ?? 'vacancies';

    $filename = 'prayas_' . preg_replace('/[^a-z0-9_]/i', '', $exportReport) . '_' . date('Ymd_His');

    if ($exportFormat === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}.csv");
        $out = fopen('php://output', 'w');

        if ($exportReport === 'vacancies' || $exportReport === 'executive') {
            fputcsv($out, ['Requisition Code', 'Job Title', 'Company', 'Vacancies', 'Status', 'City', 'District', 'Sourced', 'Shortlisted', 'Selected', 'Joined']);
            $rows = $reportCtrl->getRequirementWiseVacancyReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['requirement_id'], $r['title'], $r['company_name'], $r['quantity_required'], $r['status'],
                    $r['job_location_city'], $r['job_location_district'], $r['total_sourced'], $r['shortlisted_count'],
                    $r['selected_count'], $r['joined_count']
                ]));
            }
        } elseif ($exportReport === 'company_requirements') {
            fputcsv($out, ['Company Name', 'Industry Sector', 'District', 'City', 'Status', 'Total Requisitions', 'Total Vacancies', 'Active Requisitions', 'Fulfilled Requisitions', 'Matched Candidates', 'Total Hired']);
            $rows = $reportCtrl->getCompanyWiseRequirementReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['company_name'], $r['industry_type'], $r['district'], $r['city'], $r['verification_status'],
                    $r['total_requirements'], $r['total_vacancies'], $r['active_requirements'], $r['fulfilled_requirements'],
                    $r['total_candidates_matched'], $r['total_joined']
                ]));
            }
        } elseif ($exportReport === 'trade_demand') {
            fputcsv($out, ['Trade Name', 'Requisitions Count', 'Vacancies Demanded', 'Matched Candidates', 'Candidates Joined']);
            $rows = $reportCtrl->getTradeWiseRequirementReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['trade_name'], $r['requirement_count'], $r['vacancies_demanded'], $r['total_matched_candidates'], $r['candidates_joined']
                ]));
            }
        } elseif ($exportReport === 'plant_demand') {
            fputcsv($out, ['Plant Name', 'Plant Code', 'Company', 'City', 'District', 'Requisitions', 'Vacancies Demanded', 'Candidates Joined']);
            $rows = $reportCtrl->getPlantLocationWiseRequirementReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['plant_name'], $r['plant_code'], $r['company_name'], $r['city'], $r['district'],
                    $r['requirement_count'], $r['vacancies_demanded'], $r['total_joined']
                ]));
            }
        } elseif ($exportReport === 'candidate_sourcing') {
            fputcsv($out, ['Candidate Name', 'Trade', 'Passing Batch', 'Source', 'Score %', 'Pipeline Stage', 'Requisition Code', 'Requisition Title', 'Company']);
            $rows = $reportCtrl->getCandidateSourcingReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['candidate_name'], $r['trade'], $r['passing_year'], $r['candidate_source'], $r['match_score'],
                    $r['status'], $r['req_code'], $r['req_title'], $r['company_name']
                ]));
            }
        } elseif ($exportReport === 'interview_selections') {
            fputcsv($out, ['Candidate Name', 'Trade', 'Requisition', 'Company', 'Interview Date', 'Interview Round', 'Rating', 'Recommendation', 'Offered Designation', 'Offered CTC', 'Status']);
            $rows = $reportCtrl->getInterviewSelectionReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['candidate_name'], $r['trade'], $r['req_code'], $r['company_name'], $r['interview_datetime'],
                    $r['interview_round'], $r['overall_rating'], $r['recommendation'], $r['offered_designation'],
                    $r['offered_ctc'], $r['pipeline_stage']
                ]));
            }
        } elseif ($exportReport === 'joining_status') {
            fputcsv($out, ['Candidate Name', 'Trade', 'Requisition', 'Company', 'Designation', 'Offer Ref', 'Joining Date', 'Plant Unit', 'Joining Status', 'Onboarding Notes']);
            $rows = $reportCtrl->getJoiningStatusReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['candidate_name'], $r['trade'], $r['req_code'], $r['company_name'], $r['offered_designation'],
                    $r['letter_reference_no'], $r['actual_joining_date'], $r['plant_reporting_unit'], $r['joining_status'],
                    $r['onboarding_remarks']
                ]));
            }
        } elseif ($exportReport === 'rejected_candidates') {
            fputcsv($out, ['Candidate Name', 'Trade', 'Passing Year', 'Requisition', 'Company', 'Match Score %', 'Rejection Reason', 'Date']);
            $rows = $reportCtrl->getRejectedCandidateReport($filters);
            foreach ($rows as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    $r['candidate_name'], $r['trade'], $r['passing_year'], $r['req_code'], $r['company_name'],
                    $r['match_score'], $r['rejection_reason'], $r['matched_at']
                ]));
            }
        } elseif ($exportReport === 'overdue_audit') {
            fputcsv($out, ['Category', 'Requisition Code', 'Title', 'Company', 'Vacancies', 'Deadline', 'Status', 'Days Relative']);
            $audit = $reportCtrl->getPendingAndOverdueRequirementsReport();
            foreach ($audit['approaching_deadline'] as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    'Approaching Deadline (<=7d)', $r['requirement_id'], $r['title'], $r['company_name'], $r['quantity_required'], $r['application_deadline'], $r['status'], $r['days_left'] . ' days left'
                ]));
            }
            foreach ($audit['overdue'] as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    'Overdue Requisition', $r['requirement_id'], $r['title'], $r['company_name'], $r['quantity_required'], $r['application_deadline'], $r['status'], $r['days_overdue'] . ' days overdue'
                ]));
            }
            foreach ($audit['zero_matches'] as $r) {
                fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                    'Zero Matched Active', $r['requirement_id'], $r['title'], $r['company_name'], $r['quantity_required'], 'N/A', $r['status'], '0 candidates matched'
                ]));
            }
        } else {
            fputcsv($out, ['Report', 'Generated On', 'Status']);
            fputcsv($out, [ReportController::sanitizeExportCell($exportReport), date('Y-m-d H:i:s'), 'Export Complete']);
        }

        fclose($out);
        exit;
    } elseif ($exportFormat === 'excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename={$filename}.xls");
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<h3>PRAYAS – The Automation Academy</h3>";
        echo "<p>Report: " . htmlspecialchars($exportReport) . " | Generated: " . date('d M Y, H:i') . "</p>";
        echo "<table border='1' cellspacing='0' cellpadding='5'>";
        
        $rows = $reportCtrl->getRequirementWiseVacancyReport($filters);
        if (!empty($rows)) {
            echo "<tr style='background:#f2f2f2;'><th>Requisition</th><th>Title</th><th>Company</th><th>Vacancies</th><th>Status</th><th>Joined</th></tr>";
            foreach ($rows as $r) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['requirement_id'])) . "</td>";
                echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['title'])) . "</td>";
                echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['company_name'])) . "</td>";
                echo "<td>" . (int)$r['quantity_required'] . "</td>";
                echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['status'])) . "</td>";
                echo "<td>" . (int)$r['joined_count'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td>No records found for active filters.</td></tr>";
        }
        echo "</table></body></html>";
        exit;
    }
}

$pageTitle = 'Master Reports & Executive Analytics — PRAYAS';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Dropdown filter data
$companiesList = $pdo->query("SELECT id, company_name FROM `companies` ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$tradesList = $pdo->query("SELECT DISTINCT trade_name FROM `requirement_trades` ORDER BY trade_name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch 14 Core Executive Metrics
$execMetrics = $reportCtrl->getComprehensiveDashboardMetrics(null, $filters);

// Chart Datasets (10 Interactive Datasets)
$monthlyDemandData   = $reportCtrl->getMonthlyDemandChartData($filters);
$tradeVacancyData    = $reportCtrl->getTradeVacancyChartData($filters);
$industryDistData    = $reportCtrl->getIndustryDistributionChartData($filters);
$plantDemandData     = $reportCtrl->getPlantDemandChartData($filters);
$reqStatusData       = $reportCtrl->getRequirementStatusChartData($filters);
$matchingScoreData   = $reportCtrl->getCandidateMatchingScoreChartData($filters);
$funnelChartData     = $reportCtrl->getRecruitmentFunnelChartData($filters);
$monthlyJoiningData  = $reportCtrl->getMonthlyJoiningChartData($filters);
$offerAcceptanceData = $reportCtrl->getOfferAcceptanceChartData($filters);
$fulfillmentTrendData= $reportCtrl->getVacancyFulfillmentTrendChartData($filters);

// Operational Efficiency
$efficiency = $reportCtrl->getRecruitmentEfficiencyMetrics(null, $filters);
$conversionRates = $reportCtrl->getStageWiseConversionRates($filters);
?>

<!-- Chart.js CDN for Executive Interactive Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Console Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--prayas-navy);">
                    <i class="bi bi-graph-up text-primary me-2"></i>Executive Analytics &amp; Master Reports Hub
                </h4>
                <p class="text-muted small mb-0">
                    PRAYAS Master Intelligence &bull; Demand Trends &bull; ITI Talent Matching &bull; 16-Stage Funnel &bull; Operational TAT.
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print Report
                </button>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-success dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i>Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php
                            $csvQuery = $_GET;
                            $csvQuery['export'] = 'csv';
                            $csvQuery['report'] = $activeReport;

                            $excelQuery = $_GET;
                            $excelQuery['export'] = 'excel';
                            $excelQuery['report'] = $activeReport;
                        ?>
                        <li>
                            <a class="dropdown-item" href="/views/super_admin/reports.php?<?= http_build_query($csvQuery) ?>">
                                <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export to CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/views/super_admin/reports.php?<?= http_build_query($excelQuery) ?>">
                                <i class="bi bi-file-earmark-excel text-primary me-2"></i>Export to Excel (.xls)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:window.print()">
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export as PDF / Print
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 2: Executive Dashboard KPIs (Row of 14 Real Database Metrics) -->
        <div class="row g-2 mb-4">
            <!-- Row 1: Companies & Requirements -->
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-primary">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Companies</span>
                    <div class="h4 fw-bold text-dark my-1"><?= number_format($execMetrics['total_companies']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Registered Industries</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-info">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Requirements</span>
                    <div class="h4 fw-bold text-info my-1"><?= number_format($execMetrics['total_requirements']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= $execMetrics['active_requirements'] ?> Active / <?= $execMetrics['pending_requirements'] ?> Review</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-warning">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Total Demand</span>
                    <div class="h4 fw-bold text-warning my-1"><?= number_format($execMetrics['total_vacancies']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= number_format($execMetrics['active_vacancies']) ?> Active Openings</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-secondary">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Total Sourced</span>
                    <div class="h4 fw-bold text-secondary my-1"><?= number_format($execMetrics['total_candidates_matched']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= $execMetrics['candidates_shortlisted'] ?> Shortlisted</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-primary">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Interviewed</span>
                    <div class="h4 fw-bold text-primary my-1"><?= number_format($execMetrics['candidates_interviewed']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= $execMetrics['candidates_selected'] ?> Selected</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm p-3 text-center h-100 border-start border-4 border-success">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem;">Total Joined</span>
                    <div class="h4 fw-bold text-success my-1"><?= number_format($execMetrics['candidates_joined']) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Offers: <?= $execMetrics['offers_accepted'] ?>/<?= $execMetrics['offers_released'] ?></small>
                </div>
            </div>
        </div>

        <!-- Secondary KPI Micro-Bar -->
        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body py-2 px-3">
                <div class="row g-2 text-center align-items-center">
                    <div class="col-6 col-md-3">
                        <span class="text-muted small">Fulfillment Rate:</span>
                        <strong class="text-success ms-1"><?= $execMetrics['requirement_fulfillment_pct'] ?>%</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small">Offer Acceptance:</span>
                        <strong class="text-primary ms-1"><?= $execMetrics['offer_acceptance_pct'] ?>%</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small">Average TAT:</span>
                        <strong class="text-dark ms-1"><?= $execMetrics['avg_time_to_fulfill_days'] ?> Days</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small">Overdue / Near Deadline:</span>
                        <strong class="text-danger ms-1"><?= $execMetrics['overdue_requirements'] ?> Overdue</strong> / 
                        <strong class="text-warning ms-1"><?= $execMetrics['requirements_near_deadline'] ?> Due Soon</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Universal Multi-Factor Filter Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/views/super_admin/reports.php" class="row g-2 align-items-center">
                    <input type="hidden" name="report" value="<?= htmlspecialchars($activeReport) ?>">

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Company</label>
                        <select name="company_id" class="form-select form-select-sm">
                            <option value="">All Companies</option>
                            <?php foreach ($companiesList as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $filters['company_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Trade</label>
                        <select name="trade" class="form-select form-select-sm">
                            <option value="">All Trades</option>
                            <?php foreach ($tradesList as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $filters['trade'] === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-2 align-self-end">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Apply Filter</button>
                        <a href="/views/super_admin/reports.php?report=<?= urlencode($activeReport) ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 4: Multi-Tab Report Selector Navigation -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border flex-wrap">
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'executive' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=executive">
                    <i class="bi bi-speedometer2 me-1"></i>Executive Analytics Hub (10 Charts)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'vacancies' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=vacancies">
                    <i class="bi bi-briefcase me-1"></i>Requirement Vacancies
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'company_requirements' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=company_requirements">
                    <i class="bi bi-buildings me-1"></i>Company Consolidation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'trade_demand' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=trade_demand">
                    <i class="bi bi-tools me-1"></i>Trade Demand &amp; Supply
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'plant_demand' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=plant_demand">
                    <i class="bi bi-geo-alt me-1"></i>Plant &amp; Location Demand
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'candidate_sourcing' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=candidate_sourcing">
                    <i class="bi bi-people me-1"></i>Candidate Sourcing &amp; ITI
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'interview_selections' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=interview_selections">
                    <i class="bi bi-award me-1"></i>Interviews &amp; Selections
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'joining_status' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=joining_status">
                    <i class="bi bi-person-check me-1"></i>Joining &amp; Onboarding
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'rejected_candidates' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=rejected_candidates">
                    <i class="bi bi-x-circle me-1"></i>Attrition &amp; Dropouts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeReport === 'overdue_audit' ? 'active' : '' ?>" href="/views/super_admin/reports.php?report=overdue_audit">
                    <i class="bi bi-exclamation-triangle me-1"></i>Deadlines &amp; Overdue Audit
                </a>
            </li>
        </ul>

        <!-- =================================================================== -->
        <!-- TAB 1: EXECUTIVE ANALYTICS (10 INTERACTIVE CHARTS & METRICS) -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'executive'): ?>
            <!-- Row of Charts 1 & 2 -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>1. Monthly Manpower Demand Trend (Vacancies)
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartMonthlyDemand"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-pie-chart-fill text-info me-2"></i>2. Trade-Wise Vacancy Distribution
                        </div>
                        <div class="card-body p-4 d-flex align-items-center justify-content-center">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartTradeVacancy"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row of Charts 3 & 4 -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-diagram-3-fill text-warning me-2"></i>3. Industry Sector Demand Distribution
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartIndustryDist"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-building-fill-check text-success me-2"></i>4. Plant-Wise Manpower Demand
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartPlantDemand"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row of Charts 5 & 6 -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-clock-history text-secondary me-2"></i>5. Requirement Status Breakdown
                        </div>
                        <div class="card-body p-4 d-flex align-items-center justify-content-center">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartReqStatus"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-award-fill text-primary me-2"></i>6. Candidate Matching Score Bands
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartMatchingScores"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row of Charts 7 & 8 -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-funnel-fill text-teal me-2" style="color: #20c997;"></i>7. Recruitment Pipeline Funnel
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartPipelineFunnel"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-graph-up-arrow text-success me-2"></i>8. Monthly Candidate Joining Trend
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartMonthlyJoining"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row of Charts 9 & 10 -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-envelope-check-fill text-primary me-2"></i>9. Offer Release vs. Acceptance Trend
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartOfferAcceptance"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-trophy-fill text-warning me-2"></i>10. Vacancy Fulfillment Percentage Trend
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; width: 100%;">
                                <canvas id="chartFulfillmentTrend"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Efficiency TAT Breakdown Strip -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-speedometer text-primary me-2"></i>Recruitment Operational Efficiency &amp; Stage Turnaround Times (TAT)
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Sourced &rarr; Shortlist</div>
                            <div class="h5 fw-bold text-info mb-0"><?= $efficiency['sourced_to_shortlist_days'] ?> Days</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Shortlist &rarr; Interview</div>
                            <div class="h5 fw-bold text-warning mb-0"><?= $efficiency['shortlist_to_interview_days'] ?> Days</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Interview &rarr; Select</div>
                            <div class="h5 fw-bold text-primary mb-0"><?= $efficiency['interview_to_selection_days'] ?> Days</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Select &rarr; Offer</div>
                            <div class="h5 fw-bold text-secondary mb-0"><?= $efficiency['selection_to_offer_days'] ?> Days</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Offer &rarr; Joining</div>
                            <div class="h5 fw-bold text-teal mb-0" style="color: #20c997;"><?= $efficiency['offer_to_joining_days'] ?> Days</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="small text-muted">Total Time-to-Hire</div>
                            <div class="h5 fw-bold text-success mb-0"><?= $efficiency['total_time_to_hire_days'] ?> Days</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Render Script for all 10 Charts -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 1. Monthly Demand
                new Chart(document.getElementById('chartMonthlyDemand'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($monthlyDemandData['labels']) ?>,
                        datasets: [{
                            label: 'Vacancies Demanded',
                            data: <?= json_encode($monthlyDemandData['data']) ?>,
                            backgroundColor: '#0d6efd',
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                // 2. Trade Vacancy
                new Chart(document.getElementById('chartTradeVacancy'), {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($tradeVacancyData['labels']) ?>,
                        datasets: [{
                            data: <?= json_encode($tradeVacancyData['data']) ?>,
                            backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#d63384', '#6c757d']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                // 3. Industry Dist
                new Chart(document.getElementById('chartIndustryDist'), {
                    type: 'pie',
                    data: {
                        labels: <?= json_encode($industryDistData['labels']) ?>,
                        datasets: [{
                            data: <?= json_encode($industryDistData['data']) ?>,
                            backgroundColor: ['#fd7e14', '#0dcaf0', '#198754', '#6610f2', '#ffc107', '#adb5bd']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                // 4. Plant Demand
                new Chart(document.getElementById('chartPlantDemand'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($plantDemandData['labels']) ?>,
                        datasets: [{
                            label: 'Vacancies',
                            data: <?= json_encode($plantDemandData['data']) ?>,
                            backgroundColor: '#198754',
                            borderRadius: 4
                        }]
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true } } }
                });

                // 5. Requirement Status
                new Chart(document.getElementById('chartReqStatus'), {
                    type: 'polarArea',
                    data: {
                        labels: <?= json_encode($reqStatusData['labels']) ?>,
                        datasets: [{
                            data: <?= json_encode($reqStatusData['data']) ?>,
                            backgroundColor: ['#6c757d', '#ffc107', '#0d6efd', '#198754', '#dc3545']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // 6. Matching Score Bands
                new Chart(document.getElementById('chartMatchingScores'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($matchingScoreData['labels']) ?>,
                        datasets: [{
                            label: 'Candidate Count',
                            data: <?= json_encode($matchingScoreData['data']) ?>,
                            backgroundColor: ['#198754', '#20c997', '#0dcaf0', '#ffc107', '#6c757d'],
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                // 7. Funnel
                new Chart(document.getElementById('chartPipelineFunnel'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($funnelChartData['labels']) ?>,
                        datasets: [{
                            label: 'Candidates',
                            data: <?= json_encode($funnelChartData['data']) ?>,
                            backgroundColor: ['#6c757d', '#0dcaf0', '#ffc107', '#0d6efd', '#198754'],
                            borderRadius: 4
                        }]
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
                });

                // 8. Monthly Joining
                new Chart(document.getElementById('chartMonthlyJoining'), {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($monthlyJoiningData['labels']) ?>,
                        datasets: [{
                            label: 'Joined Placements',
                            data: <?= json_encode($monthlyJoiningData['data']) ?>,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                // 9. Offer Acceptance
                new Chart(document.getElementById('chartOfferAcceptance'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($offerAcceptanceData['labels']) ?>,
                        datasets: [
                            { label: 'Offers Released', data: <?= json_encode($offerAcceptanceData['released']) ?>, backgroundColor: '#0d6efd' },
                            { label: 'Offers Accepted', data: <?= json_encode($offerAcceptanceData['accepted']) ?>, backgroundColor: '#20c997' }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                // 10. Fulfillment Trend
                new Chart(document.getElementById('chartFulfillmentTrend'), {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($fulfillmentTrendData['labels']) ?>,
                        datasets: [{
                            label: 'Fulfillment %',
                            data: <?= json_encode($fulfillmentTrendData['fulfillment_pct']) ?>,
                            borderColor: '#fd7e14',
                            backgroundColor: 'rgba(253, 126, 20, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
                });
            });
            </script>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 2: REQUIREMENT-WISE VACANCY REPORT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'vacancies'): ?>
            <?php $rows = $reportCtrl->getRequirementWiseVacancyReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Requirement-Wise Vacancy &amp; Progress Report</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Requisitions</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Requisition Code</th>
                                    <th>Title &amp; Company</th>
                                    <th>Location</th>
                                    <th class="text-center">Vacancies</th>
                                    <th>Status</th>
                                    <th class="text-center">Sourced</th>
                                    <th class="text-center">Shortlisted</th>
                                    <th class="text-center">Selected</th>
                                    <th class="text-center">Joined</th>
                                    <th>Fulfillment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="10" class="text-center py-4 text-muted">No records match current filter criteria.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <?php
                                            $vac = max(1, (int)$r['quantity_required']);
                                            $joined = (int)$r['joined_count'];
                                            $pct = round(($joined / $vac) * 100, 1);
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($r['requirement_id']) ?></td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($r['title']) ?></div>
                                                <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($r['company_name']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($r['job_location_city'] ?: $r['job_location_district'] ?: 'N/A') ?></td>
                                            <td class="text-center fw-bold"><?= (int)$r['quantity_required'] ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['status']) ?></span></td>
                                            <td class="text-center text-secondary"><?= (int)$r['total_sourced'] ?></td>
                                            <td class="text-center text-info"><?= (int)$r['shortlisted_count'] ?></td>
                                            <td class="text-center text-primary"><?= (int)$r['selected_count'] ?></td>
                                            <td class="text-center fw-bold text-success"><?= $joined ?></td>
                                            <td style="width: 140px;">
                                                <div class="d-flex align-items-center">
                                                    <span class="small fw-bold me-2"><?= $pct ?>%</span>
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-primary' : 'bg-warning') ?>" style="width: <?= min(100, (int)$pct) ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 3: COMPANY-WISE REQUIREMENT CONSOLIDATION -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'company_requirements'): ?>
            <?php $rows = $reportCtrl->getCompanyWiseRequirementReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-buildings text-primary me-2"></i>Company-Wise Requirement Consolidation</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Companies</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Company Name</th>
                                    <th>Industry Sector</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-center">Requisitions</th>
                                    <th class="text-center">Vacancies</th>
                                    <th class="text-center">Active Reqs</th>
                                    <th class="text-center">Candidates Matched</th>
                                    <th class="text-center">Total Hired</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="9" class="text-center py-4 text-muted">No company records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['company_name']) ?></td>
                                            <td><?= htmlspecialchars($r['industry_type'] ?? 'General') ?></td>
                                            <td><?= htmlspecialchars($r['city'] ?: $r['district'] ?: 'N/A') ?></td>
                                            <td><span class="badge bg-light text-success border"><?= htmlspecialchars($r['verification_status']) ?></span></td>
                                            <td class="text-center fw-bold"><?= (int)$r['total_requirements'] ?></td>
                                            <td class="text-center text-primary fw-bold"><?= (int)$r['total_vacancies'] ?></td>
                                            <td class="text-center text-warning"><?= (int)$r['active_requirements'] ?></td>
                                            <td class="text-center text-secondary"><?= (int)$r['total_candidates_matched'] ?></td>
                                            <td class="text-center fw-bold text-success"><?= (int)$r['total_joined'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 4: TRADE DEMAND & SUPPLY -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'trade_demand'): ?>
            <?php $rows = $reportCtrl->getTradeWiseRequirementReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-tools text-primary me-2"></i>Trade-Wise Demand vs. Sourcing Supply</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Technical Trades</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>ITI Technical Trade</th>
                                    <th class="text-center">Requisitions Demanding</th>
                                    <th class="text-center">Vacancies Demanded</th>
                                    <th class="text-center">Candidates Matched</th>
                                    <th class="text-center">Candidates Joined</th>
                                    <th>Supply Coverage Ratio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No trade demand records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <?php
                                            $dem = max(1, (int)$r['vacancies_demanded']);
                                            $matched = (int)$r['total_matched_candidates'];
                                            $ratio = round(($matched / $dem), 1);
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['trade_name']) ?></td>
                                            <td class="text-center"><?= (int)$r['requirement_count'] ?></td>
                                            <td class="text-center fw-bold text-primary"><?= (int)$r['vacancies_demanded'] ?></td>
                                            <td class="text-center fw-bold text-info"><?= $matched ?></td>
                                            <td class="text-center fw-bold text-success"><?= (int)$r['candidates_joined'] ?></td>
                                            <td>
                                                <span class="badge <?= $ratio >= 2.0 ? 'bg-success' : ($ratio >= 1.0 ? 'bg-primary' : 'bg-warning') ?>">
                                                    <?= $ratio ?>x Pool
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 5: PLANT & LOCATION DEMAND -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'plant_demand'): ?>
            <?php $rows = $reportCtrl->getPlantLocationWiseRequirementReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-geo-alt text-primary me-2"></i>Plant &amp; Location Manpower Demand Breakdown</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Plants / Units</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Plant Name</th>
                                    <th>Plant Code</th>
                                    <th>Company</th>
                                    <th>City &amp; District</th>
                                    <th class="text-center">Requisitions</th>
                                    <th class="text-center">Vacancies Demanded</th>
                                    <th class="text-center">Placements Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No plant location records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['plant_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['plant_code']) ?></span></td>
                                            <td class="small fw-semibold"><?= htmlspecialchars($r['company_name']) ?></td>
                                            <td><?= htmlspecialchars($r['city'] . ', ' . $r['district']) ?></td>
                                            <td class="text-center fw-bold"><?= (int)$r['requirement_count'] ?></td>
                                            <td class="text-center text-primary fw-bold"><?= (int)$r['vacancies_demanded'] ?></td>
                                            <td class="text-center text-success fw-bold"><?= (int)$r['total_joined'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 6: CANDIDATE SOURCING & ITI REPORT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'candidate_sourcing'): ?>
            <?php 
                $rows = $reportCtrl->getCandidateSourcingReport($filters); 
                $itiRows = $reportCtrl->getItiWiseCandidateReport($filters);
                $tiers = $reportCtrl->getCandidateQualityTiersReport($filters);
            ?>
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-mortarboard text-primary me-2"></i>ITI Institution Sourcing Repository
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted">
                                        <tr>
                                            <th>ITI Institute Name</th>
                                            <th class="text-center">Students</th>
                                            <th class="text-center">Trades</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($itiRows as $iti): ?>
                                            <tr>
                                                <td class="small fw-semibold"><?= htmlspecialchars($iti['iti_name']) ?></td>
                                                <td class="text-center fw-bold text-primary"><?= (int)$iti['total_students'] ?></td>
                                                <td class="text-center text-muted"><?= (int)$iti['trades_offered'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Active Sourced Candidates (Sample <?= count($rows) ?>)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 480px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted sticky-top">
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Trade &amp; Batch</th>
                                            <th>Score</th>
                                            <th>Stage</th>
                                            <th>Requisition</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rows)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No candidate records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($rows as $c): ?>
                                                <tr>
                                                    <td class="fw-bold small"><?= htmlspecialchars($c['candidate_name']) ?></td>
                                                    <td class="small">
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['trade']) ?></span>
                                                        <span class="ms-1"><?= htmlspecialchars((string)($c['passing_year'] ?? '')) ?></span>
                                                    </td>
                                                    <td class="fw-bold text-primary small"><?= number_format((float)$c['match_score'], 1) ?>%</td>
                                                    <td><span class="badge bg-secondary small"><?= htmlspecialchars($c['status']) ?></span></td>
                                                    <td class="small text-truncate" style="max-width: 140px;"><?= htmlspecialchars($c['req_code']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quality Tiers Strip -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-star-fill text-warning me-2"></i>Candidate Sourcing Quality Tiers &amp; Progression
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Match Quality Tier</th>
                                    <th class="text-center">Candidates</th>
                                    <th class="text-center">Avg Match Score</th>
                                    <th class="text-center">Shortlisted</th>
                                    <th class="text-center">Selected</th>
                                    <th class="text-center">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tiers as $t): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($t['quality_tier']) ?></td>
                                        <td class="text-center fw-bold"><?= (int)$t['candidate_count'] ?></td>
                                        <td class="text-center text-primary fw-bold"><?= $t['avg_score'] ?>%</td>
                                        <td class="text-center text-info"><?= (int)$t['shortlisted_count'] ?></td>
                                        <td class="text-center text-primary"><?= (int)$t['selected_count'] ?></td>
                                        <td class="text-center text-success fw-bold"><?= (int)$t['joined_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 7: INTERVIEW & SELECTIONS REPORT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'interview_selections'): ?>
            <?php $rows = $reportCtrl->getInterviewSelectionReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-award text-primary me-2"></i>Interview &amp; Selection Consolidated Report</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Evaluated Candidates</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Candidate Name</th>
                                    <th>Trade</th>
                                    <th>Company &amp; Requisition</th>
                                    <th>Interview Round &amp; Date</th>
                                    <th>Rating</th>
                                    <th>Recommendation</th>
                                    <th>Offered CTC</th>
                                    <th>Pipeline Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No interview records match current filters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['candidate_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['trade']) ?></span></td>
                                            <td>
                                                <div class="small fw-semibold"><?= htmlspecialchars($r['company_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r['req_code']) ?></small>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-warning"><?= date('d M Y, H:i', strtotime($r['interview_datetime'])) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r['interview_round']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= (int)($r['overall_rating'] ?? 3) ?> / 5</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($r['recommendation'] ?? 'Evaluated') ?></span>
                                            </td>
                                            <td class="fw-bold text-teal" style="color: #20c997;">
                                                <?= !empty($r['offered_ctc']) ? '₹' . number_format((float)$r['offered_ctc']) : '—' ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($r['pipeline_stage']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 8: JOINING & ONBOARDING STATUS REPORT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'joining_status'): ?>
            <?php $rows = $reportCtrl->getJoiningStatusReport($filters); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-check text-success me-2"></i>Ground Physical Joining &amp; Onboarding Status</h6>
                    <span class="badge bg-light text-dark border"><?= count($rows) ?> Candidates Tracked</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Trade</th>
                                    <th>Employer</th>
                                    <th>Designation</th>
                                    <th>Offer Ref</th>
                                    <th>Reporting Date</th>
                                    <th>Plant Unit</th>
                                    <th>Joining Status</th>
                                    <th>Onboarding Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="9" class="text-center py-4 text-muted">No joining records match active filters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['candidate_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['trade']) ?></span></td>
                                            <td class="small fw-semibold"><?= htmlspecialchars($r['company_name']) ?></td>
                                            <td class="small"><?= htmlspecialchars($r['offered_designation'] ?? 'Technician') ?></td>
                                            <td class="small text-primary"><?= htmlspecialchars($r['letter_reference_no'] ?? '—') ?></td>
                                            <td class="small fw-bold text-dark"><?= date('d M Y', strtotime($r['actual_joining_date'])) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($r['plant_reporting_unit']) ?></td>
                                            <td>
                                                <span class="badge <?= $r['joining_status'] === 'Joined' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= htmlspecialchars($r['joining_status']) ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($r['onboarding_remarks'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 9: ATTRITION & DROPOUTS REPORT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'rejected_candidates'): ?>
            <?php 
                $rejected = $reportCtrl->getRejectedCandidateReport($filters); 
                $dropouts = $reportCtrl->getDropoutCandidateReport($filters);
            ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold text-danger">
                            <i class="bi bi-x-circle text-danger me-2"></i>Disqualified / Rejected Candidates (<?= count($rejected) ?>)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 480px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted sticky-top">
                                        <tr>
                                            <th>Candidate &amp; Trade</th>
                                            <th>Requisition</th>
                                            <th>Rejection Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rejected)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No rejected candidate records.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($rejected as $r): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold small"><?= htmlspecialchars($r['candidate_name']) ?></div>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($r['trade']) ?></span>
                                                    </td>
                                                    <td class="small"><?= htmlspecialchars($r['req_code']) ?></td>
                                                    <td class="small text-danger"><?= htmlspecialchars($r['rejection_reason'] ?: 'Did not clear criteria') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold text-dark">
                            <i class="bi bi-person-x text-warning me-2"></i>Candidate Dropouts &amp; No-Shows (<?= count($dropouts) ?>)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 480px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted sticky-top">
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Stage</th>
                                            <th>Dropout / Extension Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($dropouts)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No candidate dropout events recorded.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($dropouts as $d): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold small"><?= htmlspecialchars($d['candidate_name']) ?></div>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($d['trade']) ?></span>
                                                    </td>
                                                    <td><span class="badge bg-dark small"><?= htmlspecialchars($d['dropout_stage']) ?></span></td>
                                                    <td class="small text-muted"><?= htmlspecialchars($d['dropout_reason']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- TAB 10: DEADLINES & OVERDUE REQUIREMENTS AUDIT -->
        <!-- =================================================================== -->
        <?php if ($activeReport === 'overdue_audit'): ?>
            <?php $audit = $reportCtrl->getPendingAndOverdueRequirementsReport(); ?>
            <div class="row g-4">
                <!-- Approaching Deadlines -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                        <div class="card-header bg-white py-3 fw-bold text-warning">
                            <i class="bi bi-hourglass-split me-2"></i>Requisitions Near Deadline (&le; 7 Days Remaining)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted">
                                        <tr>
                                            <th>Requisition</th>
                                            <th>Company</th>
                                            <th>Deadline</th>
                                            <th>Vacancies</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($audit['approaching_deadline'])): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No requisitions due in the next 7 days.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($audit['approaching_deadline'] as $r): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-primary small"><?= htmlspecialchars($r['requirement_id']) ?></div>
                                                        <small class="text-dark"><?= htmlspecialchars($r['title']) ?></small>
                                                    </td>
                                                    <td class="small"><?= htmlspecialchars($r['company_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark"><?= date('d M Y', strtotime($r['application_deadline'])) ?></span>
                                                        <small class="d-block text-muted"><?= $r['days_left'] ?> days remaining</small>
                                                    </td>
                                                    <td class="fw-bold text-center"><?= (int)$r['quantity_required'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Requisitions -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                        <div class="card-header bg-white py-3 fw-bold text-danger">
                            <i class="bi bi-exclamation-octagon me-2"></i>Overdue Requisitions (Deadline Passed)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted">
                                        <tr>
                                            <th>Requisition</th>
                                            <th>Company</th>
                                            <th>Deadline</th>
                                            <th>Vacancies</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($audit['overdue'])): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No overdue active requisitions.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($audit['overdue'] as $r): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-danger small"><?= htmlspecialchars($r['requirement_id']) ?></div>
                                                        <small class="text-dark"><?= htmlspecialchars($r['title']) ?></small>
                                                    </td>
                                                    <td class="small"><?= htmlspecialchars($r['company_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?= date('d M Y', strtotime($r['application_deadline'])) ?></span>
                                                        <small class="d-block text-danger"><?= $r['days_overdue'] ?> days overdue</small>
                                                    </td>
                                                    <td class="fw-bold text-center"><?= (int)$r['quantity_required'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zero Match Requisitions -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-secondary">
                        <div class="card-header bg-white py-3 fw-bold text-secondary">
                            <i class="bi bi-person-x-fill me-2"></i>Active Requisitions with Zero Matched Candidates (Action Required)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase text-muted">
                                        <tr>
                                            <th>Requisition</th>
                                            <th>Company</th>
                                            <th>Created Date</th>
                                            <th>Vacancies Demanded</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($audit['zero_matches'])): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">All active requisitions currently have candidate matches.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($audit['zero_matches'] as $r): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold text-primary small"><?= htmlspecialchars($r['requirement_id']) ?></span>
                                                        <div class="text-dark small"><?= htmlspecialchars($r['title']) ?></div>
                                                    </td>
                                                    <td class="small"><?= htmlspecialchars($r['company_name']) ?></td>
                                                    <td class="small text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                                    <td class="fw-bold text-center"><?= (int)$r['quantity_required'] ?></td>
                                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($r['status']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
