<?php
/**
 * Super Admin Company Management Console — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Company Management & Verification — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

requireRole('super_admin');

$controller = new CompanyController();

// Handle Status & Limit Mutation Actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $action    = $_POST['action'] ?? '';
    $companyId = (int)($_POST['company_id'] ?? 0);
    $reason    = $_POST['rejection_reason'] ?? null;

    if ($action === 'verify' && $companyId > 0) {
        $res = $controller->updateVerificationStatus($companyId, 'Verified');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'under_review' && $companyId > 0) {
        $res = $controller->updateVerificationStatus($companyId, 'Under Review');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'reject' && $companyId > 0) {
        $res = $controller->updateVerificationStatus($companyId, 'Rejected', $reason);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'suspend' && $companyId > 0) {
        $res = $controller->updateVerificationStatus($companyId, 'Suspended');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'reactivate' && $companyId > 0) {
        $res = $controller->updateVerificationStatus($companyId, 'Active');
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    } elseif ($action === 'set_limits' && $companyId > 0) {
        $res = $controller->setCompanyLimits($companyId, [
            'max_users'        => $_POST['max_users'] ?? 5,
            'max_requirements' => $_POST['max_requirements'] ?? 10,
            'max_plants'       => $_POST['max_plants'] ?? 5,
            'max_vacancies'    => $_POST['max_vacancies'] ?? 250
        ]);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Filters, Sorting & Pagination
$filters = [
    'search'        => trim((string)($_GET['search'] ?? '')),
    'status'        => trim((string)($_GET['status'] ?? '')),
    'industry_type' => trim((string)($_GET['industry_type'] ?? '')),
    'district'      => trim((string)($_GET['district'] ?? '')),
    'date_from'     => trim((string)($_GET['date_from'] ?? '')),
    'date_to'       => trim((string)($_GET['date_to'] ?? ''))
];

$sort = trim((string)($_GET['sort'] ?? 'id'));
$dir = trim((string)($_GET['dir'] ?? 'DESC'));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportResult = $controller->getCompanies($filters, 1, 10000, $sort, $dir);
    $exportData = $exportResult['data'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=prayas_companies_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Company ID', 'Company Code', 'Company Name', 'Registration Number (CIN)', 
        'Industry Type', 'State', 'District', 'City', 'Pincode', 
        'HR Contact Name', 'HR Email', 'HR Phone', 'Verification Status', 
        'Assigned Administrator', 'Total Requisitions', 'Total Plants', 'Registration Date'
    ]);

    foreach ($exportData as $row) {
        fputcsv($out, [
            $row['id'],
            $row['company_code'],
            $row['company_name'],
            $row['registration_number'] ?: 'N/A',
            $row['industry_type'],
            $row['state'],
            $row['district'],
            $row['city'],
            $row['pincode'],
            $row['hr_contact_name'],
            $row['hr_contact_email'],
            $row['hr_contact_phone'],
            $row['verification_status'],
            $row['assigned_admin_name'] ?: 'None Assigned',
            $row['total_requirements'],
            $row['total_plants'],
            $row['created_at']
        ]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$result = $controller->getCompanies($filters, $page, $perPage, $sort, $dir);
$companies = $result['data'];
$totalPages = $result['total_pages'];
$totalCount = $result['total'];

// Industry Sectors
$industryTypes = [
    'Automotive & Auto Components', 'Industrial Automation & Robotics', 
    'Machinery & Heavy Engineering', 'Electrical & Electronics Manufacturing', 
    'Precision Tooling & CNC Machining', 'Process & Chemical Plants', 
    'Solar & Renewable Energy', 'Other Manufacturing / Assembly'
];
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Title & Action Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title">Industrial Partner Governance Directory</h1>
                <p class="supporting-text mb-0">Centralized control over partner factories, legal verification, allocated limits & administrator assignments</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= app_url('/views/super_admin/company_limits.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-sliders me-1"></i> Company Limits
                </a>
                <a href="<?= app_url('/views/super_admin/companies.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
                <a href="<?= app_url('/views/super_admin/company_form.php') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-building-add me-1"></i> Add Company
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/super_admin/companies.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label micro-text text-muted mb-1">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control" name="search" value="<?= e($filters['search']) ?>" placeholder="Name, Code, CIN, HR email...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Verification Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['Pending', 'Under Review', 'Verified', 'Active', 'Rejected', 'Suspended'] as $st): ?>
                                <option value="<?= e($st) ?>" <?= ($filters['status'] === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label micro-text text-muted mb-1">Industry Sector</label>
                        <select class="form-select form-select-sm" name="industry_type">
                            <option value="">All Industry Sectors</option>
                            <?php foreach ($industryTypes as $type): ?>
                                <option value="<?= e($type) ?>" <?= ($filters['industry_type'] === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Sort By</label>
                        <select class="form-select form-select-sm" name="sort">
                            <option value="id" <?= ($sort === 'id') ? 'selected' : '' ?>>Newest First</option>
                            <option value="name" <?= ($sort === 'name') ? 'selected' : '' ?>>Company Name</option>
                            <option value="status" <?= ($sort === 'status') ? 'selected' : '' ?>>Verification Status</option>
                            <option value="date" <?= ($sort === 'date') ? 'selected' : '' ?>>Registration Date</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1 mt-auto">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Companies Data Table -->
        <div class="card-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-uppercase" style="color: var(--deep-navy);">
                    Showing <?= count($companies) ?> of <?= number_format($totalCount) ?> Registered Companies
                </span>
                <?php if (!empty($filters['status'])): ?>
                    <span class="badge bg-light text-dark border">Filtered by: <?= e($filters['status']) ?></span>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Company ID / Code</th>
                            <th>Company Name & Legal Info</th>
                            <th>Sector & Location</th>
                            <th>Primary HR Contact</th>
                            <th>Assigned Administrator</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-buildings"></i>
                                        </div>
                                        <div class="empty-state-title">No Company Records Found</div>
                                        <p class="empty-state-text">No corporate partner accounts match your active search or filter criteria.</p>
                                        <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-sm btn-outline-secondary mt-2">Clear All Filters</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $comp): ?>
                                <?php
                                $st = $comp['verification_status'];
                                $badgeClass = match ($st) {
                                    'Verified', 'Active' => 'badge-verified',
                                    'Pending'            => 'badge-pending',
                                    'Under Review'       => 'badge-under-review',
                                    'Rejected'           => 'badge-rejected',
                                    'Suspended'          => 'badge-suspended',
                                    default              => 'bg-light text-dark'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-primary small"><?= e($comp['company_code']) ?></span>
                                        <div class="micro-text text-muted">ID: #<?= $comp['id'] ?></div>
                                    </td>

                                    <td>
                                        <div class="fw-bold">
                                            <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $comp['id']) ?>" class="text-decoration-none" style="color: var(--deep-navy);">
                                                <?= e($comp['company_name']) ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($comp['registration_number'])): ?>
                                            <div class="micro-text text-muted font-monospace">CIN: <?= e($comp['registration_number']) ?></div>
                                        <?php endif; ?>
                                        <div class="micro-text text-muted">Registered: <?= date('d M Y', strtotime($comp['created_at'])) ?></div>
                                    </td>

                                    <td>
                                        <div class="small fw-semibold"><?= e($comp['industry_type']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($comp['district']) ?>, <?= e($comp['state']) ?></div>
                                        <div class="micro-text text-muted">Plants: <?= (int)$comp['total_plants'] ?> &bull; Demands: <?= (int)$comp['total_requirements'] ?></div>
                                    </td>

                                    <td>
                                        <div class="small fw-semibold"><?= e($comp['hr_contact_name']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-envelope me-1"></i><?= e($comp['hr_contact_email']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-telephone me-1"></i><?= e($comp['hr_contact_phone']) ?></div>
                                    </td>

                                    <td>
                                        <?php if (!empty($comp['assigned_admin_name'])): ?>
                                            <div class="small fw-semibold"><?= e($comp['assigned_admin_name']) ?></div>
                                            <div class="micro-text text-muted"><?= e($comp['assigned_admin_email']) ?></div>
                                            <span class="badge bg-light text-success border" style="font-size: 0.68rem;">
                                                <i class="bi bi-check-circle me-1"></i><?= e($comp['assigned_admin_status'] ?? 'Active') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">No Admin Linked</span>
                                            <div class="mt-1">
                                                <a href="<?= app_url('/views/super_admin/user_form.php?company_id=' . $comp['id'] . '&role=industry_admin') ?>" class="micro-text text-primary text-decoration-none">
                                                    + Assign Admin
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge-status <?= $badgeClass ?>"><?= e($st) ?></span>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $comp['id']) ?>" class="btn btn-outline-primary" title="View Full Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= app_url('/views/super_admin/company_form.php?id=' . $comp['id']) ?>" class="btn btn-outline-secondary" title="Edit Company Details">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><h6 class="dropdown-header">Workflow Decisions</h6></li>
                                                <?php if (in_array($st, ['Pending', 'Under Review', 'Rejected', 'Suspended'])): ?>
                                                    <li>
                                                        <form method="POST" action="" onsubmit="return confirm('Verify and approve <?= addslashes($comp['company_name']) ?>?');">
                                                            <?= renderCSRFField() ?>
                                                            <input type="hidden" name="action" value="verify">
                                                            <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-check-circle me-2"></i> Approve & Verify
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($st === 'Pending'): ?>
                                                    <li>
                                                        <form method="POST" action="">
                                                            <?= renderCSRFField() ?>
                                                            <input type="hidden" name="action" value="under_review">
                                                            <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-info">
                                                                <i class="bi bi-hourglass-split me-2"></i> Move to Under Review
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($st !== 'Rejected'): ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $comp['id'] ?>">
                                                            <i class="bi bi-x-circle me-2"></i> Reject Registration
                                                        </button>
                                                    </li>
                                                <?php endif; ?>

                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header">Account Controls</h6></li>

                                                <?php if (in_array($st, ['Verified', 'Active'])): ?>
                                                    <li>
                                                        <form method="POST" action="" onsubmit="return confirm('Suspend <?= addslashes($comp['company_name']) ?>? Active requirements will be paused.');">
                                                            <?= renderCSRFField() ?>
                                                            <input type="hidden" name="action" value="suspend">
                                                            <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-warning">
                                                                <i class="bi bi-slash-circle me-2"></i> Suspend Company
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($st === 'Suspended'): ?>
                                                    <li>
                                                        <form method="POST" action="" onsubmit="return confirm('Reactivate <?= addslashes($comp['company_name']) ?>?');">
                                                            <?= renderCSRFField() ?>
                                                            <input type="hidden" name="action" value="reactivate">
                                                            <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-arrow-repeat me-2"></i> Reactivate Account
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <li>
                                                    <a class="dropdown-item" href="<?= app_url('/views/super_admin/company_details.php?id=' . $comp['id'] . '#tab-limits') ?>">
                                                        <i class="bi bi-sliders me-2"></i> Configure Limits
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade text-start" id="rejectModal<?= $comp['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject Company Registration</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">
                                                                You are rejecting the registration for <strong><?= e($comp['company_name']) ?></strong>. 
                                                                This action will notify the company contact. The record will remain preserved in the database.
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Mandatory Rejection Reason (Min. 10 chars) *</label>
                                                                <textarea class="form-control form-control-sm" name="rejection_reason" rows="4" required minlength="10" placeholder="Specify clearly why this company profile is being rejected (e.g. invalid registration number, unverified facility address)..."></textarea>
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
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= app_url('/views/super_admin/companies.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">Previous</a>
                            </li>
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= app_url('/views/super_admin/companies.php?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= app_url('/views/super_admin/companies.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
