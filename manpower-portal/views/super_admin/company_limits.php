<?php
/**
 * Super Admin Company Limits Management Console — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Company Limits & Capacity Governance — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

requireRole('super_admin');

$controller = new CompanyController();

// Handle Limit Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $companyId = (int)($_POST['company_id'] ?? 0);
    if ($companyId > 0) {
        $res = $controller->setCompanyLimits($companyId, [
            'max_users'        => (int)($_POST['max_users'] ?? 5),
            'max_requirements' => (int)($_POST['max_requirements'] ?? 10),
            'max_plants'       => (int)($_POST['max_plants'] ?? 5),
            'max_vacancies'    => (int)($_POST['max_vacancies'] ?? 250)
        ]);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: " . app_url('/views/super_admin/company_limits.php'));
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$companiesWithLimits = $controller->getAllCompaniesWithLimits();
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Title & Action Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title">Company Capacity & Limit Management</h1>
                <p class="supporting-text mb-0">Backend-enforced operational quotas: vacancy headcounts, requisition volumes, team sizes & factory plants</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-buildings me-1"></i> Company Directory
                </a>
            </div>
        </div>

        <!-- Explanatory Policy Callout -->
        <div class="alert alert-secondary small mb-4">
            <div class="d-flex gap-2">
                <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
                <div>
                    <strong>Backend Enforcement Architecture:</strong>
                    Feature creation (manufacturing plants, team member invitations, job requisitions, and vacancy headcounts) is verified server-side prior to database insertion.
                    If an industrial employer attempts to exceed their assigned quota, the operation is blocked with an informative capacity warning and logged to the audit ledger.
                </div>
            </div>
        </div>

        <!-- Limits Overview Table -->
        <div class="card-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-uppercase" style="color: var(--deep-navy);">
                    Allocated Quotas across <?= count($companiesWithLimits) ?> Industrial Partners
                </span>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Company Details</th>
                            <th>Vacancy Headcount</th>
                            <th>Requisitions</th>
                            <th>Team Members</th>
                            <th>Manufacturing Plants</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companiesWithLimits)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No company records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companiesWithLimits as $comp): ?>
                                <?php $ldata = $comp['limits_data']; ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $comp['id']) ?>" class="text-decoration-none" style="color: var(--deep-navy);">
                                                <?= e($comp['company_name']) ?>
                                            </a>
                                        </div>
                                        <span class="font-monospace micro-text text-primary"><?= e($comp['company_code']) ?></span> &bull; 
                                        <span class="micro-text text-muted"><?= e($comp['industry_type']) ?></span>
                                    </td>

                                    <!-- Vacancies Limit -->
                                    <td style="min-width: 140px;">
                                        <div class="d-flex justify-content-between micro-text mb-1">
                                            <span><strong><?= $ldata['usage']['vacancies'] ?></strong> / <?= $ldata['limits']['max_vacancies'] ?></span>
                                            <span class="text-muted"><?= $ldata['percentages']['vacancies'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= ($ldata['percentages']['vacancies'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $ldata['percentages']['vacancies'] ?>%;"></div>
                                        </div>
                                        <div class="micro-text text-muted mt-1">Remaining: <?= $ldata['remaining']['vacancies'] ?></div>
                                    </td>

                                    <!-- Requirements Limit -->
                                    <td style="min-width: 130px;">
                                        <div class="d-flex justify-content-between micro-text mb-1">
                                            <span><strong><?= $ldata['usage']['requirements'] ?></strong> / <?= $ldata['limits']['max_requirements'] ?></span>
                                            <span class="text-muted"><?= $ldata['percentages']['requirements'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= ($ldata['percentages']['requirements'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $ldata['percentages']['requirements'] ?>%;"></div>
                                        </div>
                                        <div class="micro-text text-muted mt-1">Remaining: <?= $ldata['remaining']['requirements'] ?></div>
                                    </td>

                                    <!-- Team Members Limit -->
                                    <td style="min-width: 130px;">
                                        <div class="d-flex justify-content-between micro-text mb-1">
                                            <span><strong><?= $ldata['usage']['users'] ?></strong> / <?= $ldata['limits']['max_users'] ?></span>
                                            <span class="text-muted"><?= $ldata['percentages']['users'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= ($ldata['percentages']['users'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $ldata['percentages']['users'] ?>%;"></div>
                                        </div>
                                        <div class="micro-text text-muted mt-1">Remaining: <?= $ldata['remaining']['users'] ?></div>
                                    </td>

                                    <!-- Plants Limit -->
                                    <td style="min-width: 130px;">
                                        <div class="d-flex justify-content-between micro-text mb-1">
                                            <span><strong><?= $ldata['usage']['plants'] ?></strong> / <?= $ldata['limits']['max_plants'] ?></span>
                                            <span class="text-muted"><?= $ldata['percentages']['plants'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= ($ldata['percentages']['plants'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $ldata['percentages']['plants'] ?>%;"></div>
                                        </div>
                                        <div class="micro-text text-muted mt-1">Remaining: <?= $ldata['remaining']['plants'] ?></div>
                                    </td>

                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editLimitModal<?= $comp['id'] ?>">
                                            <i class="bi bi-sliders me-1"></i> Edit Limits
                                        </button>

                                        <!-- Edit Limit Modal -->
                                        <div class="modal fade text-start" id="editLimitModal<?= $comp['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-6 fw-bold">
                                                                <i class="bi bi-sliders me-2 text-primary"></i>Configure Capacity: <?= e($comp['company_name']) ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Max Vacancy Headcount</label>
                                                                <input type="number" class="form-control form-control-sm" name="max_vacancies" value="<?= $ldata['limits']['max_vacancies'] ?>" min="1" max="10000" required>
                                                                <div class="micro-text text-muted">Currently active: <?= $ldata['usage']['vacancies'] ?> vacancies</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Max Requisitions</label>
                                                                <input type="number" class="form-control form-control-sm" name="max_requirements" value="<?= $ldata['limits']['max_requirements'] ?>" min="1" max="500" required>
                                                                <div class="micro-text text-muted">Currently active: <?= $ldata['usage']['requirements'] ?> requisitions</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Max Company Users</label>
                                                                <input type="number" class="form-control form-control-sm" name="max_users" value="<?= $ldata['limits']['max_users'] ?>" min="1" max="100" required>
                                                                <div class="micro-text text-muted">Currently active: <?= $ldata['usage']['users'] ?> users</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Max Manufacturing Plants</label>
                                                                <input type="number" class="form-control form-control-sm" name="max_plants" value="<?= $ldata['limits']['max_plants'] ?>" min="1" max="100" required>
                                                                <div class="micro-text text-muted">Currently active: <?= $ldata['usage']['plants'] ?> plants</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
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
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

