<?php
/**
 * Top Navigation Bar — Modern Enterprise Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/permissions.php';

$auth = getAuthUser();
$headerTitle = $pageTitle ?? 'Dashboard';

// Fetch unread notifications for active user
$unreadNotifs = [];
$unreadCount = 0;
$notifUrl = app_url('/views/industry/notifications.php');
if ($auth && !empty($auth['id'])) {
    if (($auth['role_name'] ?? '') === 'super_admin') {
        $notifUrl = app_url('/views/super_admin/notifications.php');
    }
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = Database::getConnection();
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `recipient_user_id` = :uid AND `is_read` = 0");
        $countStmt->execute([':uid' => $auth['id']]);
        $unreadCount = (int)$countStmt->fetchColumn();

        $notifStmt = $pdo->prepare("SELECT id, title, message, action_url, created_at 
            FROM `notifications` 
            WHERE `recipient_user_id` = :uid AND `is_read` = 0 
            ORDER BY id DESC LIMIT 5");
        $notifStmt->execute([':uid' => $auth['id']]);
        $unreadNotifs = $notifStmt->fetchAll();
    } catch (Exception $e) {
        // Silently fallback if notification query fails
    }
}
?>
<header class="top-navbar d-flex align-items-center justify-content-between">
    <!-- Left: Sidebar Toggle, Title & Breadcrumb -->
    <div class="d-flex align-items-center gap-3">
        <!-- Desktop Sidebar Collapse Toggle -->
        <button type="button" class="btn btn-sm btn-nav-icon d-none d-lg-inline-flex" id="sidebarCollapseBtn" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
        <!-- Mobile Sidebar Open Button -->
        <button type="button" class="btn btn-sm btn-nav-icon d-lg-none" id="sidebarToggle" title="Open Menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        
        <!-- Breadcrumb / Header Title -->
        <div>
            <div class="breadcrumb-custom d-none d-sm-flex">
                <span>PRAYAS</span>
                <span class="separator">/</span>
                <span>Portal</span>
                <span class="separator">/</span>
                <span class="text-dark fw-semibold"><?= e($headerTitle) ?></span>
            </div>
            <div class="fw-bold text-dark d-sm-none small"><?= e($headerTitle) ?></div>
        </div>
    </div>

    <!-- Center: Global Quick Search Input -->
    <div class="search-container d-none d-md-block position-relative">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control search-input" id="globalSearchInput" placeholder="Search requirements, companies, candidates..." aria-label="Search">
    </div>

    <!-- Right: Actions, Notifications & User Dropdown -->
    <div class="d-flex align-items-center gap-3">
        <!-- Notifications Dropdown -->
        <div class="dropdown">
            <button class="btn-nav-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="View notifications">
                <i class="bi bi-bell fs-6"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; border: 2px solid #FFFFFF;">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="width: 320px; max-height: 400px; overflow-y: auto;">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <span class="fw-bold text-dark small">Notifications</span>
                    <span class="badge bg-light text-secondary"><?= $unreadCount ?> new</span>
                </div>
                <?php if (empty($unreadNotifs)): ?>
                    <div class="px-3 py-4 text-center text-muted small">
                        <i class="bi bi-bell-slash d-block fs-3 mb-2 text-secondary"></i>
                        No unread notifications.
                    </div>
                <?php else: ?>
                    <?php foreach ($unreadNotifs as $n): ?>
                        <a class="dropdown-item py-2 px-3 border-bottom d-flex flex-column" href="<?= e(app_url($n['action_url'] ?: '')) ?>">
                            <div class="fw-semibold small text-dark"><?= e($n['title']) ?></div>
                            <div class="text-muted small text-truncate" style="max-width: 280px;"><?= e($n['message']) ?></div>
                            <div class="micro-text mt-1 text-secondary"><?= date('d M, H:i', strtotime($n['created_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="pt-2 text-center">
                    <a class="text-decoration-none small fw-semibold" style="color: var(--primary);" href="<?= e($notifUrl) ?>">
                        View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="user-profile-btn border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar-circle">
                    <?= strtoupper(substr($auth['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-sm-block">
                    <div class="fw-bold text-dark small" style="line-height: 1.1;"><?= e($auth['full_name'] ?? 'Authorized User') ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= e($auth['role_label'] ?? 'Staff') ?></div>
                </div>
                <i class="bi bi-chevron-down text-muted small ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-dark small"><?= e($auth['full_name'] ?? '') ?></div>
                    <div class="text-muted micro-text"><?= e($auth['email'] ?? '') ?></div>
                    <?php if (!empty($auth['company_name'])): ?>
                        <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">
                            <i class="bi bi-building me-1"></i><?= e($auth['company_name']) ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li>
                    <a class="dropdown-item py-2 small" href="<?= app_url('/views/industry/my_profile.php') ?>">
                        <i class="bi bi-person me-2 text-muted"></i>My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 small" href="<?= app_url('/views/auth/change_password.php') ?>">
                        <i class="bi bi-shield-lock me-2 text-muted"></i>Change Password
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2 small text-danger fw-semibold" href="<?= app_url('/views/auth/logout.php') ?>">
                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                    </a>
                </li>
            </ul>
        </div>

        <!-- Direct Quick Logout Button in Header -->
        <a href="<?= app_url('/views/auth/logout.php') ?>" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 py-1 px-2 text-decoration-none shadow-sm" title="Sign Out of Portal">
            <i class="bi bi-box-arrow-right"></i>
            <span class="d-none d-md-inline small fw-semibold">Sign Out</span>
        </a>
    </div>
</header>
