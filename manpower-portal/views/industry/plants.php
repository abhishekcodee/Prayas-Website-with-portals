<?php
/**
 * Manufacturing Plants Management Directory — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Manufacturing Plant Management
 */

declare(strict_types=1);

$pageTitle = 'Manufacturing Plants — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$industryCtrl = new IndustryController();

// Handle Status Toggle via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCSRF();

    $action = $_POST['action'];

    if ($action === 'toggle_status') {
        $plantId = (int)($_POST['plant_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? 'Active');
        $res = $industryCtrl->setPlantStatus($companyId, $plantId, $newStatus);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'assign_admin') {
        $plantId = (int)($_POST['plant_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $res = $industryCtrl->assignPlantAdmin($companyId, $plantId, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    }

    header("Location: " . app_url('/views/industry/plants.php'));
    exit;
}

// Search and Filter parameters
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$stateFilter = trim((string)($_GET['state'] ?? ''));
$districtFilter = trim((string)($_GET['district'] ?? ''));

$plants = $industryCtrl->getPlants($companyId, [
    'search'   => $search,
    'status'   => $statusFilter,
    'state'    => $stateFilter,
    'district' => $districtFilter
]);

$teamMembers = $industryCtrl->getTeamMembers($companyId);

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
                <li class="breadcrumb-item"><a href="<?= app_url('/views/industry/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manufacturing Plants</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="dashboard-title mb-1"><i class="bi bi-geo-alt me-2 text-primary"></i>Manufacturing Plants & Facilities</h1>
                <p class="supporting-text mb-0">Manage factory sites, physical coordinates, and plant coordinators for manpower placement.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/industry/plant_form.php') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Manufacturing Plant
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/industry/plants.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, code, city, coordinator..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="district" class="form-control form-control-sm" placeholder="Filter by District" value="<?= e($districtFilter) ?>">
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button type="submit" class="btn btn-sm btn-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Plants Table Card -->
        <div class="card-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-building me-2 text-primary"></i>Plant Units Roster (<?= count($plants) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Plant ID</th>
                            <th>Code</th>
                            <th>Plant Name & Type</th>
                            <th>Location / District</th>
                            <th>Coordinator / Contact</th>
                            <th>Assigned Admin</th>
                            <th>Requisitions</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plants)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="empty-state py-2">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-building-slash"></i>
                                        </div>
                                        <div class="empty-state-title">No manufacturing plants found</div>
                                        <div class="empty-state-description">No plant facilities matched your search parameters.</div>
                                        <a href="<?= app_url('/views/industry/plant_form.php') ?>" class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-plus-circle me-1"></i>Add First Manufacturing Plant
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plants as $p): ?>
                                <tr>
                                    <td class="font-monospace text-muted small">#<?= (int)$p['id'] ?></td>
                                    <td class="fw-bold font-monospace text-primary"><?= e($p['plant_code']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($p['plant_name']) ?></div>
                                        <div class="micro-text text-muted"><?= e($p['plant_type'] ?? 'Manufacturing Unit') ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= e($p['city']) ?>, <?= e($p['district']) ?></div>
                                        <div class="micro-text text-muted"><?= e($p['state']) ?> - <?= e($p['pincode']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= e($p['contact_person']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-telephone me-1"></i><?= e($p['contact_number']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['assigned_admin_name'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-person-check me-1 text-success"></i><?= e($p['assigned_admin_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#assignModal<?= $p['id'] ?>">
                                                <i class="bi bi-person-plus me-1"></i>Assign
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= (int)$p['requirement_count'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'Active'): ?>
                                            <span class="badge-status badge-verified">Active</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-rejected">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('/views/industry/plant_details.php?id=' . $p['id']) ?>" class="btn btn-outline-primary" title="View Plant Dossier">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= app_url('/views/industry/plant_form.php?id=' . $p['id']) ?>" class="btn btn-outline-secondary" title="Edit Plant">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-info" title="Assign Coordinator" data-bs-toggle="modal" data-bs-target="#assignModal<?= $p['id'] ?>">
                                                <i class="bi bi-person-gear"></i>
                                            </button>
                                            <form method="POST" action="<?= app_url('/views/industry/plants.php') ?>" class="d-inline" onsubmit="return confirm('Change status for this plant? Historical requirement records will remain safely intact.');">
                                                <?= renderCSRFField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="plant_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $p['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                                                <button type="submit" class="btn <?= $p['status'] === 'Active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $p['status'] === 'Active' ? 'Deactivate Plant' : 'Activate Plant' ?>">
                                                    <?= $p['status'] === 'Active' ? '<i class="bi bi-pause"></i>' : '<i class="bi bi-play"></i>' ?>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Assign Coordinator Modal -->
                                        <div class="modal fade text-start" id="assignModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="<?= app_url('/views/industry/plants.php') ?>">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="action" value="assign_admin">
                                                        <input type="hidden" name="plant_id" value="<?= $p['id'] ?>">

                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-6 fw-bold">
                                                                <i class="bi bi-person-gear me-2 text-primary"></i>Assign Plant Coordinator
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold text-muted">Plant Facility</label>
                                                                <div class="fw-bold text-dark"><?= e($p['plant_name']) ?> (<?= e($p['plant_code']) ?>)</div>
                                                                <div class="micro-text text-muted"><?= e($p['city']) ?>, <?= e($p['state']) ?></div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Select Company User / Coordinator <span class="text-danger">*</span></label>
                                                                <select name="user_id" class="form-select form-select-sm" required>
                                                                    <option value="">-- Choose User --</option>
                                                                    <?php foreach ($teamMembers as $tm): ?>
                                                                        <option value="<?= $tm['id'] ?>" <?= ((int)($p['assigned_admin_user_id'] ?? 0) === (int)$tm['id']) ? 'selected' : '' ?>>
                                                                            <?= e($tm['full_name']) ?> (<?= e($tm['role_label']) ?> &bull; <?= e($tm['email']) ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="micro-text text-muted mt-1">This user will be notified and assigned as the primary contact coordinator for this manufacturing unit.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary">Save Assignment</button>
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
