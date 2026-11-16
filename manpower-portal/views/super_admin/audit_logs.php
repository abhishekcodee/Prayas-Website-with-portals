<?php
/**
 * Super Admin Audit Log Inspector — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

requireRole('super_admin');

$pdo = Database::getConnection();

// Filters & Search
$search = trim((string)($_GET['search'] ?? ''));
$actionFilter = trim((string)($_GET['action'] ?? ''));
$userFilter = (int)($_GET['user_id'] ?? 0);
$companyFilter = (int)($_GET['company_id'] ?? 0);
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(a.action LIKE :search1 OR a.ip_address LIKE :search2 OR a.details_json LIKE :search3 OR u.full_name LIKE :search4 OR c.company_name LIKE :search5)";
    $sVal = "%{$search}%";
    $params[':search1'] = $sVal;
    $params[':search2'] = $sVal;
    $params[':search3'] = $sVal;
    $params[':search4'] = $sVal;
    $params[':search5'] = $sVal;
}

if (!empty($actionFilter)) {
    $conditions[] = "a.action = :action";
    $params[':action'] = $actionFilter;
}

if ($userFilter > 0) {
    $conditions[] = "a.user_id = :uid";
    $params[':uid'] = $userFilter;
}

if ($companyFilter > 0) {
    $conditions[] = "a.company_id = :cid";
    $params[':cid'] = $companyFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(a.created_at) >= :dfrom";
    $params[':dfrom'] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(a.created_at) <= :dto";
    $params[':dto'] = $dateTo;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=prayas_audit_logs_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Audit ID', 'Action', 'User ID', 'User Name', 'Company ID', 'Company Name', 'IP Address', 'User Agent', 'Details JSON', 'Timestamp']);

    $exportSql = "SELECT a.id, a.action, a.user_id, u.full_name, a.company_id, c.company_name, 
            a.ip_address, a.user_agent, a.details_json, a.created_at
        FROM `audit_logs` a
        LEFT JOIN `users` u ON a.user_id = u.id
        LEFT JOIN `companies` c ON a.company_id = c.id
        $whereClause
        ORDER BY a.id DESC";

    $exportStmt = $pdo->prepare($exportSql);
    $exportStmt->execute($params);

    while ($row = $exportStmt->fetch()) {
        fputcsv($out, [
            $row['id'], $row['action'], $row['user_id'], $row['full_name'] ?: 'System',
            $row['company_id'], $row['company_name'] ?: 'N/A', $row['ip_address'],
            $row['user_agent'], $row['details_json'], $row['created_at']
        ]);
    }

    fclose($out);
    exit;
}

$pageTitle = 'Audit Log Inspector — PRAYAS Super Admin';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM `audit_logs` a LEFT JOIN `users` u ON a.user_id = u.id LEFT JOIN `companies` c ON a.company_id = c.id $whereClause");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);
$offset = ($page - 1) * $perPage;

$sql = "SELECT a.id, a.action, a.user_id, a.company_id, a.ip_address, a.user_agent, a.details_json, a.created_at,
        u.full_name, u.email,
        c.company_name
    FROM `audit_logs` a
    LEFT JOIN `users` u ON a.user_id = u.id
    LEFT JOIN `companies` c ON a.company_id = c.id
    $whereClause
    ORDER BY a.id DESC
    LIMIT :offset, :limit";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Distinct actions for dropdown filter
$distinctActions = $pdo->query("SELECT DISTINCT action FROM `audit_logs` ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

// Users list for dropdown
$usersList = $pdo->query("SELECT id, full_name, email FROM `users` ORDER BY full_name ASC")->fetchAll();

// Companies list for dropdown
$companiesList = $pdo->query("SELECT id, company_name FROM `companies` ORDER BY company_name ASC")->fetchAll();
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Title & Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title">System Audit Log Inspector</h1>
                <p class="supporting-text mb-0">Append-only administrative ledger tracking authentications, state transitions & governance events</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= app_url('/views/super_admin/audit_logs.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Export Filtered CSV
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/super_admin/audit_logs.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label micro-text text-muted mb-1">Keywords</label>
                        <input type="text" class="form-control form-control-sm" name="search" value="<?= e($search) ?>" placeholder="Action, IP, details...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Action Event</label>
                        <select class="form-select form-select-sm" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($distinctActions as $act): ?>
                                <option value="<?= e($act) ?>" <?= ($actionFilter === $act) ? 'selected' : '' ?>><?= e($act) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Initiator / User</label>
                        <select class="form-select form-select-sm" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($usersList as $usr): ?>
                                <option value="<?= $usr['id'] ?>" <?= ($userFilter === (int)$usr['id']) ? 'selected' : '' ?>>
                                    <?= e($usr['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label micro-text text-muted mb-1">Target Company</label>
                        <select class="form-select form-select-sm" name="company_id">
                            <option value="">All Companies</option>
                            <?php foreach ($companiesList as $cmp): ?>
                                <option value="<?= $cmp['id'] ?>" <?= ($companyFilter === (int)$cmp['id']) ? 'selected' : '' ?>>
                                    <?= e($cmp['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-1 mt-auto">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                        <a href="<?= app_url('/views/super_admin/audit_logs.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-uppercase" style="color: var(--deep-navy);">
                    Found <?= number_format($totalLogs) ?> Recorded Audit Entries
                </span>
                <span class="badge bg-light text-secondary border small">Append-Only Immutable Records</span>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Action Event</th>
                            <th>Initiator</th>
                            <th>Company Scope</th>
                            <th>IP Address</th>
                            <th>Details Snapshot</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No audit events match your selected criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="font-monospace text-muted">#<?= $log['id'] ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($log['action']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <div class="fw-semibold text-dark"><?= e($log['full_name'] ?: 'User #' . $log['user_id']) ?></div>
                                            <div class="micro-text text-muted"><?= e($log['email']) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">System / Anonymous</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['company_name'])): ?>
                                            <div class="small fw-semibold"><?= e($log['company_name']) ?></div>
                                            <div class="micro-text text-muted">ID: #<?= $log['company_id'] ?></div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Platform System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace text-secondary small"><?= e($log['ip_address']) ?></td>
                                    <td>
                                        <?php if (!empty($log['details_json'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 micro-text" data-bs-toggle="modal" data-bs-target="#auditModal<?= $log['id'] ?>">
                                                <i class="bi bi-code-slash me-1"></i> View Payload
                                            </button>

                                            <!-- Payload Modal -->
                                            <div class="modal fade text-start" id="auditModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title fw-bold fs-6">Audit Event #<?= $log['id'] ?>: <?= e($log['action']) ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-2 text-muted micro-text"><strong>User Agent:</strong> <?= e($log['user_agent'] ?? '') ?></div>
                                                            <label class="form-label micro-text text-muted mb-1 fw-bold">Event Details JSON:</label>
                                                            <pre class="bg-light p-3 border rounded-2 small text-dark" style="max-height: 250px; overflow-y: auto;"><code><?= json_encode(json_decode($log['details_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted font-monospace micro-text text-nowrap"><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center py-2">
                    <div class="micro-text text-muted">Page <?= $page ?> of <?= $totalPages ?></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= app_url('/views/super_admin/audit_logs.php?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
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
