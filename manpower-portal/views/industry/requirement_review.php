<?php
/**
 * Requirement Specification Review Console
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Requirement Review — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/RequirementController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: /views/auth/login.php");
    exit;
}

$reqId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reqId <= 0) {
    header("Location: /views/industry/requirements.php");
    exit;
}

$pdo = Database::getConnection();
$reqCtrl = new RequirementController($pdo);
$req = $reqCtrl->getRequirementById($companyId, $reqId);

if (!$req) {
    $_SESSION['flash_error'] = 'Requirement not found or access denied.';
    header("Location: /views/industry/requirements.php");
    exit;
}

// Handle Direct Submission from Review Screen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_submit') {
    requireCSRF();

    // Prepare payload from stored draft or requisition
    $payload = $req;
    $res = $reqCtrl->submitRequirement($companyId, $payload, $reqId);

    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        header("Location: /views/industry/requirement_details.php?id=" . $reqId);
        exit;
    } else {
        $_SESSION['flash_error'] = $res['message'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-card-checklist me-2 text-primary"></i>Specification Review: <?= htmlspecialchars($req['requirement_id']) ?></h4>
                <p class="text-muted small mb-0">Confirm requirement parameters, trade allocations, and compensation benchmarks.</p>
            </div>
            <div class="btn-group">
                <a href="/views/industry/requirements.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <?php if (in_array($req['status'], ['DRAFT', 'NEEDS_CORRECTION'], true)): ?>
                    <a href="/views/industry/requirement_form.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pencil me-1"></i>Edit Parameters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm w-100 mb-4">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Requisition Blueprint</h6>
                <span class="badge bg-secondary"><?= htmlspecialchars($req['status']) ?></span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($req['title']) ?></h4>
                        <div class="small text-muted mb-3">
                            <span><i class="bi bi-building me-1"></i>Plant: <?= htmlspecialchars($req['plant_name'] ?? 'Headquarters') ?></span> &bull; 
                            <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($req['job_location_city']) ?>, <?= htmlspecialchars($req['job_location_district']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="small text-muted text-uppercase fw-semibold d-block">Cumulative Demand</span>
                        <h2 class="fw-bold text-primary mb-0"><?= $req['quantity_required'] ?> Vacancies</h2>
                    </div>
                </div>

                <!-- Trades -->
                <h6 class="text-uppercase text-muted fw-bold small mb-2 border-bottom pb-1">Trade Allocation</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Trade Discipline</th>
                                <th class="text-center">Vacancies</th>
                                <th>Board</th>
                                <th>Passing Batch</th>
                                <th>Experience</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($req['trades'] as $tr): ?>
                                <tr class="small">
                                    <td class="fw-bold"><?= htmlspecialchars($tr['trade_name']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= $tr['vacancies'] ?></td>
                                    <td><?= htmlspecialchars($tr['board_type']) ?></td>
                                    <td><?= htmlspecialchars($tr['passing_year_from'] ?? '2022') ?> - <?= htmlspecialchars($tr['passing_year_to'] ?? '2026') ?></td>
                                    <td><?= htmlspecialchars($tr['min_experience'] ?? 'Fresher') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Compensation & Logistics -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <span class="small text-muted d-block">Monthly Salary Range:</span>
                            <strong class="text-success fs-5">₹<?= number_format((float)$req['min_salary']) ?> &mdash; ₹<?= number_format((float)$req['max_salary']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <span class="small text-muted d-block">Application Deadline:</span>
                            <strong class="fs-6"><?= htmlspecialchars($req['application_deadline']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <span class="small text-muted d-block">Target Joining Date:</span>
                            <strong class="text-primary fs-6"><?= htmlspecialchars($req['target_joining_date']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <h6 class="text-uppercase text-muted fw-bold small mb-2 border-bottom pb-1">Job Responsibilities</h6>
                <div class="p-3 bg-light rounded text-secondary small mb-4" style="white-space: pre-line;">
                    <?= htmlspecialchars($req['job_description']) ?>
                </div>

                <?php if ($req['status'] === 'DRAFT'): ?>
                    <form method="POST" action="/views/industry/requirement_review.php?id=<?= $req['id'] ?>" class="text-end border-top pt-3">
                        <?= renderCSRFField() ?>
                        <input type="hidden" name="action" value="confirm_submit">
                        <a href="/views/industry/requirement_form.php?id=<?= $req['id'] ?>" class="btn btn-outline-secondary me-2">Back to Wizard</a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-send-check me-1"></i>Confirm & Submit Requirement
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

