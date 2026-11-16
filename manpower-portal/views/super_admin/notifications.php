<?php
/**
 * Super Admin Notifications & System Alerts Console
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'System Notifications & Alerts — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/notification_service.php';

requireRole('super_admin');

$pdo = Database::getConnection();
$notifService = new NotificationService($pdo);
$userId = getUserId();

// Handle Mark as Read / Delete Actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        if ($notifId > 0) {
            $notifService->markAsRead($notifId, $userId);
        } elseif ($notifId === -1) {
            $notifService->markAllAsRead($userId);
        }
    } elseif ($action === 'delete') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        if ($notifId > 0) {
            $notifService->deleteNotification($notifId, $userId);
        }
    }
    
    header("Location: " . app_url('/views/super_admin/notifications.php?' . http_build_query($_GET)));
    exit;
}

// Filters
$filterStatus = $_GET['status'] ?? 'all';
$filterType   = $_GET['type'] ?? '';
$search       = trim((string)($_GET['search'] ?? ''));

$filters = [];
if ($filterStatus === 'unread' || $filterStatus === 'read') {
    $filters['status'] = $filterStatus;
}
if (!empty($filterType)) {
    $filters['type'] = $filterType;
}
if (!empty($search)) {
    $filters['search'] = $search;
}

$notifications = $notifService->getUserNotifications($userId, $filters, 100);
$unreadCount   = $notifService->getUnreadCount($userId);
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-bell-fill me-2 text-primary"></i>Administrative Alerts & Sourcing Dispatch</h4>
                <p class="text-muted small mb-0">Centralized stream of enterprise employer submissions, algorithmic validation decisions, and review queues.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" action="<?= app_url('/views/super_admin/notifications.php') ?>" class="d-inline">
                        <?= renderCSRFField() ?>
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="notification_id" value="-1">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check2-all me-1"></i>Mark All as Read (<?= $unreadCount ?>)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/super_admin/notifications.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search notification title, message, or requisition code..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="unread" <?= $filterStatus === 'unread' ? 'selected' : '' ?>>Unread Only (<?= $unreadCount ?>)</option>
                            <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>Read Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Alert Types</option>
                            <option value="REQUIREMENT_NEEDS_MANUAL_REVIEW" <?= $filterType === 'REQUIREMENT_NEEDS_MANUAL_REVIEW' ? 'selected' : '' ?>>Manual Review Required</option>
                            <option value="NEW_APPROVED_REQUIREMENT" <?= $filterType === 'NEW_APPROVED_REQUIREMENT' ? 'selected' : '' ?>>New Approved Requirement</option>
                            <option value="NEW_COMPANY_REGISTRATION" <?= $filterType === 'NEW_COMPANY_REGISTRATION' ? 'selected' : '' ?>>New Company Registration</option>
                            <option value="COMPANY_REVERIFICATION_REQUIRED" <?= $filterType === 'COMPANY_REVERIFICATION_REQUIRED' ? 'selected' : '' ?>>Re-verification Required</option>
                            <option value="VALIDATION_FAILED" <?= $filterType === 'VALIDATION_FAILED' ? 'selected' : '' ?>>Validation Failed</option>
                            <option value="DEADLINE_APPROACHING" <?= $filterType === 'DEADLINE_APPROACHING' ? 'selected' : '' ?>>Deadline Approaching</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i></button>
                        <a href="<?= app_url('/views/super_admin/notifications.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card border-0 shadow-sm w-100">
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold">No Administrative Alerts</h6>
                        <p class="small text-muted mb-0">The administrative queue is clean. Automated alerts and employer escalations will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $n): ?>
                            <?php 
                                $isUnread = !(bool)$n['is_read'];
                                $icon = match($n['notification_type']) {
                                    'NEW_APPROVED_REQUIREMENT' => ['bi-check-circle-fill', 'text-success', 'bg-success-subtle'],
                                    'REQUIREMENT_NEEDS_MANUAL_REVIEW' => ['bi-hourglass-split', 'text-warning', 'bg-warning-subtle'],
                                    'COMPANY_REVERIFICATION_REQUIRED', 'VALIDATION_FAILED' => ['bi-exclamation-triangle-fill', 'text-danger', 'bg-danger-subtle'],
                                    'NEW_COMPANY_REGISTRATION' => ['bi-building-fill', 'text-info', 'bg-info-subtle'],
                                    default => ['bi-bell-fill', 'text-primary', 'bg-primary-subtle']
                                };
                                $actionUrl = !empty($n['action_url']) ? app_url($n['action_url']) : null;
                            ?>
                            <div class="list-group-item p-3 <?= $isUnread ? 'bg-primary-subtle bg-opacity-25' : '' ?> transition-all">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="d-flex gap-3 align-items-start flex-grow-1">
                                        <div class="p-2 rounded-circle <?= $icon[2] ?> <?= $icon[1] ?> mt-1 flex-shrink-0" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi <?= $icon[0] ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <h6 class="fw-bold mb-0 <?= $isUnread ? 'text-dark' : 'text-secondary' ?>">
                                                    <?= htmlspecialchars($n['title']) ?>
                                                </h6>
                                                <?php if ($isUnread): ?>
                                                    <span class="badge bg-primary" style="font-size: 0.65rem;">NEW</span>
                                                <?php endif; ?>
                                                <?php if (!empty($n['notification_type'])): ?>
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;"><?= htmlspecialchars($n['notification_type']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1 text-secondary small"><?= htmlspecialchars($n['message']) ?></p>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($n['created_at']) ?>
                                                <?php if (!empty($n['read_at'])): ?>
                                                    <span class="ms-2 text-muted">&bull; Read at <?= htmlspecialchars($n['read_at']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 align-items-center flex-shrink-0">
                                        <?php if ($actionUrl): ?>
                                            <a href="<?= htmlspecialchars($actionUrl) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.8rem;">
                                                View <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isUnread): ?>
                                            <form method="POST" action="<?= app_url('/views/super_admin/notifications.php') ?>" class="d-inline">
                                                <?= renderCSRFField() ?>
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-secondary" title="Mark as Read" style="font-size: 0.8rem;">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="<?= app_url('/views/super_admin/notifications.php') ?>" class="d-inline" onsubmit="return confirm('Delete this alert?');">
                                            <?= renderCSRFField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-danger" title="Delete Alert" style="font-size: 0.8rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
