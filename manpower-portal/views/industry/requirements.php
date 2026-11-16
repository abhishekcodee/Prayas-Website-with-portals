<?php
/**
 * Industry Requirement Management Dashboard
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Requirements Management — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/RequirementController.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$pdo = Database::getConnection();
$reqCtrl = new RequirementController($pdo);
$industryCtrl = new IndustryController($pdo);

// Handle Cancellation Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_requirement') {
    requireCSRF();
    $reqId = (int)($_POST['requirement_id'] ?? 0);
    $reason = trim((string)($_POST['cancellation_reason'] ?? ''));

    $res = $reqCtrl->cancelRequirement($companyId, $reqId, $reason);
    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
    } else {
        $_SESSION['flash_error'] = $res['message'];
    }
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

// Handle Duplicate Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'duplicate_requirement') {
    requireCSRF();
    $reqId = (int)($_POST['requirement_id'] ?? 0);
    $res = $reqCtrl->duplicateRequirement($companyId, $reqId);
    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        header("Location: " . app_url('/views/industry/requirement_form.php?id=' . $res['requirement_id']));
        exit;
    } else {
        $_SESSION['flash_error'] = $res['message'];
    }
    header("Location: " . app_url('/views/industry/requirements.php'));
    exit;
}

// Summary Metrics for Dashboard
$stats = $reqCtrl->getRequirementDashboardStats($companyId);

// Search and Filter parameters
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = trim((string)($_GET['search'] ?? ''));
$status   = trim((string)($_GET['status'] ?? ''));
$trade    = trim((string)($_GET['trade'] ?? ''));
$plantId  = !empty($_GET['plant_id']) ? (int)$_GET['plant_id'] : 0;

$filters = [
    'search'   => $search,
    'status'   => $status,
    'trade'    => $trade,
    'plant_id' => $plantId
];

$reqsData = $reqCtrl->getRequirements($companyId, $filters, $page, 10);
$plants = $industryCtrl->getPlants($companyId, ['status' => 'Active']);

// Fetch Distinct ITI Trades configured by this company
$tradeStmt = $pdo->prepare("SELECT DISTINCT rt.trade_name 
    FROM `requirement_trades` rt 
    JOIN `manpower_requirements` mr ON rt.requirement_id = mr.id 
    WHERE mr.company_id = :cid ORDER BY rt.trade_name ASC");
$tradeStmt->execute([':cid' => $companyId]);
$availableTrades = $tradeStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="dashboard-title"><i class="bi bi-briefcase me-2 text-primary"></i>Manpower Requisition Dashboard</h1>
                <p class="supporting-text mb-0">Create, track, and manage factory technician demands across your manufacturing facilities.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/industry/requirement_form.php') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Create New Requirement
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Total Requisitions</div>
                        <i class="bi bi-briefcase text-primary fs-5"></i>
                    </div>
                    <div class="metric-value"><?= number_format($stats['total_requirements']) ?></div>
                    <div class="micro-text text-muted">All-time recorded</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Drafts</div>
                        <i class="bi bi-pencil-square text-warning fs-5"></i>
                    </div>
                    <div class="metric-value text-warning"><?= number_format($stats['total_drafts']) ?></div>
                    <div class="micro-text text-muted">Incomplete wizards</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Under Validation</div>
                        <i class="bi bi-shield-check text-info fs-5"></i>
                    </div>
                    <div class="metric-value text-info"><?= number_format($stats['pending_validation']) ?></div>
                    <div class="micro-text text-muted">Awaiting allocation</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Approved & Active</div>
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="metric-value text-success"><?= number_format($stats['approved'] + $stats['in_recruitment']) ?></div>
                    <div class="micro-text text-muted">Live candidate sourcing</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Fulfilled</div>
                        <i class="bi bi-trophy text-primary fs-5"></i>
                    </div>
                    <div class="metric-value"><?= number_format($stats['fulfilled'] + $stats['partially_fulfilled']) ?></div>
                    <div class="micro-text text-muted">Hiring completed</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="metric-label">Total Vacancies</div>
                        <i class="bi bi-people text-danger fs-5"></i>
                    </div>
                    <div class="metric-value text-danger"><?= number_format($stats['total_vacancies']) ?></div>
                    <div class="micro-text text-muted">Technicians demanded</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-panel mb-4">
            <form method="GET" action="<?= app_url('/views/industry/requirements.php') ?>" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by title, REQ code, plant..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="DRAFT" <?= $status === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                        <option value="SUBMITTED" <?= $status === 'SUBMITTED' ? 'selected' : '' ?>>Submitted</option>
                        <option value="UNDER_VALIDATION" <?= $status === 'UNDER_VALIDATION' ? 'selected' : '' ?>>Under Validation</option>
                        <option value="NEEDS_CORRECTION" <?= $status === 'NEEDS_CORRECTION' ? 'selected' : '' ?>>Needs Correction</option>
                        <option value="PENDING_REVIEW" <?= $status === 'PENDING_REVIEW' ? 'selected' : '' ?>>Pending Review</option>
                        <option value="APPROVED" <?= $status === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                        <option value="RECRUITMENT_IN_PROGRESS" <?= $status === 'RECRUITMENT_IN_PROGRESS' ? 'selected' : '' ?>>Recruiting</option>
                        <option value="PARTIALLY_FULFILLED" <?= $status === 'PARTIALLY_FULFILLED' ? 'selected' : '' ?>>Partially Fulfilled</option>
                        <option value="FULFILLED" <?= $status === 'FULFILLED' ? 'selected' : '' ?>>Fulfilled</option>
                        <option value="CANCELLED" <?= $status === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="plant_id" class="form-select form-select-sm">
                        <option value="">All Manufacturing Plants</option>
                        <?php foreach ($plants as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $plantId === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['plant_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="trade" class="form-select form-select-sm">
                        <option value="">All ITI Trades</option>
                        <?php foreach ($availableTrades as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $trade === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-sm btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Requirements Listing -->
        <div class="card-table mb-4">
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Requisition Code</th>
                            <th>Job Title & Location</th>
                            <th>Trades & Distribution</th>
                            <th>Plant Facility</th>
                            <th class="text-center">Vacancies</th>
                            <th class="text-center">Placed</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reqsData['data'])): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="empty-state py-2">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-state-title">No requirements found</div>
                                        <div class="empty-state-description">Try adjusting your search criteria or create a new requisition.</div>
                                        <a href="<?= app_url('/views/industry/requirement_form.php') ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Create First Requirement
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reqsData['data'] as $r): ?>
                                <?php
                                $sBadge = match ($r['status']) {
                                    'APPROVED'                => 'badge-approved',
                                    'RECRUITMENT_IN_PROGRESS' => 'badge-in-progress',
                                    'FULFILLED'               => 'badge-fulfilled',
                                    'PARTIALLY_FULFILLED'     => 'badge-partially-fulfilled',
                                    'NEEDS_CORRECTION'        => 'badge-rejected',
                                    'UNDER_VALIDATION'        => 'badge-under-review',
                                    'PENDING_REVIEW'          => 'badge-pending',
                                    'SUBMITTED'               => 'badge-under-review',
                                    'CANCELLED'               => 'badge-closed',
                                    default                   => 'badge-pending'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= app_url('/views/industry/requirement_details.php?id=' . $r['id']) ?>" class="font-monospace fw-bold text-decoration-none" style="color: var(--primary);">
                                            <?= htmlspecialchars($r['requirement_id']) ?>
                                        </a>
                                        <?php if ($r['status'] === 'DRAFT'): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Step <?= $r['wizard_step'] ?? 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></div>
                                        <div class="micro-text text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['job_location_city']) ?>, <?= htmlspecialchars($r['job_location_district']) ?></div>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold"><?= htmlspecialchars($r['trade_names'] ?? 'Trades unassigned') ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?= htmlspecialchars($r['plant_name'] ?? 'Headquarters') ?></span>
                                        <?php if (!empty($r['plant_code'])): ?>
                                            <small class="text-muted font-monospace d-block micro-text"><?= htmlspecialchars($r['plant_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold fs-6"><?= $r['quantity_required'] ?></td>
                                    <td class="text-center">
                                        <span class="fw-bold text-success"><?= $r['joined_count'] ?></span>
                                    </td>
                                    <td class="small">
                                        <div><span class="text-muted">Deadline:</span> <?= htmlspecialchars($r['application_deadline']) ?></div>
                                        <div><span class="text-muted">Joining:</span> <?= htmlspecialchars($r['target_joining_date']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $sBadge ?>"><?= str_replace('_', ' ', $r['status']) ?></span>
                                        <?php if ($r['status'] === 'NEEDS_CORRECTION'): ?>
                                            <div class="micro-text text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Correction Required</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= app_url('/views/industry/requirement_details.php?id=' . $r['id']) ?>" class="btn btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i> Details
                                            </a>
                                            <?php if (in_array($r['status'], ['DRAFT', 'NEEDS_CORRECTION'], true)): ?>
                                                <a href="<?= app_url('/views/industry/requirement_form.php?id=' . $r['id']) ?>" class="btn btn-outline-warning" title="Edit / Resume Wizard">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" action="<?= app_url('/views/industry/requirements.php') ?>" class="d-inline" onsubmit="return confirm('Duplicate requirement \'<?= htmlspecialchars($r['requirement_id']) ?>\' as a new draft?');">
                                                <?= renderCSRFField() ?>
                                                <input type="hidden" name="action" value="duplicate_requirement">
                                                <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-outline-secondary" title="Duplicate as New Draft">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                            </form>
                                            <?php if (!in_array($r['status'], ['CANCELLED', 'CLOSED', 'FULFILLED'], true)): ?>
                                                <button type="button" class="btn btn-outline-danger" title="Cancel Requirement" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $r['id'] ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Cancel Modal -->
                                        <div class="modal fade" id="cancelModal<?= $r['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <form method="POST" action="<?= app_url('/views/industry/requirements.php') ?>">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="action" value="cancel_requirement">
                                                        <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-6 fw-bold">Cancel Requirement: <?= htmlspecialchars($r['requirement_id']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="supporting-text text-muted">Are you sure you want to cancel this requisition? Sourcing will cease immediately.</p>
                                                            <label class="form-label required">Cancellation Rationale</label>
                                                            <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Provide cancellation reason (e.g. Project deferred, internal hiring completed...)" required></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Keep Active</button>
                                                            <button type="submit" class="btn btn-danger btn-sm">Confirm Cancellation</button>
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

            <!-- Pagination -->
            <?php if ($reqsData['total_pages'] > 1): ?>
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <small class="supporting-text">Showing page <?= $reqsData['page'] ?> of <?= $reqsData['total_pages'] ?> (Total <?= $reqsData['total'] ?> requirements)</small>
                    <nav>
                        <ul class="pagination pagination-prayas pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $reqsData['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $reqsData['page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&trade=<?= urlencode($trade) ?>&plant_id=<?= $plantId ?>"><?= $i ?></a>
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

