<?php
/**
 * Super Admin Company Add/Edit Form
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

requireRole('super_admin');

$controller = new CompanyController();
$companyId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$company = $companyId ? $controller->getCompanyById($companyId) : null;

$isEdit = !empty($company);
$pageTitle = ($isEdit ? 'Edit Company' : 'Add New Company') . ' — PRAYAS Super Admin';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $result = $controller->saveCompany($_POST, $companyId);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
        header("Location: " . app_url('/views/super_admin/company_details.php?id=' . ($result['id'] ?? $companyId)));
        exit;
    } else {
        $error = $result['message'];
    }
}

$formData = $_POST ?: ($company ?: [
    'company_name'        => '',
    'registration_number' => '',
    'industry_type'       => '',
    'address'             => '',
    'state'               => 'Maharashtra',
    'district'            => '',
    'city'                => '',
    'pincode'             => '',
    'hr_contact_name'     => '',
    'hr_contact_phone'    => '',
    'hr_contact_email'    => '',
    'verification_status' => 'Verified'
]);

$industryTypes = [
    'Automotive & Auto Components', 'Industrial Automation & Robotics', 
    'Machinery & Heavy Engineering', 'Electrical & Electronics Manufacturing', 
    'Precision Tooling & CNC Machining', 'Process & Chemical Plants', 
    'Solar & Renewable Energy', 'Other Manufacturing / Assembly'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="p-4">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Breadcrumb & Header -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/companies.php') ?>">Companies</a></li>
                <li class="breadcrumb-item active"><?= $isEdit ? 'Edit ' . e($company['company_name']) : 'New Company' ?></li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--deep-navy);">
                    <?= $isEdit ? 'Edit Company Profile' : 'Add New Industrial Partner' ?>
                </h3>
                <p class="text-muted small mb-0">Configure factory credentials, plant geographic coordinates, and primary HR liaison</p>
            </div>
            <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="">
                            <?= renderCSRFField() ?>

                            <h5 class="fw-bold mb-3" style="color: var(--prayas-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                1. Organization & Factory Information
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-7">
                                    <label class="form-label small fw-semibold">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="company_name" value="<?= e($formData['company_name']) ?>" required placeholder="Full registered company name">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">Registration / CIN / GSTIN</label>
                                    <input type="text" class="form-control" name="registration_number" value="<?= e($formData['registration_number']) ?>" placeholder="e.g. U29100MH2020PTC123456">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Industry Sector <span class="text-danger">*</span></label>
                                    <select class="form-select" name="industry_type" required>
                                        <option value="">-- Select Industry Type --</option>
                                        <?php foreach ($industryTypes as $type): ?>
                                            <option value="<?= e($type) ?>" <?= ($formData['industry_type'] === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Verification Status</label>
                                    <select class="form-select" name="verification_status">
                                        <?php foreach (['Pending', 'Under Review', 'Verified', 'Active', 'Rejected', 'Suspended'] as $st): ?>
                                            <option value="<?= e($st) ?>" <?= ($formData['verification_status'] === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Plant / Factory Postal Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="address" rows="2" required placeholder="Plot No., Industrial Area, MIDC, Street"><?= e($formData['address']) ?></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="state" value="<?= e($formData['state']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="district" value="<?= e($formData['district']) ?>" required placeholder="e.g. Pune">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">City / MIDC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="city" value="<?= e($formData['city']) ?>" required placeholder="e.g. Chakan">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Pincode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="pincode" value="<?= e($formData['pincode']) ?>" required placeholder="6-digit code">
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color: var(--prayas-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                2. Primary HR Contact Liaison
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">HR Contact Person <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="hr_contact_name" value="<?= e($formData['hr_contact_name']) ?>" required placeholder="Full Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Official Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="hr_contact_email" value="<?= e($formData['hr_contact_email']) ?>" required placeholder="hr@company.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="hr_contact_phone" value="<?= e($formData['hr_contact_phone']) ?>" required placeholder="10-digit phone">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <?= $isEdit ? 'Update Company Profile' : 'Save Company Record' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

