<?php
/**
 * Automated Validation Dossier & Alert Summary View
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + Bootstrap 5 + PRAYAS Design System
 */

declare(strict_types=1);

$pageTitle = 'Validation Summary — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/RequirementController.php';
require_once __DIR__ . '/../../includes/requirement_validation_engine.php';

requireRole(['industry_admin', 'industry_user', 'super_admin']);

$companyId = getCompanyId();
$isSuperAdmin = isSuperAdmin();

$reqId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reqId <= 0) {
    $_SESSION['flash_error'] = 'Invalid requirement ID specified.';
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

$reqCtrl = new RequirementController();
$valEngine = new RequirementValidationEngine(Database::getConnection());

// Handle Revalidation POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revalidate') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid or expired security token. Please try again.';
        header("Location: " . app_url('/views/industry/validation_summary.php?id=' . $reqId));
        exit;
    }

    $revalResult = $reqCtrl->revalidateRequirement($companyId, $reqId, getUserId());
    if ($revalResult['success']) {
        if ($revalResult['validation_status'] === 'VALIDATED') {
            $_SESSION['flash_success'] = 'Requirement revalidated successfully! All automated validation checks passed.';
        } elseif ($revalResult['validation_status'] === 'VALIDATION_WARNING') {
            $_SESSION['flash_warning'] = 'Requirement revalidated with advisory warnings. Please inspect the suggested improvements.';
        } else {
            $_SESSION['flash_error'] = 'Requirement revalidated: Validation failed with ' . count($revalResult['errors']) . ' critical issue(s).';
        }
    } else {
        $_SESSION['flash_error'] = $revalResult['message'] ?? 'Revalidation failed.';
    }

    header("Location: " . app_url('/views/industry/validation_summary.php?id=' . $reqId));
    exit;
}

