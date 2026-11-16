<?php
/**
 * Industry Partner Reports & Analytics Console
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL + Bootstrap 5 + Chart.js
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: /views/auth/login.php");
    exit;
}

$pdo = Database::getConnection();
$industryCtrl = new IndustryController($pdo);
$reportCtrl = new ReportController($pdo);

// Strict Company Scoping Filters
$filters = [
    'company_id' => $companyId,
    'date_from'  => !empty($_GET['date_from']) ? trim((string)$_GET['date_from']) : null,
    'date_to'    => !empty($_GET['date_to']) ? trim((string)$_GET['date_to']) : null,
    'trade'      => !empty($_GET['trade']) ? trim((string)$_GET['trade']) : null,
    'status'     => !empty($_GET['status']) ? trim((string)$_GET['status']) : null
];

// Handle Exports with strict company isolation & formula injection defense
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];

    if ($exportType === 'requirements_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=prayas_my_requirements_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Requisition Code', 'Title', 'Plant Code', 'Vacancies Required', 'Qualification', 'Experience', 'Min Salary', 'Max Salary', 'Deadline', 'Status', 'Submission Date']);

        $stmt = $pdo->prepare("SELECT mr.requirement_id, mr.title, mp.plant_code, mr.quantity_required, 
                mr.qualification, mr.experience_required, mr.min_salary, mr.max_salary, 
                mr.application_deadline, mr.status, mr.submission_date
            FROM `manpower_requirements` mr
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
            WHERE mr.company_id = :cid
            ORDER BY mr.id DESC");
        $stmt->execute([':cid' => $companyId]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                $row['requirement_id'], $row['title'], $row['plant_code'] ?? 'HQ', $row['quantity_required'],
                $row['qualification'], $row['experience_required'], $row['min_salary'], $row['max_salary'],
                $row['application_deadline'], $row['status'], $row['submission_date']
            ]));
        }
        fclose($out);
        exit;
    } elseif ($exportType === 'candidates_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=prayas_matched_candidates_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Candidate Reference', 'Trade', 'Passing Batch', 'Requirement Code', 'Match Score %', 'Pipeline Status', 'Matched Date']);

        $stmt = $pdo->prepare("SELECT cm.id, cm.candidate_name, cm.trade, cm.passing_year, mr.requirement_id, 
                cm.match_score, cm.status, cm.matched_at
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
            WHERE mr.company_id = :cid AND (cm.is_shared_with_industry = 1 OR cm.status IN ('Shortlisted', 'Scheduled', 'Completed', 'Selected', 'Offer Released', 'Offer Accepted', 'Joined'))
            ORDER BY cm.match_score DESC");
        $stmt->execute([':cid' => $companyId]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // DPDP Compliance: Candidate name masked for employer privacy
            $maskedName = ReportController::maskCandidateName((string)$row['candidate_name'], (int)$row['id']);
            fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
                $maskedName, $row['trade'], $row['passing_year'], $row['requirement_id'],
                $row['match_score'], $row['status'], $row['matched_at']
            ]));
        }
        fclose($out);
        exit;
    } elseif ($exportType === 'requirements_excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=prayas_my_requirements_" . date('Ymd_His') . ".xls");
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<h3>PRAYAS – The Automation Academy</h3>";
        echo "<p>Company Manpower Requisitions | Generated: " . date('d M Y, H:i') . "</p>";
        echo "<table border='1' cellspacing='0' cellpadding='5'>";
        echo "<tr style='background:#f2f2f2;'><th>Requisition</th><th>Title</th><th>Vacancies</th><th>Status</th><th>Deadline</th></tr>";
        
        $stmt = $pdo->prepare("SELECT mr.requirement_id, mr.title, mr.quantity_required, mr.status, mr.application_deadline 
            FROM `manpower_requirements` mr 
            WHERE mr.company_id = :cid 
            ORDER BY mr.id DESC");
        $stmt->execute([':cid' => $companyId]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['requirement_id'])) . "</td>";
            echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['title'])) . "</td>";
            echo "<td>" . (int)$r['quantity_required'] . "</td>";
            echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell($r['status'])) . "</td>";
            echo "<td>" . htmlspecialchars(ReportController::sanitizeExportCell((string)($r['application_deadline'] ?? 'Open'))) . "</td>";
            echo "</tr>";
        }
        echo "</table></body></html>";
        exit;
    }
}

$pageTitle = 'Reports & Analytics — PRAYAS Industry Portal';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Company-Scoped Executive Metrics
$execMetrics = $reportCtrl->getComprehensiveDashboardMetrics($companyId, $filters);

// Trade Demand Summary
$tradeRows = $reportCtrl->getTradeWiseRequirementReport($filters, $companyId);

// Plant Demand Summary
$plantRows = $reportCtrl->getPlantLocationWiseRequirementReport($filters, $companyId);

// Requirement List
$reqRows = $reportCtrl->getRequirementWiseVacancyReport($filters, $companyId);

// Candidate Sourcing (Company Scoped)
$candidateMatches = $reportCtrl->getCandidateSourcingReport($filters, $companyId);

// Chart Data (Company Scoped)
$tradeChartData = $reportCtrl->getTradeVacancyChartData($filters, $companyId);
$funnelChartData= $reportCtrl->getRecruitmentFunnelChartData($filters, $companyId);
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Console Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--prayas-navy);"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Company Recruitment Reports &amp; Analytics</h4>
                <p class="text-muted small mb-0">Demands &bull; Trade Distribution &bull; Plant Breakdowns &bull; Candidate Sourcing Velocity.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print Report
                </button>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i>Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="/views/industry/reports.php?export=requirements_csv"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Requirements (CSV)</a></li>
                        <li><a class="dropdown-item" href="/views/industry/reports.php?export=candidates_csv"><i class="bi bi-people me-2 text-primary"></i>Candidates (CSV)</a></li>
                        <li><a class="dropdown-item" href="/views/industry/reports.php?export=requirements_excel"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Requirements (Excel)</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Executive KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary h-100">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Total Vacancy Demand</span>
                    <h3 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($execMetrics['total_vacancies']) ?></h3>
                    <small class="text-muted"><?= $execMetrics['total_requirements'] ?> Requisitions (<?= $execMetrics['active_requirements'] ?> Active)</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-info h-100">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Talent Sourced &amp; Matched</span>
                    <h3 class="fw-bold mb-0 mt-1 text-info"><?= number_format($execMetrics['total_candidates_matched']) ?></h3>
                    <small class="text-muted"><?= $execMetrics['candidates_shortlisted'] ?> Shortlisted for review</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning h-100">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Evaluated &amp; Selected</span>
                    <h3 class="fw-bold mb-0 mt-1 text-warning"><?= number_format($execMetrics['candidates_selected']) ?></h3>
                    <small class="text-muted"><?= $execMetrics['candidates_interviewed'] ?> Interviews conducted</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-success h-100">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Fulfillment Rate</span>
                    <h3 class="fw-bold mb-0 mt-1 text-success"><?= $execMetrics['requirement_fulfillment_pct'] ?>%</h3>
                    <small class="text-muted"><?= $execMetrics['candidates_joined'] ?> Placements Joined Plant</small>
                </div>
            </div>
        </div>

        <!-- Interactive Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>My Company Vacancies by Technical Trade
                    </div>
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        <div style="height: 260px; width: 100%;">
                            <canvas id="industryTradeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="bi bi-funnel-fill text-info me-2"></i>Recruitment Pipeline Conversion Funnel
                    </div>
                    <div class="card-body p-4">
                        <div style="height: 260px; width: 100%;">
                            <canvas id="industryFunnelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plant & Trade Summaries Row -->
        <div class="row g-4 mb-4">
            <!-- Plant Summary -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="bi bi-geo-alt-fill text-success me-2"></i>Plant &amp; Facility Manpower Demand
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-muted">
                                    <tr>
                                        <th>Plant Name</th>
                                        <th>Code</th>
                                        <th class="text-center">Requisitions</th>
                                        <th class="text-center">Demand</th>
                                        <th class="text-center">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($plantRows)): ?>
                                        <tr><td colspan="5" class="text-center py-3 text-muted small">No plant demands recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($plantRows as $p): ?>
                                            <tr>
                                                <td class="small fw-semibold"><?= htmlspecialchars($p['plant_name']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['plant_code']) ?></span></td>
                                                <td class="text-center small"><?= (int)$p['requirement_count'] ?></td>
                                                <td class="text-center fw-bold text-primary small"><?= (int)$p['vacancies_demanded'] ?></td>
                                                <td class="text-center fw-bold text-success small"><?= (int)$p['total_joined'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade Summary -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="bi bi-tools text-warning me-2"></i>Trade-Wise Demand &amp; Placements
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-muted">
                                    <tr>
                                        <th>Technical Trade</th>
                                        <th class="text-center">Vacancies</th>
                                        <th class="text-center">Matched</th>
                                        <th class="text-center">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tradeRows)): ?>
                                        <tr><td colspan="4" class="text-center py-3 text-muted small">No trade demands recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($tradeRows as $t): ?>
                                            <tr>
                                                <td class="small fw-semibold"><?= htmlspecialchars($t['trade_name']) ?></td>
                                                <td class="text-center fw-bold text-primary small"><?= (int)$t['vacancies_demanded'] ?></td>
                                                <td class="text-center fw-bold text-info small"><?= (int)$t['total_matched_candidates'] ?></td>
                                                <td class="text-center fw-bold text-success small"><?= (int)$t['candidates_joined'] ?></td>
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

        <!-- Requirements Breakdown Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>My Manpower Requisitions Status</h6>
                <span class="badge bg-light text-dark border"><?= count($reqRows) ?> Requisitions</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th>Requisition Code</th>
                                <th>Job Title</th>
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
                            <?php if (empty($reqRows)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted small">No requisition records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reqRows as $r): ?>
                                    <?php
                                        $vac = max(1, (int)$r['quantity_required']);
                                        $joined = (int)$r['joined_count'];
                                        $pct = round(($joined / $vac) * 100, 1);
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-primary small"><?= htmlspecialchars($r['requirement_id']) ?></td>
                                        <td class="fw-semibold text-dark small"><?= htmlspecialchars($r['title']) ?></td>
                                        <td class="text-center fw-bold small"><?= (int)$r['quantity_required'] ?></td>
                                        <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars($r['status']) ?></span></td>
                                        <td class="text-center text-secondary small"><?= (int)$r['total_sourced'] ?></td>
                                        <td class="text-center text-info small"><?= (int)$r['shortlisted_count'] ?></td>
                                        <td class="text-center text-primary small"><?= (int)$r['selected_count'] ?></td>
                                        <td class="text-center fw-bold text-success small"><?= $joined ?></td>
                                        <td style="width: 140px;">
                                            <div class="d-flex align-items-center">
                                                <span class="small fw-bold me-2"><?= $pct ?>%</span>
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, (int)$pct) ?>%"></div>
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

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Trade Donut
    new Chart(document.getElementById('industryTradeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($tradeChartData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($tradeChartData['data']) ?>,
                backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#d63384', '#6c757d']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Chart 2: Funnel Bar
    new Chart(document.getElementById('industryFunnelChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($funnelChartData['labels']) ?>,
            datasets: [{
                label: 'Candidates Count',
                data: <?= json_encode($funnelChartData['data']) ?>,
                backgroundColor: ['#6c757d', '#0dcaf0', '#ffc107', '#0d6efd', '#198754'],
                borderRadius: 4
            }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
