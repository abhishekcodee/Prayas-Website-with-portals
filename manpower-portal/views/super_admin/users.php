<?php
/**
 * Super Admin User & Administrator Management Console — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'User & Administrator Management — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/UserController.php';

requireRole('super_admin');

$controller = new UserController();

// Handle Actions (Activate, Suspend, Reset Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'status' && $userId > 0) {
        $newStatus = (string)($_POST['status'] ?? '');
        $res = $controller->setUserStatus($userId, $newStatus);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'reset_password' && $userId > 0) {
        $newPass = (string)($_POST['new_password'] ?? '');
        $res = $controller->resetPassword($userId, $newPass);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Filters & Pagination
$filters = [
    'search'     => trim((string)($_GET['search'] ?? '')),
    'role'       => trim((string)($_GET['role'] ?? '')),
    'status'     => trim((string)($_GET['status'] ?? '')),
    'company_id' => trim((string)($_GET['company_id'] ?? ''))
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$result = $controller->getUsers($filters, $page, $perPage);
$users = $result['data'];
$totalPages = $result['total_pages'];
$totalCount = $result['total'];

$roles = $controller->getRoles();
$companies = $controller->getCompaniesList();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Title & Action Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title">
                    <?= ($filters['role'] === 'industry_admin') ? 'Company Administrators Management' : 'User Accounts & Access Management' ?>
                </h1>
                <p class="supporting-text mb-0">Manage platform users, company administrator assignments, role boundaries & authentication status</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= app_url('/views/super_admin/roles_permissions.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-shield-lock me-1"></i> Permissions Matrix
                </a>
                <a href="<?= app_url('/views/super_admin/user_form.php' . ($filters['role'] ? '?role=' . urlencode($filters['role']) : '')) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i> Add New User
                </a>
            </div>
        </div>

        <!-- Role Quick Switcher Pills -->
        <div class="d-flex gap-2 mb-3 overflow-auto pb-1">
            <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-sm <?= empty($filters['role']) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                All Users (<?= number_format($totalCount) ?>)
            </a>
            <a href="<?= app_url('/views/super_admin/users.php?role=industry_admin') ?>" class="btn btn-sm <?= ($filters['role'] === 'industry_admin') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-person-badge me-1"></i> Company Administrators
            </a>
            <a href="<?= app_url('/views/super_admin/users.php?role=recruiter') ?>" class="btn btn-sm <?= ($filters['role'] === 'recruiter') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-briefcase me-1"></i> Recruiters
            </a>
            <a href="<?= app_url('/views/super_admin/users.php?role=super_admin') ?>" class="btn btn-sm <?= ($filters['role'] === 'super_admin') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-shield-check me-1"></i> Super Admins
            </a>
        </div>

        <!-- Filter Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/super_admin/users.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label micro-text text-muted mb-1">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control" name="search" value="<?= e($filters['search']) ?>" placeholder="Search by name, email, phone...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label micro-text text-muted mb-1">Role Filter</label>
                        <select class="form-select form-select-sm" name="role">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r['name']) ?>" <?= ($filters['role'] === $r['name']) ? 'selected' : '' ?>><?= e($r['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Account Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['Active', 'Inactive', 'Suspended'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= ($filters['status'] === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-1 mt-auto">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-uppercase" style="color: var(--deep-navy);">
                    Showing <?= count($users) ?> of <?= number_format($totalCount) ?> Portal Users
                </span>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Name & Contact</th>
                            <th>Role & Access Level</th>
                            <th>Company Affiliation</th>
                            <th>Account Status</th>
                            <th>Created / Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                                        <div class="empty-state-title">No User Accounts Found</div>
                                        <p class="empty-state-text">No user records match your selected filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="<?= app_url('/views/super_admin/user_details.php?id=' . $u['id']) ?>" class="text-decoration-none" style="color: var(--deep-navy);">
                                                <?= e($u['full_name']) ?>
                                            </a>
                                            <?php if ($u['id'] === getUserId()): ?>
                                                <span class="badge bg-secondary-subtle text-secondary micro-text ms-1">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="micro-text text-muted"><i class="bi bi-envelope me-1"></i><?= e($u['email']) ?></div>
                                        <?php if (!empty($u['phone'])): ?>
                                            <div class="micro-text text-muted"><i class="bi bi-telephone me-1"></i><?= e($u['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-primary border fw-semibold small">
                                            <?= e($u['role_label']) ?>
                                        </span>
                                        <?php if ($u['must_change_password']): ?>
                                            <div class="micro-text text-danger mt-1"><i class="bi bi-shield-exclamation me-1"></i>Password Change Required</div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($u['company_name'])): ?>
                                            <div class="fw-semibold small text-dark"><?= e($u['company_name']) ?></div>
                                            <div class="micro-text text-muted">ID: #<?= $u['company_id'] ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">PRAYAS Academy Internal</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge-status <?= ($u['status'] === 'Active') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.72rem;">
                                            <?= e($u['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="small text-muted font-monospace">
                                            <?= $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : 'Never logged in' ?>
                                        </div>
                                        <div class="micro-text text-muted">Created: <?= date('d M Y', strtotime($u['created_at'])) ?></div>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('/views/super_admin/user_details.php?id=' . $u['id']) ?>" class="btn btn-outline-primary" title="View Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= app_url('/views/super_admin/user_form.php?id=' . $u['id']) ?>" class="btn btn-outline-secondary" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                <li>
                                                    <button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $u['id'] ?>">
                                                        <i class="bi bi-key me-2"></i> Reset Password
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <?php if ($u['id'] !== getUserId()): ?>
                                                    <?php if ($u['status'] === 'Active'): ?>
                                                        <li>
                                                            <form method="POST" action="" onsubmit="return confirm('Suspend user <?= addslashes($u['full_name']) ?>?');">
                                                                <?= renderCSRFField() ?>
                                                                <input type="hidden" name="action" value="status">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="status" value="Suspended">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="bi bi-slash-circle me-2"></i> Suspend Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" action="">
                                                                <?= renderCSRFField() ?>
                                                                <input type="hidden" name="action" value="status">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="status" value="Active">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="bi bi-check-circle me-2"></i> Activate Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <!-- Password Reset Modal -->
                                        <div class="modal fade text-start" id="resetPassModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold fs-6">Direct Password Reset: <?= e($u['full_name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">
                                                                Provide a new secure temporary password for <strong><?= e($u['email']) ?></strong>. 
                                                                The password change flag will be set to force the user to update their credentials on their next sign in.
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">New Temporary Password *</label>
                                                                <input type="text" class="form-control form-control-sm font-monospace" name="new_password" value="Prayas@<?= date('Y') ?>!" required minlength="8">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-warning">Apply Password Reset</button>
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

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center py-2">
                    <span class="micro-text text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= app_url('/views/super_admin/users.php?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
