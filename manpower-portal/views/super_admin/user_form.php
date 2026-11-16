<?php
/**
 * Super Admin User Add/Edit Form — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/UserController.php';

requireRole('super_admin');

$controller = new UserController();
$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user = $userId ? $controller->getUserById($userId) : null;

$isEdit = !empty($user);
$pageTitle = ($isEdit ? 'Edit User' : 'Add New User') . ' — PRAYAS Super Admin';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $result = $controller->saveUser($_POST, $userId);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
        header("Location: " . app_url('/views/super_admin/users.php'));
        exit;
    } else {
        $error = $result['message'];
    }
}

$preselectedCompany = isset($_GET['company_id']) ? (int)$_GET['company_id'] : '';
$preselectedRoleName = isset($_GET['role']) ? trim((string)$_GET['role']) : '';

$roles = $controller->getRoles();
$companies = $controller->getCompaniesList();

$preselectedRoleId = '';
if (!empty($preselectedRoleName)) {
    foreach ($roles as $r) {
        if ($r['name'] === $preselectedRoleName) {
            $preselectedRoleId = $r['id'];
            break;
        }
    }
}

$formData = $_POST ?: ($user ?: [
    'full_name'  => '',
    'email'      => '',
    'phone'      => '',
    'role_id'    => $preselectedRoleId,
    'company_id' => $preselectedCompany,
    'status'     => 'Active'
]);

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
                <li class="breadcrumb-item active"><?= $isEdit ? 'Edit ' . e($user['full_name']) : 'New User' ?></li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title mb-0 fs-3">
                    <?= $isEdit ? 'Edit User Account' : 'Provision User Account' ?>
                </h1>
                <p class="supporting-text mb-0">Assign user credentials, role access boundaries, and organizational affiliation</p>
            </div>
            <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to User List
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-enterprise">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="">
                            <?= renderCSRFField() ?>

                            <h5 class="fw-bold mb-3" style="color: var(--deep-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                1. Personal & Contact Information
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="full_name" value="<?= e($formData['full_name']) ?>" required placeholder="e.g. Rajesh Sharma">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?= e($formData['email']) ?>" required placeholder="user@company.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Mobile Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?= e($formData['phone']) ?>" placeholder="10-digit mobile number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Account Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <?php foreach (['Active', 'Inactive', 'Suspended'] as $st): ?>
                                            <option value="<?= e($st) ?>" <?= ($formData['status'] === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color: var(--deep-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                2. Role & Organizational Association
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                                    <select class="form-select" name="role_id" id="roleSelect" required>
                                        <option value="">-- Select Role --</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id'] ?>" data-name="<?= e($r['name']) ?>" <?= ((string)$formData['role_id'] === (string)$r['id']) ? 'selected' : '' ?>>
                                                <?= e($r['label']) ?> (<?= e($r['name']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="micro-text text-muted mt-1">Determines permission boundaries and dashboard access.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Company Affiliation</label>
                                    <select class="form-select" name="company_id" id="companySelect">
                                        <option value="">-- No Company (PRAYAS Internal Staff) --</option>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= ((string)$formData['company_id'] === (string)$c['id']) ? 'selected' : '' ?>>
                                                <?= e($c['company_name']) ?> (<?= e($c['district']) ?> - <?= e($c['verification_status']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="micro-text text-muted mt-1">Mandatory for Industry Admin and Team Member accounts.</div>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color: var(--deep-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                3. Password & Security Credentials
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">
                                        <?= $isEdit ? 'New Password (Leave blank to keep existing)' : 'Initial Password *' ?>
                                    </label>
                                    <input type="password" class="form-control" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8" placeholder="Min. 8 characters (letters + numbers)">
                                    <div class="micro-text text-muted mt-1">New accounts are automatically required to change password on first login.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <?= $isEdit ? 'Save Changes' : 'Create User Account' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
