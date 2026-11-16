<?php
/**
 * Recruitment Pipeline Analytics & Reporting Console
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL + Bootstrap 5
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/PipelineController.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

requireRole(['recruiter', 'recruitment_admin', 'super_admin']);

$pdo = Database::getConnection();
$pipelineCtrl = new PipelineController($pdo);

$reqFilter  = !empty($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : null;
$compFilter = !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null;

// Handle CSV Export
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $candidates = $pipelineCtrl->getPipelineCandidates([
        'requirement_id' => $reqFilter,
        'company_id'     => $compFilter
    ], 5000, 0);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=prayas_recruitment_pipeline_' . date('Ymd_His') . '.csv');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Match ID',
        'Candidate Name',
        'Trade',
        'Batch Year',
        'Requisition ID',
        'Requisition Title',
        'Company Name',
        'Match Score %',
        'Pipeline Stage',
        'Interview Date',
        'Interview Round',
        'Offered Designation',
        'Offered CTC',
        'Offer Ref No',
        'Joining Date',
        'Joining Status',
        'Shared with Employer'
    ]);

    foreach ($candidates as $c) {
        fputcsv($out, array_map([ReportController::class, 'sanitizeExportCell'], [
            $c['id'],
            $c['candidate_name'],
            $c['trade'],
            $c['passing_year'] ?? 'N/A',
            $c['req_code'],
            $c['req_title'],
            $c['company_name'],
            number_format((float)$c['match_score'], 1),
            $c['status'],
            $c['interview_datetime'] ?? 'N/A',
            $c['interview_round'] ?? 'N/A',
            $c['offered_designation'] ?? 'N/A',
            $c['offered_ctc'] ?? 'N/A',
            $c['letter_reference_no'] ?? 'N/A',
            $c['actual_joining_date'] ?? 'N/A',
            $c['joining_status'] ?? 'N/A',
            !empty($c['is_shared_with_industry']) ? 'Yes' : 'No'
        ]));
    }
    fclose($out);
    exit;
}

$pageTitle = 'Recruitment Pipeline Analytics & Reports — PRAYAS';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch Filters
$reqList = $pdo->query("SELECT mr.id, mr.requirement_id, mr.title, c.company_name 
    FROM `manpower_requirements` mr 
    JOIN `companies` c ON c.id = mr.company_id 
    ORDER BY mr.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$compList = $pdo->query("SELECT id, company_name FROM `companies` ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Analytics Data
$reportData = $pipelineCtrl->generateRecruitmentReport([
    'requirement_id' => $reqFilter,
    'company_id'     => $compFilter
]);
$funnel = $reportData['funnel'];
$rates  = $reportData['conversion_rates'];

$breakdown = $pipelineCtrl->getRequisitionBreakdown($compFilter);
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>Recruitment Pipeline Analytics &amp; Reports
                </h4>
                <p class="text-muted small mb-0">
                    Real-time conversion metrics, stage-by-stage attrition ratios, and requisition fulfillment health.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="/views/recruiter/recruitment_pipeline.php<?= $reqFilter ? '?requirement_id=' . $reqFilter : '' ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-diagram-3-fill me-1"></i>Active Pipeline Console
                </a>
                <?php
                    $exportQuery = $_GET;
                    $exportQuery['export'] = 'csv';
                ?>
                <a href="/views/recruiter/recruitment_reports.php?<?= http_build_query($exportQuery) ?>" class="btn btn-success btn-sm shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Pipeline CSV
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/views/recruiter/recruitment_reports.php" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Requisition</label>
                        <select name="requirement_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Requisitions (Consolidated)</option>
                            <?php foreach ($reqList as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $reqFilter === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['requirement_id']) ?> &mdash; <?= htmlspecialchars($r['title']) ?> (<?= htmlspecialchars($r['company_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Company</label>
                        <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Companies</option>
                            <?php foreach ($compList as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $compFilter === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2 align-self-end">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Apply Filter</button>
                        <a href="/views/recruiter/recruitment_reports.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Conversion Efficiency KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <span class="text-muted small fw-semibold">Shortlist Rate</span>
                    <div class="h3 fw-bold text-info my-1"><?= $rates['shortlist_rate'] ?>%</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Sourced &rarr; Shortlisted</small>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <span class="text-muted small fw-semibold">Interview Rate</span>
                    <div class="h3 fw-bold text-warning my-1"><?= $rates['interview_rate'] ?>%</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Sourced &rarr; Interviewed</small>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <span class="text-muted small fw-semibold">Selection Ratio</span>
                    <div class="h3 fw-bold text-primary my-1"><?= $rates['selection_rate'] ?>%</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Interviewed &rarr; Selected</small>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <span class="text-muted small fw-semibold">Offer Acceptance Rate</span>
                    <div class="h3 fw-bold text-teal my-1" style="color: #20c997;"><?= $rates['offer_acc_rate'] ?>%</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Offers Issued &rarr; Accepted</small>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-success">
                    <span class="text-muted small fw-semibold">Joining Conversion Rate</span>
                    <div class="h3 fw-bold text-success my-1"><?= $rates['joining_conv_rate'] ?>%</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Selected &rarr; Physically Joined</small>
                </div>
            </div>
        </div>

        <!-- Funnel Progression Visual -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-funnel-fill text-primary me-2"></i>Recruitment Pipeline Funnel Ratios</h6>
            </div>
            <div class="card-body">
                <?php
                    $maxVal = max(1, (int)($funnel['total_sourced'] ?? 1));
                    $stagesVisual = [
                        ['label' => '1. Sourced ITI Candidates', 'count' => (int)($funnel['total_sourced'] ?? 0), 'color' => 'bg-secondary'],
                        ['label' => '2. Shortlisted by Recruiters', 'count' => (int)($funnel['shortlisted'] ?? 0), 'color' => 'bg-info'],
                        ['label' => '3. Telephonically Screened', 'count' => (int)($funnel['screened'] ?? 0), 'color' => 'bg-primary'],
                        ['label' => '4. Technical Interviews Completed', 'count' => (int)($funnel['interviewed'] ?? 0), 'color' => 'bg-warning'],
                        ['label' => '5. Officially Selected', 'count' => (int)($funnel['selected'] ?? 0), 'color' => 'bg-success'],
                        ['label' => '6. Formal Offers Released', 'count' => (int)($funnel['offers_released'] ?? 0), 'color' => 'bg-teal'],
                        ['label' => '7. Offers Accepted', 'count' => (int)($funnel['offers_accepted'] ?? 0), 'color' => 'bg-success'],
                        ['label' => '8. Candidates Reported & Joined', 'count' => (int)($funnel['joined'] ?? 0), 'color' => 'bg-success']
                    ];
                ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($stagesVisual as $st): ?>
                        <?php 
                            $pct = round(($st['count'] / $maxVal) * 100, 1);
                        ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-dark"><?= htmlspecialchars($st['label']) ?></span>
                                <span class="small fw-bold text-muted"><?= $st['count'] ?> Candidates <span class="fw-normal">(<?= $pct ?>%)</span></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?= $st['color'] ?>" style="width: <?= max(2, $pct) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-2 mt-4 pt-3 border-top text-center">
                    <div class="col-4">
                        <div class="small text-muted">Total Rejected</div>
                        <div class="h5 fw-bold text-danger mb-0"><?= (int)($funnel['rejected'] ?? 0) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">On Hold / Reserve</div>
                        <div class="h5 fw-bold text-dark mb-0"><?= (int)($funnel['on_hold'] ?? 0) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Candidate No-Shows</div>
                        <div class="h5 fw-bold text-danger mb-0"><?= (int)($funnel['no_show'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requisition-Level Fulfillment Progress Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i>Requisition-Level Pipeline Fulfillment</h6>
                <span class="badge bg-light text-dark border"><?= count($breakdown) ?> Requisitions Tracked</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th>Requisition Code &amp; Title</th>
                                <th>Employer</th>
                                <th class="text-center">Demand</th>
                                <th class="text-center">Sourced</th>
                                <th class="text-center">Screened</th>
                                <th class="text-center">Interviewed</th>
                                <th class="text-center">Selected</th>
                                <th class="text-center">Offered</th>
                                <th class="text-center">Joined</th>
                                <th>Fulfillment %</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($breakdown)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">No requisition records available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($breakdown as $row): ?>
                                    <?php
                                        $demand = max(1, (int)$row['quantity_required']);
                                        $joined = (int)$row['joined_count'];
                                        $fulfillPct = round(($joined / $demand) * 100, 1);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($row['requirement_id']) ?></div>
                                            <div class="small text-dark text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['title']) ?></div>
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;"><?= htmlspecialchars($row['req_status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark"><?= htmlspecialchars($row['company_name']) ?></div>
                                        </td>
                                        <td class="text-center fw-bold"><?= (int)$row['quantity_required'] ?></td>
                                        <td class="text-center text-muted"><?= (int)$row['total_sourced'] ?></td>
                                        <td class="text-center text-primary"><?= (int)$row['screened_count'] ?></td>
                                        <td class="text-center text-warning fw-semibold"><?= (int)$row['interviewed_count'] ?></td>
                                        <td class="text-center text-success fw-semibold"><?= (int)$row['selected_count'] ?></td>
                                        <td class="text-center text-teal fw-semibold"><?= (int)$row['offered_count'] ?></td>
                                        <td class="text-center fw-bold text-success"><?= $joined ?></td>
                                        <td style="width: 140px;">
                                            <div class="d-flex align-items-center">
                                                <span class="small fw-bold me-2"><?= $fulfillPct ?>%</span>
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar <?= $fulfillPct >= 100 ? 'bg-success' : ($fulfillPct >= 50 ? 'bg-primary' : 'bg-warning') ?>" style="width: <?= min(100, (int)$fulfillPct) ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="/views/recruiter/recruitment_pipeline.php?requirement_id=<?= (int)$row['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Pipeline">
                                                <i class="bi bi-arrow-right-short"></i> Pipeline
                                            </a>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