// Fetch requirement and enforce multi-tenant isolation
$pdo = Database::getConnection();
if ($isSuperAdmin) {
    $stmt = $pdo->prepare("SELECT mr.*, c.company_name, mp.plant_name 
                           FROM `manpower_requirements` mr 
                           JOIN `companies` c ON c.id = mr.company_id 
                           LEFT JOIN `manufacturing_plants` mp ON mp.id = mr.plant_id 
                           WHERE mr.id = :id LIMIT 1");
    $stmt->execute([':id' => $reqId]);
} else {
    $stmt = $pdo->prepare("SELECT mr.*, c.company_name, mp.plant_name 
                           FROM `manpower_requirements` mr 
                           JOIN `companies` c ON c.id = mr.company_id 
                           LEFT JOIN `manufacturing_plants` mp ON mp.id = mr.plant_id 
                           WHERE mr.id = :id AND mr.company_id = :cid LIMIT 1");
    $stmt->execute([':id' => $reqId, ':cid' => $companyId]);
}
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    $_SESSION['flash_error'] = 'Requirement not found or access denied.';
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

// Fetch latest validation run from requirement_validation_history
$latestRun = $valEngine->getLatestValidationRun($reqId);

// If no historical run recorded yet, run an on-demand inspection without mutating state
if (!$latestRun) {
    $trStmt = $pdo->prepare("SELECT * FROM `requirement_trades` WHERE requirement_id = :rid");
    $trStmt->execute([':rid' => $reqId]);
    $trades = $trStmt->fetchAll(PDO::FETCH_ASSOC);

    $skStmt = $pdo->prepare("SELECT skill_name FROM `requirement_skills` WHERE requirement_id = :rid");
    $skStmt->execute([':rid' => $reqId]);
    $skills = $skStmt->fetchAll(PDO::FETCH_COLUMN);

    $payload = $req;
    $payload['trades'] = $trades;
    $payload['skills'] = $skills;

    $runData = $valEngine->validate($payload, (int)$req['company_id'], getUserId(), $reqId, true, 'MANUAL_REVALIDATION');
    $latestRun = $valEngine->getLatestValidationRun($reqId);
}

// Fetch history runs summary
$historyRuns = $valEngine->getValidationRunsSummary($reqId);

$details = $latestRun['details'] ?? [];
$ruleResults = $details['rule_results'] ?? [];
$valStatus = $details['validation_status'] ?? ($latestRun['is_valid'] ? 'VALIDATED' : 'VALIDATION_FAILED');
$passedCount = (int)($details['passed_count'] ?? 0);
$warningCount = (int)($details['warning_count'] ?? 0);
$errorCount = (int)($details['error_count'] ?? 0);
$criticalCount = (int)($details['critical_issues'] ?? 0);
$totalRules = $passedCount + $warningCount + $errorCount;

// Status styling
$statusBadgeClass = match ($valStatus) {
    'VALIDATED'          => 'bg-success text-white',
    'VALIDATION_WARNING' => 'bg-warning text-dark',
    'VALIDATION_FAILED'  => 'bg-danger text-white',
    default              => 'bg-secondary text-white'
};

$statusIcon = match ($valStatus) {
    'VALIDATED'          => 'bi-check-circle-fill',
    'VALIDATION_WARNING' => 'bi-exclamation-triangle-fill',
    'VALIDATION_FAILED'  => 'bi-x-circle-fill',
    default              => 'bi-question-circle-fill'
};

// Map categories to user-friendly titles
$categoryLabels = [
    'BASIC_INFO'               => ['title' => '1. Basic Requisition Information', 'icon' => 'bi-info-circle', 'step' => 1],
    'TRADE_DEMAND'             => ['title' => '2. Trade-wise Vacancies & Demand', 'icon' => 'bi-diagram-3', 'step' => 2],
    'QUALIFICATION'            => ['title' => '3. Qualification & Age Eligibility', 'icon' => 'bi-mortarboard', 'step' => 3],
    'SALARY_BENEFITS'          => ['title' => '4. Compensation & Statutory Benefits', 'icon' => 'bi-cash-stack', 'step' => 4],
    'CHRONOLOGY_DATES'         => ['title' => '5. Chronology & Milestone Deadlines', 'icon' => 'bi-calendar-event', 'step' => 5],
    'PLANT_LOCATION'           => ['title' => '6. Plant Facility & Physical Location', 'icon' => 'bi-geo-alt', 'step' => 6],
    'RECRUITMENT_COORDINATION' => ['title' => '7. Interview & Recruitment Coordination', 'icon' => 'bi-person-badge', 'step' => 7],
];

// Group rule results by category
$groupedResults = [];
foreach ($ruleResults as $rule) {
    $cat = $rule['category'] ?? 'OTHER';
    $groupedResults[$cat][] = $rule;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Dossier Breadcrumbs & Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= app_url('/views/industry/requirements.php') ?>">Requirements</a></li>
                        <li class="breadcrumb-item"><a href="<?= app_url('/views/industry/requirement_details.php?id=' . $reqId) ?>"><?= htmlspecialchars($req['requirement_id']) ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Validation Dossier</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark font-monospace fs-6"><?= htmlspecialchars($req['requirement_id']) ?></span>
                    <span class="badge <?= $statusBadgeClass ?> fs-6"><i class="bi <?= $statusIcon ?> me-1"></i><?= str_replace('_', ' ', $valStatus) ?></span>
                    <span class="badge bg-secondary"><?= htmlspecialchars($req['company_name']) ?></span>
                    <?php if (!empty($req['plant_name'])): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($req['plant_name']) ?></span>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold mb-0">Automated Validation Report: <?= htmlspecialchars($req['title']) ?></h4>
                <small class="text-muted">
                    Engine: <code><?= htmlspecialchars($details['algorithm_version'] ?? 'ALG-VAL-2026-V1.0') ?></code> &bull; 
                    Last Inspected: <?= htmlspecialchars($latestRun['validated_at'] ?? 'Just now') ?> &bull; 
                    Trigger: <span class="badge bg-light text-dark border"><?= htmlspecialchars($latestRun['trigger_type'] ?? 'MANUAL_REVALIDATION') ?></span>
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= app_url('/views/industry/requirement_details.php?id=' . $reqId) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Requisition Dossier
                </a>
                <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $reqId) ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil-square me-1"></i>Edit Requisition
                </a>
                <form method="POST" action="<?= app_url('/views/industry/validation_summary.php?id=' . $reqId) ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="revalidate">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i>Revalidate Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Total Rules</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= $totalRules ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Comprehensive Checks</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-success border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-success">Passed</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= $passedCount ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Criteria Satisfied</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-warning border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-warning-emphasis">Advisory Warnings</span>
                    <h3 class="fw-bold text-warning mb-0 mt-1"><?= $warningCount ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Non-blocking Suggestions</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-danger border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-danger">Defects / Errors</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= $errorCount ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Violations Detected</small>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="card border-0 shadow-sm text-center p-3 h-100 <?= $criticalCount > 0 ? 'border-top border-danger border-4 bg-danger-subtle' : 'bg-light' ?>">
                    <span class="text-muted small text-uppercase fw-semibold <?= $criticalCount > 0 ? 'text-danger' : '' ?>">Critical Gatekeepers</span>
                    <h3 class="fw-bold <?= $criticalCount > 0 ? 'text-danger' : 'text-success' ?> mb-0 mt-1"><?= $criticalCount ?></h3>
                    <small class="<?= $criticalCount > 0 ? 'text-danger' : 'text-muted' ?>" style="font-size: 0.75rem;">
                        <?= $criticalCount > 0 ? 'Blocks Sourcing / Approval' : 'Zero Blocking Defects' ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Executive Banner -->
        <?php if ($valStatus === 'VALIDATED'): ?>
            <div class="alert alert-success d-flex align-items-center p-3 mb-4 shadow-sm">
                <i class="bi bi-shield-check fs-2 text-success me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Requirement Fully Validated</h6>
                    <p class="small mb-0">All automated algorithmic checks passed successfully without critical violations. 
                    <em>Note: Automated validation verifies data integrity and compliance; final sourcing authorization follows the established administrative review workflow (VALIDATED &ne; APPROVED).</em></p>
                </div>
            </div>
        <?php elseif ($valStatus === 'VALIDATION_WARNING'): ?>
            <div class="alert alert-warning d-flex align-items-center p-3 mb-4 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-warning me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Validation Passed with Advisory Notices</h6>
                    <p class="small mb-0">The requisition meets the baseline parameters for processing, but contains <?= $warningCount ?> advisory condition(s) (e.g. narrow age brackets, competitive salary recommendations). Please review the suggested enhancements below.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger d-flex align-items-center p-3 mb-4 shadow-sm">
                <i class="bi bi-x-octagon-fill fs-2 text-danger me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Validation Failed — Action Required</h6>
                    <p class="small mb-0">The requisition has <?= $errorCount ?> defect(s) preventing automated approval and student matching. Click on the <strong>Suggested Correction</strong> buttons below to jump straight to the relevant wizard step and update the fields.</p>
                </div>
                <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $reqId) ?>" class="btn btn-sm btn-danger ms-3 text-nowrap">
                    <i class="bi bi-tools me-1"></i>Fix All Issues
                </a>
            </div>
        <?php endif; ?>

        <!-- Category Accordion / Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="accordion shadow-sm" id="validationAccordion">
                    <?php 
                    $catIdx = 0;
                    foreach ($categoryLabels as $catKey => $catMeta): 
                        $catIdx++;
                        $rulesInCat = $groupedResults[$catKey] ?? [];
                        $hasCatErrors = false;
                        $hasCatWarnings = false;
                        foreach ($rulesInCat as $r) {
                            if ($r['status'] === 'ERROR') $hasCatErrors = true;
                            if ($r['status'] === 'WARNING') $hasCatWarnings = true;
                        }

                        $headerBadge = '';
                        if ($hasCatErrors) {
                            $headerBadge = '<span class="badge bg-danger ms-auto me-3">Defects Detected</span>';
                        } elseif ($hasCatWarnings) {
                            $headerBadge = '<span class="badge bg-warning text-dark ms-auto me-3">Advisory Warning</span>';
                        } else {
                            $headerBadge = '<span class="badge bg-success ms-auto me-3">Passed</span>';
                        }
                    ?>
                        <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                            <h2 class="accordion-header" id="heading_<?= $catKey ?>">
                                <button class="accordion-button <?= $catIdx === 1 ? '' : 'collapsed' ?> bg-white py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $catKey ?>" aria-expanded="<?= $catIdx === 1 ? 'true' : 'false' ?>">
                                    <i class="bi <?= $catMeta['icon'] ?> text-primary me-2 fs-5"></i>
                                    <strong><?= $catMeta['title'] ?></strong>
                                    <?= $headerBadge ?>
                                </button>
                            </h2>
                            <div id="collapse_<?= $catKey ?>" class="accordion-collapse collapse <?= ($catIdx === 1 || $hasCatErrors) ? 'show' : '' ?>" data-bs-parent="#validationAccordion">
                                <div class="accordion-body bg-light p-3">
                                    <?php if (empty($rulesInCat)): ?>
                                        <div class="text-muted small">No specific rules evaluated under this category.</div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush rounded">
                                            <?php foreach ($rulesInCat as $rule): 
                                                $badgeType = match ($rule['status']) {
                                                    'PASS'    => 'bg-success',
                                                    'WARNING' => 'bg-warning text-dark',
                                                    'ERROR'   => 'bg-danger',
                                                    default   => 'bg-secondary'
                                                };
                                                $sevBadge = match ($rule['severity']) {
                                                    'Critical' => 'bg-danger-subtle text-danger border border-danger',
                                                    'High'     => 'bg-danger text-white',
                                                    'Medium'   => 'bg-warning-subtle text-dark border border-warning',
                                                    'Low'      => 'bg-info-subtle text-dark',
                                                    default    => 'bg-light text-muted'
                                                };
                                            ?>
                                                <div class="list-group-item p-3 mb-2 rounded bg-white shadow-sm border">
                                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                                        <div>
                                                            <span class="badge <?= $badgeType ?> me-1"><?= $rule['status'] ?></span>
                                                            <span class="badge <?= $sevBadge ?> me-2"><?= $rule['severity'] ?> Severity</span>
                                                            <strong class="font-monospace text-dark"><?= htmlspecialchars($rule['rule_id']) ?></strong>
                                                            <span class="text-muted small">&bull; Field: <code><?= htmlspecialchars($rule['field']) ?></code></span>
                                                        </div>
                                                        <?php if ($rule['status'] !== 'PASS'): ?>
                                                            <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $reqId . '&step=' . $catMeta['step']) ?>" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">
                                                                <i class="bi bi-pencil me-1"></i>Fix in Step <?= $catMeta['step'] ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="small mb-2 text-dark">
                                                        <strong>Observation:</strong> <?= htmlspecialchars($rule['message']) ?>
                                                    </div>

                                                    <?php if (isset($rule['current_value']) && $rule['current_value'] !== null): ?>
                                                        <div class="small text-muted mb-2">
                                                            <span class="text-secondary fw-semibold">Current Value:</span> 
                                                            <code class="bg-light p-1 rounded"><?= htmlspecialchars(is_array($rule['current_value']) ? json_encode($rule['current_value']) : (string)$rule['current_value']) ?></code>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($rule['suggested_correction'])): ?>
                                                        <div class="alert <?= $rule['status'] === 'ERROR' ? 'alert-danger' : 'alert-warning' ?> py-2 px-3 mb-0 small rounded">
                                                            <i class="bi bi-lightbulb-fill me-1"></i>
                                                            <strong>Suggested Correction:</strong> <?= htmlspecialchars($rule['suggested_correction']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Verification Policy & Historical Runs -->
            <div class="col-lg-4">
                <!-- Policy Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2 text-primary"></i>Validation Governance</h6>
                    </div>
                    <div class="card-body small">
                        <p class="text-muted mb-2">
                            PRAYAS enforces strict algorithmic validation rules prior to ITI batch matching to protect candidate placements and plant compliance.
                        </p>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i><strong>Valid != Approved:</strong> Passing rules guarantees format compliance, while administrative approval controls live student access.</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i><strong>Multi-tenant Isolation:</strong> Plant IDs and company boundaries are verified against your accredited account.</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i><strong>Automated Alerts:</strong> Milestone deadlines and validation notices are dispatched across in-app channels.</li>
                        </ul>
                    </div>
                </div>

                <!-- Validation History Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Validation History</h6>
                        <span class="badge bg-secondary"><?= count($historyRuns) ?> Run(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($historyRuns)): ?>
                            <div class="p-3 text-muted small">No prior validation runs recorded.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Trigger</th>
                                            <th>Outcome</th>
                                            <th class="text-end">P / W / E</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historyRuns as $hrun): 
                                            $outBadge = $hrun['is_valid'] ? 'bg-success' : 'bg-danger';
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($hrun['validated_at'])) ?></span>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= date('H:i:s', strtotime($hrun['validated_at'])) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.68rem;"><?= htmlspecialchars($hrun['trigger_type'] ?? 'VALIDATION') ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $outBadge ?>"><?= $hrun['is_valid'] ? 'VALID' : 'FAILED' ?></span>
                                                </td>
                                                <td class="text-end font-monospace">
                                                    <span class="text-success"><?= $hrun['passed_count'] ?></span> / 
                                                    <span class="text-warning"><?= $hrun['warning_count'] ?></span> / 
                                                    <span class="text-danger"><?= $hrun['error_count'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

