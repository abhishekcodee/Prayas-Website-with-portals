<?php
/**
 * Super Admin Company Detail & Governance Dossier — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

requireRole('super_admin');

$companyId = (int)($_GET['id'] ?? 0);
$controller = new CompanyController();
$company = $controller->getCompanyById($companyId);

if (!$company) {
    echo "<div class='page-container p-5 text-center'><h4>Company not found.</h4><a href='" . app_url('/views/super_admin/companies.php') . "' class='btn btn-primary mt-3'>Back to Directory</a></div>";
    exit;
}

$pageTitle = e($company['company_name']) . ' — Governance Dossier';

// Handle State & Limit Mutations via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    $reason = $_POST['rejection_reason'] ?? null;
    $approvalRemarks = $_POST['approval_remarks'] ?? null;

    if ($action === 'verify') {
        $res = $controller->updateVerificationStatus($companyId, 'Verified', null, $approvalRemarks);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'under_review') {
        $res = $controller->updateVerificationStatus($companyId, 'Under Review');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'reject') {
        $res = $controller->updateVerificationStatus($companyId, 'Rejected', $reason);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'suspend') {
        $res = $controller->updateVerificationStatus($companyId, 'Suspended');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'reactivate') {
        $res = $controller->updateVerificationStatus($companyId, 'Active');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'update_limits') {
        $res = $controller->setCompanyLimits($companyId, [
            'max_users'        => (int)($_POST['max_users'] ?? 5),
            'max_requirements' => (int)($_POST['max_requirements'] ?? 10),
            'max_plants'       => (int)($_POST['max_plants'] ?? 5),
            'max_vacancies'    => (int)($_POST['max_vacancies'] ?? 250)
        ]);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: " . app_url('/views/super_admin/company_details.php?id=' . $companyId));
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch Subsystem Data
$limitsData = $controller->getCompanyLimits($companyId);
$companyUsers = $controller->getCompanyAdmins($companyId);
$companyRequirements = $controller->getCompanyRequirements($companyId);
$auditHistory = $controller->getCompanyAuditHistory($companyId, 50);

// Status Badge Helper
$st = $company['verification_status'];
$badgeClass = match ($st) {
    'Verified', 'Active' => 'badge-verified',
    'Pending'            => 'badge-pending',
    'Under Review'       => 'badge-under-review',
    'Rejected'           => 'badge-rejected',
    'Suspended'          => 'badge-suspended',
    default              => 'bg-light text-dark'
};
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb micro-text mb-0">
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/companies.php') ?>">Companies</a></li>
                <li class="breadcrumb-item active"><?= e($company['company_name']) ?></li>
            </ol>
        </nav>

        <!-- Company Header Dossier Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box icon-box-primary" style="width: 56px; height: 56px; font-size: 1.8rem;">
                            <i class="bi bi-buildings"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h1 class="dashboard-title mb-0 fs-3"><?= e($company['company_name']) ?></h1>
                                <span class="badge-status <?= $badgeClass ?>"><?= e($st) ?></span>
                                <span class="font-monospace micro-text text-primary fw-bold bg-light px-2 py-1 border rounded">
                                    <?= e($company['company_code']) ?>
                                </span>
                            </div>
                            <div class="supporting-text mt-1">
                                <span><i class="bi bi-briefcase me-1"></i><?= e($company['industry_type']) ?></span> &bull; 
                                <span><i class="bi bi-geo-alt me-1"></i><?= e($company['district']) ?>, <?= e($company['state']) ?></span> &bull; 
                                <span>Registered on <?= date('d M Y, H:i', strtotime($company['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Workflow Action Buttons -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= app_url('/views/super_admin/company_form.php?id=' . $company['id']) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>

                        <?php if (in_array($st, ['Pending', 'Under Review', 'Rejected', 'Suspended'])): ?>
                            <form method="POST" action="" onsubmit="return confirm('Verify and approve this company?');" class="d-inline">
                                <?= renderCSRFField() ?>
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-patch-check me-1"></i> Approve & Verify
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($st === 'Pending'): ?>
                            <form method="POST" action="" class="d-inline">
                                <?= renderCSRFField() ?>
                                <input type="hidden" name="action" value="under_review">
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-hourglass-split me-1"></i> Under Review
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($st !== 'Rejected'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($st, ['Verified', 'Active'])): ?>
                            <form method="POST" action="" onsubmit="return confirm('Suspend this active employer account?');" class="d-inline">
                                <?= renderCSRFField() ?>
                                <input type="hidden" name="action" value="suspend">
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-slash-circle me-1"></i> Suspend
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($st === 'Suspended'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Reactivate this suspended company account?');" class="d-inline">
                                <?= renderCSRFField() ?>
                                <input type="hidden" name="action" value="reactivate">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-arrow-repeat me-1"></i> Reactivate
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6 Required Tabs Navigation -->
        <div class="card-enterprise">
            <div class="card-header border-bottom-0 pb-0">
                <ul class="nav nav-tabs card-header-tabs" id="companyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                            <i class="bi bi-info-circle me-1"></i> Basic Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-contact-btn" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab">
                            <i class="bi bi-person-lines-fill me-1"></i> Contact Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-verification-btn" data-bs-toggle="tab" data-bs-target="#tab-verification" type="button" role="tab">
                            <i class="bi bi-shield-check me-1"></i> Verification & Compliance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-admin-btn" data-bs-toggle="tab" data-bs-target="#tab-admin" type="button" role="tab">
                            <i class="bi bi-people me-1"></i> Administration & Users (<?= count($companyUsers) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-limits-btn" data-bs-toggle="tab" data-bs-target="#tab-limits" type="button" role="tab">
                            <i class="bi bi-sliders me-1"></i> Limits & Capacity
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-activity-btn" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i> Activity History (<?= count($auditHistory) ?>)
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4 tab-content" id="companyTabContent">
                <!-- TAB 1: BASIC INFORMATION -->
                <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted w-40 small">Company Legal Name:</th>
                                    <td class="fw-bold text-dark"><?= e($company['company_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Company System Code:</th>
                                    <td><span class="font-monospace fw-bold text-primary"><?= e($company['company_code']) ?></span></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Registration Number (CIN):</th>
                                    <td class="font-monospace"><?= e($company['registration_number'] ?: 'Not Provided') ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Industry Sector:</th>
                                    <td><span class="badge bg-light text-dark border"><?= e($company['industry_type']) ?></span></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Registration Date:</th>
                                    <td><?= date('d F Y, H:i:s', strtotime($company['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Corporate Website:</th>
                                    <td>
                                        <?php if (!empty($company['website'])): ?>
                                            <a href="<?= e($company['website']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                <?= e($company['website']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">None specified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6 border-start ps-md-4">
                            <h6 class="fw-bold mb-2 text-dark">Profile Description & Operations</h6>
                            <p class="small text-muted mb-4">
                                <?= !empty($company['description']) ? nl2br(e($company['description'])) : 'No detailed company description provided.' ?>
                            </p>

                            <h6 class="fw-bold mb-2 text-dark">Operational Footprint</h6>
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="micro-text text-muted">Active Plants</div>
                                        <div class="fs-4 fw-bold text-primary"><?= (int)$company['total_plants'] ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="micro-text text-muted">Total Requisitions</div>
                                        <div class="fs-4 fw-bold text-primary"><?= (int)$company['total_requirements'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CONTACT INFORMATION -->
                <div class="tab-pane fade" id="tab-contact" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-badge me-2 text-primary"></i>Primary HR Liaison</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted w-35 small">Contact Person:</th>
                                    <td class="fw-bold text-dark"><?= e($company['hr_contact_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Official Email:</th>
                                    <td><a href="mailto:<?= e($company['hr_contact_email']) ?>"><?= e($company['hr_contact_email']) ?></a></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">Contact Phone:</th>
                                    <td><?= e($company['hr_contact_phone']) ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6 border-start ps-md-4">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-geo-alt me-2 text-primary"></i>Registered Plant / Office Address</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted w-35 small">Address:</th>
                                    <td class="small"><?= nl2br(e($company['address'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">City & District:</th>
                                    <td class="small"><?= e($company['city']) ?>, <?= e($company['district']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted small">State & Pincode:</th>
                                    <td class="small"><?= e($company['state']) ?> — <?= e($company['pincode']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: VERIFICATION & COMPLIANCE -->
                <div class="tab-pane fade" id="tab-verification" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-patch-check me-2 text-primary"></i>Verification State & Review Decision</h6>
                            <div class="p-3 border rounded bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted">Current Verification Status:</span>
                                    <span class="badge-status <?= $badgeClass ?>"><?= e($st) ?></span>
                                </div>
                                <?php if (!empty($company['verified_by_name'])): ?>
                                    <div class="small text-muted">
                                        Reviewed by: <strong><?= e($company['verified_by_name']) ?></strong> on <?= date('d M Y, H:i', strtotime($company['verified_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($company['rejection_reason'])): ?>
                                <div class="alert alert-danger mb-3" role="alert">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-x-circle me-1"></i>Recorded Rejection Remarks:</h6>
                                    <p class="small mb-0"><?= nl2br(e($company['rejection_reason'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Action Box -->
                            <div class="card p-3 border">
                                <h6 class="fw-bold mb-2">Take Administrative Decision</h6>
                                <p class="small text-muted mb-3">Record an approval or rejection decision. Notifications will be dispatched automatically.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="" onsubmit="return confirm('Approve this company?');" class="d-inline">
                                        <?= renderCSRFField() ?>
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check2-circle me-1"></i> Approve & Verify
                                        </button>
                                    </form>

                                    <form method="POST" action="" class="d-inline">
                                        <?= renderCSRFField() ?>
                                        <input type="hidden" name="action" value="under_review">
                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-hourglass-split me-1"></i> Move to Under Review
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="bi bi-x-circle me-1"></i> Reject Registration
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 border-start ps-lg-4">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Supporting Documents & Credentials</h6>
                            <div class="alert alert-secondary small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Standard institutional verification requires validation of the corporate registration number (CIN) and authorized HR phone/email liaison.
                            </div>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Corporate CIN:</span>
                                    <span class="font-monospace fw-semibold"><?= e($company['registration_number'] ?: 'N/A') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>HR Official Contact:</span>
                                    <span><?= e($company['hr_contact_email']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Plant Geographic Verification:</span>
                                    <span><?= e($company['district']) ?>, <?= e($company['state']) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: ADMINISTRATION & USERS -->
                <div class="tab-pane fade" id="tab-admin" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Assigned Administrators & Company Users</h6>
                            <p class="micro-text text-muted mb-0">Users affiliated with <?= e($company['company_name']) ?> in the platform</p>
                        </div>
                        <a href="<?= app_url('/views/super_admin/user_form.php?company_id=' . $company['id'] . '&role=industry_admin') ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Add / Assign Admin
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>Phone</th>
                                    <th>Assigned Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($companyUsers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No users currently affiliated with this company.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($companyUsers as $u): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                                            <td><?= e($u['email']) ?></td>
                                            <td><?= e($u['phone'] ?: 'N/A') ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= e($u['role_label']) ?></span></td>
                                            <td>
                                                <span class="badge-status <?= ($u['status'] === 'Active') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.7rem;">
                                                    <?= e($u['status']) ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted font-monospace"><?= $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : 'Never' ?></td>
                                            <td class="text-end">
                                                <a href="<?= app_url('/views/super_admin/user_details.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View User">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= app_url('/views/super_admin/user_form.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit User">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 5: LIMITS & CONFIGURATION (CRITICAL REQUIREMENT) -->
                <div class="tab-pane fade" id="tab-limits" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Company Capacity Allocation & Limit Enforcement</h6>
                            <p class="micro-text text-muted mb-0">Enforced strictly on backend. Current usage vs maximum allocated thresholds.</p>
                        </div>
                    </div>

                    <!-- Usage vs Limit Cards -->
                    <div class="row g-3 mb-4">
                        <!-- Vacancy Headcount Limit -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">Total Vacancies</span>
                                    <span class="micro-text text-muted"><?= $limitsData['usage']['vacancies'] ?> / <?= $limitsData['limits']['max_vacancies'] ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= ($limitsData['percentages']['vacancies'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $limitsData['percentages']['vacancies'] ?>%;"></div>
                                </div>
                                <div class="micro-text text-muted mt-2 d-flex justify-content-between">
                                    <span>Remaining: <strong><?= $limitsData['remaining']['vacancies'] ?></strong></span>
                                    <span><?= $limitsData['percentages']['vacancies'] ?>% used</span>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements Limit -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">Requisitions</span>
                                    <span class="micro-text text-muted"><?= $limitsData['usage']['requirements'] ?> / <?= $limitsData['limits']['max_requirements'] ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= ($limitsData['percentages']['requirements'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $limitsData['percentages']['requirements'] ?>%;"></div>
                                </div>
                                <div class="micro-text text-muted mt-2 d-flex justify-content-between">
                                    <span>Remaining: <strong><?= $limitsData['remaining']['requirements'] ?></strong></span>
                                    <span><?= $limitsData['percentages']['requirements'] ?>% used</span>
                                </div>
                            </div>
                        </div>

                        <!-- Team Users Limit -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">Company Users</span>
                                    <span class="micro-text text-muted"><?= $limitsData['usage']['users'] ?> / <?= $limitsData['limits']['max_users'] ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= ($limitsData['percentages']['users'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $limitsData['percentages']['users'] ?>%;"></div>
                                </div>
                                <div class="micro-text text-muted mt-2 d-flex justify-content-between">
                                    <span>Remaining: <strong><?= $limitsData['remaining']['users'] ?></strong></span>
                                    <span><?= $limitsData['percentages']['users'] ?>% used</span>
                                </div>
                            </div>
                        </div>

                        <!-- Manufacturing Plants Limit -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">Active Plants</span>
                                    <span class="micro-text text-muted"><?= $limitsData['usage']['plants'] ?> / <?= $limitsData['limits']['max_plants'] ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= ($limitsData['percentages']['plants'] >= 90) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $limitsData['percentages']['plants'] ?>%;"></div>
                                </div>
                                <div class="micro-text text-muted mt-2 d-flex justify-content-between">
                                    <span>Remaining: <strong><?= $limitsData['remaining']['plants'] ?></strong></span>
                                    <span><?= $limitsData['percentages']['plants'] ?>% used</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limit Adjustment Form -->
                    <div class="card p-4 border rounded-3">
                        <h6 class="fw-bold mb-3 text-dark">Configure Company Limits</h6>
                        <form method="POST" action="">
                            <?= renderCSRFField() ?>
                            <input type="hidden" name="action" value="update_limits">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Vacancy Headcount</label>
                                    <input type="number" class="form-control form-control-sm" name="max_vacancies" value="<?= $limitsData['limits']['max_vacancies'] ?>" min="1" max="10000" required>
                                    <div class="micro-text text-muted">Total demand headcount allowed</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Requisitions</label>
                                    <input type="number" class="form-control form-control-sm" name="max_requirements" value="<?= $limitsData['limits']['max_requirements'] ?>" min="1" max="500" required>
                                    <div class="micro-text text-muted">Active job requisitions count</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Company Users</label>
                                    <input type="number" class="form-control form-control-sm" name="max_users" value="<?= $limitsData['limits']['max_users'] ?>" min="1" max="100" required>
                                    <div class="micro-text text-muted">Admin & member accounts</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Manufacturing Plants</label>
                                    <input type="number" class="form-control form-control-sm" name="max_plants" value="<?= $limitsData['limits']['max_plants'] ?>" min="1" max="100" required>
                                    <div class="micro-text text-muted">Factory facilities allowed</div>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Limit Allocation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TAB 6: ACTIVITY HISTORY -->
                <div class="tab-pane fade" id="tab-activity" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Audit History & Timeline for <?= e($company['company_name']) ?></h6>
                            <p class="micro-text text-muted mb-0">Append-only audit trail capturing profile updates, status decisions & security events</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                    <th>Performed By</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditHistory)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No audit trail recorded for this company.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($auditHistory as $a): ?>
                                        <tr>
                                            <td class="small text-muted font-monospace"><?= date('d M Y, H:i:s', strtotime($a['created_at'])) ?></td>
                                            <td><span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;"><?= e($a['action']) ?></span></td>
                                            <td class="small fw-semibold"><?= e($a['actor_name'] ?: 'System Process') ?></td>
                                            <td class="small text-muted font-monospace"><?= e($a['ip_address']) ?></td>
                                            <td class="small text-muted" style="max-width: 250px;"><?= e($a['details_json'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?= renderCSRFField() ?>
                        <input type="hidden" name="action" value="reject">
                        <div class="modal-header">
                            <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject Company Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                You are rejecting the registration for <strong><?= e($company['company_name']) ?></strong>. 
                                The company record will be retained in the database for auditing and re-application purposes.
                            </p>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mandatory Rejection Reason (Min. 10 characters) *</label>
                                <textarea class="form-control form-control-sm" name="rejection_reason" rows="4" required minlength="10" placeholder="Provide clear explanation for rejection (e.g. Unverifiable factory location, invalid corporate registration)..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
