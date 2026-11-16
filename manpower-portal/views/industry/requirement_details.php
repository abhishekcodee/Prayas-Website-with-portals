<?php
/**
 * Detailed Manpower Requirement Dossier
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Requirement Details — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$reqId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reqId <= 0) {
    $_SESSION['flash_error'] = 'Invalid requirement ID specified.';
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

$industryCtrl = new IndustryController();
$req = $industryCtrl->getRequirementDetails($companyId, $reqId);

if (!$req) {
    $_SESSION['flash_error'] = 'Requirement not found or access denied.';
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

require_once __DIR__ . '/../../includes/requirement_validation_engine.php';
$valEngine = new RequirementValidationEngine(Database::getConnection());
$latestValRun = $valEngine->getLatestValidationRun($reqId);

$statusBadge = match ($req['status']) {
    'APPROVED'                => 'bg-success',
    'RECRUITMENT_IN_PROGRESS' => 'bg-primary',
    'FULFILLED'               => 'bg-success-subtle text-success border border-success',
    'PARTIALLY_FULFILLED'     => 'bg-info-subtle text-info border border-info',
    'NEEDS_CORRECTION'        => 'bg-danger',
    'UNDER_VALIDATION'        => 'bg-warning text-dark',
    'SUBMITTED'               => 'bg-info text-dark',
    default                   => 'bg-secondary'
};
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Dossier Top Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark font-monospace fs-6"><?= htmlspecialchars($req['requirement_id']) ?></span>
                    <span class="badge <?= $statusBadge ?> fs-6"><?= str_replace('_', ' ', $req['status']) ?></span>
                    <span class="badge bg-secondary"><?= htmlspecialchars($req['priority']) ?> Priority</span>
                </div>
                <h4 class="fw-bold mb-0"><?= htmlspecialchars($req['title']) ?></h4>
                <small class="text-muted">
                    <i class="bi bi-calendar3 me-1"></i>Submitted: <?= htmlspecialchars($req['submission_date']) ?> &bull; 
                    <i class="bi bi-clock me-1"></i>Target Joining: <?= htmlspecialchars($req['target_joining_date']) ?>
                </small>
            </div>
            <div class="btn-group">
                <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <a href="<?= app_url('/views/industry/validation_summary.php?id=' . $req['id']) ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-shield-check me-1"></i>Validation Dossier
                </a>
                <?php if (in_array($req['status'], ['DRAFT', 'NEEDS_CORRECTION'], true)): ?>
                    <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $req['id']) ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil-square me-1"></i>Edit Requisition
                    </a>
                <?php endif; ?>
                <a href="<?= app_url('/views/industry/candidate_matches.php?requirement_id=' . $req['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-people me-1"></i>View Candidates (<?= $req['pipeline']['total_matched'] ?? 0 ?>)
                </a>
            </div>
        </div>

        <?php if ($req['status'] === 'NEEDS_CORRECTION'): ?>
            <div class="alert alert-danger d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Correction Requested by PRAYAS Evaluator</h6>
                    <p class="small mb-0">Please review validation errors and update the requisition parameters in the wizard.</p>
                </div>
                <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $req['id']) ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-pencil-square me-1"></i>Edit & Fix Requisition
                </a>
            </div>
        <?php elseif ($req['status'] === 'PENDING_REVIEW'): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-hourglass-split fs-4 me-3 text-warning-emphasis"></i>
                <div>
                    <h6 class="fw-bold mb-1">Administrative Evaluation Pending</h6>
                    <p class="small mb-0">This requirement involves high volume or special commercial conditions and has been routed to PRAYAS Super Administration for allocation signoff.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sourcing Pipeline Metric Funnel -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-funnel-fill me-2 text-primary"></i>Placement Pipeline Fulfillment</h6>
            </div>
            <div class="card-body p-4">
                <div class="row text-center g-3">
                    <div class="col">
                        <div class="p-3 border rounded bg-light">
                            <span class="text-muted small text-uppercase fw-semibold">Required</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $req['quantity_required'] ?></h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 border rounded bg-light">
                            <span class="text-muted small text-uppercase fw-semibold">Matched</span>
                            <h3 class="fw-bold mb-0 text-secondary"><?= $req['pipeline']['total_matched'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 border rounded bg-light">
                            <span class="text-muted small text-uppercase fw-semibold">Shortlisted</span>
                            <h3 class="fw-bold mb-0 text-info"><?= $req['pipeline']['shortlisted'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 border rounded bg-light">
                            <span class="text-muted small text-uppercase fw-semibold">Scheduled</span>
                            <h3 class="fw-bold mb-0 text-primary"><?= $req['pipeline']['scheduled'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 border rounded bg-light">
                            <span class="text-muted small text-uppercase fw-semibold">Selected</span>
                            <h3 class="fw-bold mb-0 text-warning"><?= $req['pipeline']['selected'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 border rounded bg-light border-success">
                            <span class="text-success small text-uppercase fw-semibold">Joined</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $req['pipeline']['joined'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Requirement Specifications -->
            <div class="col-lg-8">
                <!-- Core Specifications -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-card-text me-2 text-primary"></i>Position & Remuneration Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Qualification Required</span>
                                <span class="fw-semibold"><?= htmlspecialchars($req['qualification']) ?> (<?= htmlspecialchars($req['board_type']) ?> Board)</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Experience Criteria</span>
                                <span class="fw-semibold"><?= htmlspecialchars($req['experience_required']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Passing Year Batch</span>
                                <span class="fw-semibold"><?= htmlspecialchars($req['passing_year_from'] ?? 'Any') ?> to <?= htmlspecialchars($req['passing_year_to'] ?? 'Present') ?></span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Salary Range (<?= htmlspecialchars($req['salary_type']) ?>)</span>
                                <span class="fw-bold text-success">₹<?= number_format((float)$req['min_salary']) ?> &mdash; ₹<?= number_format((float)$req['max_salary']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Working Hours & Shifts</span>
                                <span class="fw-semibold"><?= htmlspecialchars($req['working_hours']) ?> (<?= htmlspecialchars($req['shift_details']) ?>)</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Age Eligibility</span>
                                <span class="fw-semibold"><?= $req['min_age'] ?? 18 ?> &mdash; <?= $req['max_age'] ? $req['max_age'] . ' years' : 'No upper limit' ?></span>
                            </div>
                        </div>

                        <!-- Candidate Facilities & Perks -->
                        <h6 class="fw-bold small text-muted text-uppercase mt-4 mb-2">On-Site Facilities & Benefits</h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="p-2 border rounded small bg-light">
                                    <i class="bi bi-house-door me-1 text-primary"></i>Accommodation: <strong><?= $req['accommodation_provided'] ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded small bg-light">
                                    <i class="bi bi-cup-hot me-1 text-primary"></i>Food Canteen: <strong><?= $req['food_facility'] ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded small bg-light">
                                    <i class="bi bi-bus-front me-1 text-primary"></i>Transport: <strong><?= $req['transportation_provided'] ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded small bg-light">
                                    <i class="bi bi-shield-check me-1 text-success"></i>PF / ESIC: <strong><?= $req['pf_esic_benefit'] ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <h6 class="fw-bold small text-muted text-uppercase mt-4 mb-2">Job Description & Responsibilities</h6>
                        <div class="p-3 bg-light rounded text-secondary" style="white-space: pre-line;">
                            <?= htmlspecialchars($req['job_description']) ?>
                        </div>
                    </div>
                </div>

                <!-- Trades and Skills Required -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-tools me-2 text-primary"></i>Required ITI Trades & Technical Skills</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-semibold d-block mb-2">Target ITI Trades</label>
                            <?php if (!empty($req['trades'])): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($req['trades'] as $tr): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary fs-6 px-3 py-2">
                                            <?= htmlspecialchars($tr['trade_name']) ?> (<?= $tr['vacancies'] ?> Vacancies)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">All ITI Engineering Trades</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($req['skills'])): ?>
                        <div>
                            <label class="small text-muted text-uppercase fw-semibold d-block mb-2">Key Operational Skills</label>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($req['skills'] as $sk): ?>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($sk['skill_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Plant Location, Governance & Timeline -->
            <div class="col-lg-4">
                <!-- Plant Facility Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Plant / Work Site</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($req['plant_name'])): ?>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($req['plant_name']) ?></h6>
                            <span class="badge bg-secondary font-monospace mb-2"><?= htmlspecialchars($req['plant_code'] ?? '') ?></span>
                            <div class="small text-muted mb-2">
                                <?= htmlspecialchars($req['plant_address'] ?? '') ?>
                            </div>
                            <div class="small text-muted">
                                <strong><?= htmlspecialchars($req['plant_city'] ?? $req['job_location_city']) ?>, <?= htmlspecialchars($req['job_location_district']) ?></strong>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted">
                                <strong><?= htmlspecialchars($req['job_location_city']) ?>, <?= htmlspecialchars($req['job_location_district']) ?></strong>
                                <div>State: <?= htmlspecialchars($req['job_location_state']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Automated Validation Engine Summary Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Validation Status</h6>
                        <?php if ($latestValRun): 
                            $vStat = $latestValRun['details']['validation_status'] ?? ($latestValRun['is_valid'] ? 'VALIDATED' : 'VALIDATION_FAILED');
                            $vBadge = match ($vStat) {
                                'VALIDATED'          => 'bg-success',
                                'VALIDATION_WARNING' => 'bg-warning text-dark',
                                'VALIDATION_FAILED'  => 'bg-danger',
                                default              => 'bg-secondary'
                            };
                        ?>
                            <span class="badge <?= $vBadge ?>"><?= str_replace('_', ' ', $vStat) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Unchecked</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body small">
                        <?php if ($latestValRun): 
                            $vDetails = $latestValRun['details'] ?? [];
                        ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Passed Criteria:</span>
                                <span class="fw-bold text-success"><?= (int)($vDetails['passed_count'] ?? 0) ?> rules</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Advisory Warnings:</span>
                                <span class="fw-bold text-warning"><?= (int)($vDetails['warning_count'] ?? 0) ?> rules</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Critical Defects:</span>
                                <span class="fw-bold text-danger"><?= (int)($vDetails['error_count'] ?? 0) ?> rules</span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?= app_url('/views/industry/validation_summary.php?id=' . $req['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i>Inspect Validation Report
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-3">Automated algorithmic inspection has not yet been logged for this requirement.</p>
                            <div class="d-grid">
                                <a href="<?= app_url('/views/industry/validation_summary.php?id=' . $req['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-repeat me-1"></i>Run Initial Validation
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Administrative Audit Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Audit & Approval</h6>
                    </div>
                    <div class="card-body small">
                        <div class="mb-2">
                            <span class="text-muted d-block">Created By:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($req['created_by_name'] ?? 'Industry User') ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted d-block">Approved By:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($req['approved_by_name'] ?? 'Pending Evaluation') ?></span>
                        </div>
                        <?php if (!empty($req['approved_at'])): ?>
                        <div class="mb-2">
                            <span class="text-muted d-block">Approval Timestamp:</span>
                            <span><?= htmlspecialchars($req['approved_at']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status History Timeline -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Lifecycle History</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($req['history'])): ?>
                            <small class="text-muted">No state transitions recorded yet.</small>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($req['history'] as $h): ?>
                                    <li class="mb-3 border-start border-primary border-2 ps-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($h['new_status']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($h['created_at']) ?> &bull; <?= htmlspecialchars($h['changed_by_name'] ?? 'System') ?>
                                        </div>
                                        <?php if (!empty($h['remarks'])): ?>
                                            <div class="text-secondary mt-1 p-2 bg-light rounded" style="font-size: 0.8rem;">
                                                <?= htmlspecialchars($h['remarks']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

