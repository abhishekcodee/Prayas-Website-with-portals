<?php
/**
 * Super Admin User Detail & Activity View — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/UserController.php';

requireRole('super_admin');

$userId = (int)($_GET['id'] ?? 0);
$controller = new UserController();
$userRecord = $controller->getUserById($userId);

if (!$userRecord) {
    echo "<div class='page-container p-5 text-center'><h4>User not found.</h4><a href='" . app_url('/views/super_admin/users.php') . "' class='btn btn-primary mt-3'>Back to Users</a></div>";
    exit;
}

$pageTitle = e($userRecord['full_name']) . ' — User Governance Dossier';

$activities = $controller->getUserActivity($userId, 30);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb micro-text mb-0">
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/users.php') ?>">Users</a></li>
                <li class="breadcrumb-item active"><?= e($userRecord['full_name']) ?></li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title mb-0 fs-3"><?= e($userRecord['full_name']) ?></h1>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-light text-primary border small"><?= e($userRecord['role_label']) ?></span>
                    <span class="badge-status <?= ($userRecord['status'] === 'Active') ? 'badge-verified' : 'badge-suspended' ?>">
                        <?= e($userRecord['status']) ?>
                    </span>
                    <span class="supporting-text">Account created on <?= date('d M Y, H:i', strtotime($userRecord['created_at'])) ?></span>
                </div>
            </div>

            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= app_url('/views/super_admin/user_form.php?id=' . $userRecord['id']) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Edit Account
                </a>
                <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> User Directory
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: User Profile Summary -->
            <div class="col-lg-5">
                <div class="card-enterprise mb-4">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person-lines-fill me-1 text-primary"></i> Account Profile & Credentials
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-circle">
                                <?= strtoupper(substr($userRecord['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark"><?= e($userRecord['full_name']) ?></h5>
                                <div class="text-muted small"><?= e($userRecord['role_label']) ?></div>
                            </div>
                        </div>

                        <div class="row g-3 pt-3 border-top">
                            <div class="col-12">
                                <label class="micro-text text-muted">Email Address</label>
                                <div class="fw-medium text-dark"><i class="bi bi-envelope me-1 text-muted"></i><?= e($userRecord['email']) ?></div>
                            </div>
                            <div class="col-12">
                                <label class="micro-text text-muted">Phone Number</label>
                                <div class="fw-medium text-dark"><i class="bi bi-telephone me-1 text-muted"></i><?= e($userRecord['phone'] ?: 'Not Provided') ?></div>
                            </div>
                            <div class="col-12">
                                <label class="micro-text text-muted">Assigned Organization / Company</label>
                                <?php if (!empty($userRecord['company_name'])): ?>
                                    <div class="fw-semibold text-dark">
                                        <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $userRecord['company_id']) ?>" class="text-decoration-none">
                                            <?= e($userRecord['company_name']) ?>
                                        </a>
                                    </div>
                                    <span class="badge bg-light text-secondary border micro-text">Company Verification: <?= e($userRecord['company_status'] ?? 'Unknown') ?></span>
                                <?php else: ?>
                                    <div class="text-muted small fst-italic">PRAYAS Training Academy (Internal Core)</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="micro-text text-muted">Last Activity / Login</label>
                                <div class="small font-monospace text-secondary">
                                    <?= $userRecord['last_login_at'] ? date('d M Y, H:i:s', strtotime($userRecord['last_login_at'])) : 'Never logged in' ?>
                                </div>
                            </div>
                            <?php if ($userRecord['must_change_password']): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning small mb-0">
                                        <i class="bi bi-shield-exclamation me-1"></i> User is required to update their password immediately upon next sign in.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Audit Activity History -->
            <div class="col-lg-7">
                <div class="card-table">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-uppercase" style="color: var(--deep-navy);">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Recent User Activity Trail (<?= count($activities) ?>)
                        </span>
                    </div>
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table-prayas table align-middle mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th>Action</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">No activity logs recorded for this user.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $act): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold small text-dark font-monospace"><?= e($act['action']) ?></div>
                                                <?php if (!empty($act['details_json'])): ?>
                                                    <div class="text-muted font-monospace micro-text" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?= e($act['details_json']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small font-monospace text-secondary"><?= e($act['ip_address']) ?></td>
                                            <td class="small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($act['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
