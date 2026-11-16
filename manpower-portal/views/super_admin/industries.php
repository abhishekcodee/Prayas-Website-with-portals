<?php
/**
 * Industry Management Console — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Industry Administration & Governance
 */

declare(strict_types=1);

$pageTitle = 'Industry Management — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole('super_admin');

$industryCtrl = new IndustryController();

// Handle Status & Admin Assignment Mutations via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $action = $_POST['action'] ?? '';
    $industryId = (int)($_POST['industry_id'] ?? 0);

    if ($action === 'toggle_status' && $industryId > 0) {
        $newStatus = (string)($_POST['new_status'] ?? 'Active');
        $res = $industryCtrl->setIndustryStatus($industryId, $newStatus);
        if ($res['success']) $_SESSION['flash_success'] = $res['message'];
        else $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: " . app_url('/views/super_admin/industries.php'));
    exit;
}

// Filters & Pagination
$search       = trim((string)($_GET['search'] ?? ''));
$typeFilter   = trim((string)($_GET['industry_type'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;

$industriesResult = $industryCtrl->getIndustries([
    'search'        => $search,
    'industry_type' => $typeFilter,
    'status'        => $statusFilter
], $page, $perPage);

$industries = $industriesResult['data'];
$total = $industriesResult['total'];
$totalPages = $industriesResult['total_pages'];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Industry Management</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="dashboard-title mb-1"><i class="bi bi-buildings me-2 text-primary"></i>Industry Administration Directory</h1>
                <p class="supporting-text mb-0">Centralized governance of industry partners, manufacturing sectors, assigned admins, and facility units.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/super_admin/industry_form.php') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Create / Register Industry
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/super_admin/industries.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, code, contact person, email, city..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="industry_type" class="form-select form-select-sm">
                            <option value="">All Industry Types / Sectors</option>
                            <option value="Automotive Manufacturing" <?= $typeFilter === 'Automotive Manufacturing' ? 'selected' : '' ?>>Automotive Manufacturing</option>
                            <option value="Heavy Engineering" <?= $typeFilter === 'Heavy Engineering' ? 'selected' : '' ?>>Heavy Engineering</option>
                            <option value="CNC & Precision Tooling" <?= $typeFilter === 'CNC & Precision Tooling' ? 'selected' : '' ?>>CNC & Precision Tooling</option>
                            <option value="Electronics & Assembly" <?= $typeFilter === 'Electronics & Assembly' ? 'selected' : '' ?>>Electronics & Assembly</option>
                            <option value="Robotics & Industrial Automation" <?= $typeFilter === 'Robotics & Industrial Automation' ? 'selected' : '' ?>>Robotics & Automation</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Verified" <?= $statusFilter === 'Verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Suspended" <?= $statusFilter === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button type="submit" class="btn btn-sm btn-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="<?= app_url('/views/super_admin/industries.php') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Industries Table -->
        <div class="card-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Registered Industries Roster (<?= $total ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Industry Name & Sector</th>
                            <th>Primary Contact</th>
                            <th>Official Email & Phone</th>
                            <th>Manufacturing Plants</th>
                            <th>Assigned Admin</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($industries)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="empty-state py-2">
                                        <i class="bi bi-buildings text-muted fs-2 d-block mb-2"></i>
                                        <div class="fw-bold">No industry partners found</div>
                                        <div class="small text-muted mb-2">No industry profiles match your current search criteria.</div>
                                        <a href="<?= app_url('/views/super_admin/industry_form.php') ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Register New Industry
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($industries as $ind): ?>
                                <tr>
                                    <td class="font-monospace text-muted small">#<?= (int)$ind['id'] ?></td>
                                    <td class="fw-bold font-monospace text-primary"><?= e($ind['industry_code']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($ind['company_name']) ?></div>
                                        <div class="micro-text text-muted">
                                            <span class="badge bg-light text-dark border me-1"><?= e($ind['industry_type']) ?></span>
                                            <span><?= e($ind['city'] ?? '') ?>, <?= e($ind['state'] ?? '') ?></span>
                                        </div>
                                    </td>
                                    <td class="small">
                                        <div class="fw-semibold text-dark"><?= e($ind['hr_contact_name']) ?></div>
                                    </td>
                                    <td class="small">
                                        <div><i class="bi bi-envelope me-1 text-muted"></i><?= e($ind['hr_contact_email']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-telephone me-1"></i><?= e($ind['hr_contact_phone']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $ind['id']) ?>#plants" class="badge bg-light text-dark border text-decoration-none">
                                            <i class="bi bi-geo-alt me-1 text-primary"></i><?= (int)$ind['total_plants'] ?> Plants
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($ind['assigned_admin_name'])): ?>
                                            <div class="small fw-semibold text-dark">
                                                <i class="bi bi-person-check text-success me-1"></i><?= e($ind['assigned_admin_name']) ?>
                                            </div>
                                            <div class="micro-text text-muted"><?= e($ind['assigned_admin_email'] ?? '') ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark small">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ind['verification_status'] === 'Active' || $ind['verification_status'] === 'Verified'): ?>
                                            <span class="badge-status badge-verified"><?= e($ind['verification_status']) ?></span>
                                        <?php elseif ($ind['verification_status'] === 'Suspended'): ?>
                                            <span class="badge-status badge-suspended">Suspended</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending"><?= e($ind['verification_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($ind['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $ind['id']) ?>" class="btn btn-outline-primary" title="View Full Industry Dossier">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= app_url('/views/super_admin/industry_form.php?id=' . $ind['id']) ?>" class="btn btn-outline-secondary" title="Edit Industry Details">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="<?= app_url('/views/super_admin/industries.php') ?>" class="d-inline" onsubmit="return confirm('Change operational status for this industry? Historical records remain intact.');">
                                                <?= renderCSRFField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="industry_id" value="<?= $ind['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= ($ind['verification_status'] === 'Active' || $ind['verification_status'] === 'Verified') ? 'Suspended' : 'Active' ?>">
                                                <button type="submit" class="btn <?= ($ind['verification_status'] === 'Active' || $ind['verification_status'] === 'Verified') ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= ($ind['verification_status'] === 'Active' || $ind['verification_status'] === 'Verified') ? 'Soft Deactivate Industry' : 'Activate Industry' ?>">
                                                    <?= ($ind['verification_status'] === 'Active' || $ind['verification_status'] === 'Verified') ? '<i class="bi bi-pause"></i>' : '<i class="bi bi-play"></i>' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (Total <?= $total ?> records)</div>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= app_url('/views/super_admin/industries.php?page=' . $i . '&search=' . urlencode($search) . '&industry_type=' . urlencode($typeFilter) . '&status=' . urlencode($statusFilter)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

