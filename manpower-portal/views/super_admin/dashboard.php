<?php
/**
 * Super Admin Master Governance Dashboard — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Super Admin Governance Dashboard — PRAYAS Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

requireRole('super_admin');

$pdo = Database::getConnection();

// -----------------------------------------------------------------------------
// 1. Fetch Real Database Metrics (8 Required Summary Cards)
// -----------------------------------------------------------------------------
$metrics = [];

// Company Metrics
$metrics['total_companies']     = (int)$pdo->query("SELECT COUNT(*) FROM `companies`")->fetchColumn();
$metrics['verified_companies']  = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` IN ('Verified', 'Active')")->fetchColumn();
$metrics['pending_companies']   = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` = 'Pending'")->fetchColumn();
$metrics['under_review']        = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` = 'Under Review'")->fetchColumn();
$metrics['rejected_companies']  = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` = 'Rejected'")->fetchColumn();
$metrics['suspended_companies'] = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` = 'Suspended'")->fetchColumn();
$metrics['active_companies']    = (int)$pdo->query("SELECT COUNT(*) FROM `companies` WHERE `verification_status` IN ('Active', 'Verified')")->fetchColumn();

// User Metrics
$metrics['company_admins']      = (int)$pdo->query("SELECT COUNT(*) FROM `users` u JOIN `roles` r ON u.role_id = r.id WHERE r.name = 'industry_admin'")->fetchColumn();
$metrics['total_users']         = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();

// -----------------------------------------------------------------------------
// 2. Fetch Section A: Company Overview Feeds
// -----------------------------------------------------------------------------
$recentRegistered = $pdo->query("SELECT id, company_name, industry_type, district, hr_contact_name, hr_contact_email, verification_status, created_at 
    FROM `companies` ORDER BY id DESC LIMIT 5")->fetchAll();

$recentVerified = $pdo->query("SELECT id, company_name, industry_type, district, hr_contact_name, hr_contact_email, verification_status, verified_at 
    FROM `companies` WHERE `verification_status` IN ('Verified', 'Active') ORDER BY verified_at DESC, id DESC LIMIT 5")->fetchAll();

$awaitingApproval = $pdo->query("SELECT id, company_name, industry_type, district, hr_contact_name, hr_contact_email, verification_status, created_at 
    FROM `companies` WHERE `verification_status` IN ('Pending', 'Under Review') ORDER BY id DESC LIMIT 5")->fetchAll();

$suspendedList = $pdo->query("SELECT id, company_name, industry_type, district, hr_contact_name, hr_contact_email, verification_status, updated_at 
    FROM `companies` WHERE `verification_status` = 'Suspended' ORDER BY updated_at DESC, id DESC LIMIT 5")->fetchAll();

// -----------------------------------------------------------------------------
// 3. Fetch Section C: System Activity Feeds
// -----------------------------------------------------------------------------
$recentAdminActivities = $pdo->query("SELECT a.id, a.action, a.ip_address, a.created_at, a.details_json, u.full_name AS actor_name, c.company_name
    FROM `audit_logs` a
    LEFT JOIN `users` u ON a.user_id = u.id
    LEFT JOIN `companies` c ON a.company_id = c.id
    WHERE a.action LIKE '%COMPANY%' OR a.action LIKE '%USER%' OR a.action LIKE '%SETTING%' OR a.action LIKE '%ROUTE%'
    ORDER BY a.id DESC LIMIT 6")->fetchAll();

$recentUsersCreated = $pdo->query("SELECT u.id, u.full_name, u.email, u.status, u.created_at, r.label AS role_label, c.company_name
    FROM `users` u
    JOIN `roles` r ON u.role_id = r.id
    LEFT JOIN `companies` c ON u.company_id = c.id
    ORDER BY u.id DESC LIMIT 5")->fetchAll();
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Welcome & Quick Action Bar -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h1 class="dashboard-title">Super Admin Governance Panel</h1>
                <p class="supporting-text mb-0">Centralized administrative control over corporate partners, approvals, capacity limits & audit trail</p>
            </div>
            <!-- Section D: Quick Actions Bar -->
            <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2">
                <a href="<?= app_url('/views/super_admin/company_form.php') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-building-add me-1"></i> Add Company
                </a>
                <a href="<?= app_url('/views/super_admin/companies.php?status=Pending') ?>" class="btn btn-outline-primary btn-sm position-relative">
                    <i class="bi bi-hourglass-split me-1"></i> Review Pending
                    <?php if ($metrics['pending_companies'] > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?= $metrics['pending_companies'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-buildings me-1"></i> Manage Companies
                </a>
                <a href="<?= app_url('/views/super_admin/users.php?role=industry_admin') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-people me-1"></i> Administrators
                </a>
                <a href="<?= app_url('/views/super_admin/audit_logs.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-journal-text me-1"></i> Audit Logs
                </a>
            </div>
        </div>

        <!-- 8 Required Summary Cards (Grid Row) -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Registered Companies -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Total Registered</div>
                            <div class="metric-value"><?= number_format($metrics['total_companies']) ?></div>
                            <div class="supporting-text mt-1">Companies in database</div>
                        </div>
                        <div class="icon-box icon-box-primary">
                            <i class="bi bi-buildings"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Verified Companies -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Verified Companies</div>
                            <div class="metric-value text-success"><?= number_format($metrics['verified_companies']) ?></div>
                            <div class="supporting-text mt-1 text-success fw-semibold">
                                <i class="bi bi-check-circle me-1"></i>Approved & Active
                            </div>
                        </div>
                        <div class="icon-box" style="background: rgba(22, 163, 74, 0.1); color: #16A34A;">
                            <i class="bi bi-patch-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Pending Company Verification -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Pending Verification</div>
                            <div class="metric-value text-warning"><?= number_format($metrics['pending_companies']) ?></div>
                            <div class="supporting-text mt-1">
                                <a href="<?= app_url('/views/super_admin/companies.php?status=Pending') ?>" class="text-warning text-decoration-none fw-semibold">
                                    Needs review <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="icon-box icon-box-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Rejected Companies -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Rejected Companies</div>
                            <div class="metric-value text-danger"><?= number_format($metrics['rejected_companies']) ?></div>
                            <div class="supporting-text mt-1 text-danger">Preserved in registry</div>
                        </div>
                        <div class="icon-box icon-box-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Suspended Companies -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Suspended Companies</div>
                            <div class="metric-value" style="color: #64748B;"><?= number_format($metrics['suspended_companies']) ?></div>
                            <div class="supporting-text mt-1">Access deactivated</div>
                        </div>
                        <div class="icon-box" style="background: rgba(100, 116, 139, 0.1); color: #64748B;">
                            <i class="bi bi-slash-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Total Company Administrators -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Company Admins</div>
                            <div class="metric-value" style="color: var(--deep-navy);"><?= number_format($metrics['company_admins']) ?></div>
                            <div class="supporting-text mt-1">Industry Administrators</div>
                        </div>
                        <div class="icon-box" style="background: rgba(15, 23, 42, 0.1); color: var(--deep-navy);">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Total Registered Users -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Total Portal Users</div>
                            <div class="metric-value"><?= number_format($metrics['total_users']) ?></div>
                            <div class="supporting-text mt-1">Across all role tiers</div>
                        </div>
                        <div class="icon-box icon-box-info">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. Total Active Companies -->
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="metric-label">Total Active Companies</div>
                            <div class="metric-value text-success"><?= number_format($metrics['active_companies']) ?></div>
                            <div class="supporting-text mt-1 text-success">Compliant & operational</div>
                        </div>
                        <div class="icon-box" style="background: rgba(22, 163, 74, 0.1); color: #16A34A;">
                            <i class="bi bi-activity"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION B: Verification Overview (Lifecycle Funnel) -->
        <div class="card-enterprise mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="section-title mb-0">Company Verification & Compliance Overview</h2>
                    <p class="supporting-text mb-0">Breakdown of corporate partners across official onboarding stages</p>
                </div>
                <a href="<?= app_url('/views/super_admin/companies.php') ?>" class="btn btn-sm btn-outline-primary">
                    Manage Verification Queue
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="metric-label text-warning">Pending Review</div>
                            <div class="fs-4 fw-bold text-warning mt-1"><?= $metrics['pending_companies'] ?></div>
                            <a href="<?= app_url('/views/super_admin/companies.php?status=Pending') ?>" class="btn btn-link btn-sm text-decoration-none p-0 mt-1">View list &rarr;</a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="metric-label" style="color: #0284C7;">Under Review</div>
                            <div class="fs-4 fw-bold mt-1" style="color: #0284C7;"><?= $metrics['under_review'] ?></div>
                            <a href="<?= app_url('/views/super_admin/companies.php?status=Under+Review') ?>" class="btn btn-link btn-sm text-decoration-none p-0 mt-1">View list &rarr;</a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="metric-label text-success">Approved / Verified</div>
                            <div class="fs-4 fw-bold text-success mt-1"><?= $metrics['verified_companies'] ?></div>
                            <a href="<?= app_url('/views/super_admin/companies.php?status=Verified') ?>" class="btn btn-link btn-sm text-decoration-none p-0 mt-1">View list &rarr;</a>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="metric-label text-danger">Rejected</div>
                            <div class="fs-4 fw-bold text-danger mt-1"><?= $metrics['rejected_companies'] ?></div>
                            <a href="<?= app_url('/views/super_admin/companies.php?status=Rejected') ?>" class="btn btn-link btn-sm text-decoration-none p-0 mt-1">View list &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION A: Company Overview (Tabs for Recents, Verified, Awaiting, Suspended) -->
        <div class="card-table mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="companyOverviewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recent-pane" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i> Recently Registered
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="verified-tab" data-bs-toggle="tab" data-bs-target="#verified-pane" type="button" role="tab">
                            <i class="bi bi-patch-check text-success me-1"></i> Recently Verified
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="awaiting-tab" data-bs-toggle="tab" data-bs-target="#awaiting-pane" type="button" role="tab">
                            <i class="bi bi-hourglass-split text-warning me-1"></i> Awaiting Approval (<?= count($awaitingApproval) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="suspended-tab" data-bs-toggle="tab" data-bs-target="#suspended-pane" type="button" role="tab">
                            <i class="bi bi-slash-circle text-muted me-1"></i> Suspended Companies
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content" id="companyOverviewTabContent">
                <!-- Tab Pane 1: Recently Registered -->
                <div class="tab-pane fade show active" id="recent-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Industry Type / District</th>
                                    <th>HR Contact</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentRegistered)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No recently registered companies found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentRegistered as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($c['company_name']) ?></div>
                                                <div class="micro-text text-muted">Registered: <?= date('d M Y, H:i', strtotime($c['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold"><?= e($c['industry_type']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['district']) ?></div>
                                            </td>
                                            <td>
                                                <div class="small"><?= e($c['hr_contact_name']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['hr_contact_email']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-status <?= match ($c['verification_status']) { 'Verified', 'Active' => 'badge-verified', 'Under Review' => 'badge-under-review', 'Rejected' => 'badge-rejected', 'Suspended' => 'badge-suspended', default => 'badge-pending' } ?>">
                                                    <?= e($c['verification_status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 2: Recently Verified -->
                <div class="tab-pane fade" id="verified-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Industry Type / District</th>
                                    <th>HR Contact</th>
                                    <th>Verified Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentVerified)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No verified companies found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentVerified as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($c['company_name']) ?></div>
                                                <span class="badge-status badge-verified">Verified</span>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold"><?= e($c['industry_type']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['district']) ?></div>
                                            </td>
                                            <td>
                                                <div class="small"><?= e($c['hr_contact_name']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['hr_contact_email']) ?></div>
                                            </td>
                                            <td class="small text-muted font-monospace">
                                                <?= $c['verified_at'] ? date('d M Y, H:i', strtotime($c['verified_at'])) : 'Recorded' ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    View Profile
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 3: Awaiting Approval -->
                <div class="tab-pane fade" id="awaiting-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Sector / District</th>
                                    <th>Current Status</th>
                                    <th>Submitted Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($awaitingApproval)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-success fw-semibold">
                                            <i class="bi bi-check-circle me-1"></i> No companies currently awaiting approval. Queue is clear!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($awaitingApproval as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($c['company_name']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['hr_contact_name']) ?> (<?= e($c['hr_contact_email']) ?>)</div>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold"><?= e($c['industry_type']) ?></div>
                                                <div class="micro-text text-muted"><?= e($c['district']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-status <?= ($c['verification_status'] === 'Under Review') ? 'badge-under-review' : 'badge-pending' ?>">
                                                    <?= e($c['verification_status']) ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></td>
                                            <td class="text-end">
                                                <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $c['id']) ?>" class="btn btn-sm btn-primary">
                                                    Review & Decision
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Pane 4: Suspended Companies -->
                <div class="tab-pane fade" id="suspended-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Sector / District</th>
                                    <th>HR Liaison</th>
                                    <th>Suspension Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suspendedList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No suspended companies in registry.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suspendedList as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-muted"><?= e($c['company_name']) ?></div>
                                                <span class="badge-status badge-suspended">Suspended</span>
                                            </td>
                                            <td class="small text-muted"><?= e($c['industry_type']) ?> &bull; <?= e($c['district']) ?></td>
                                            <td class="small text-muted"><?= e($c['hr_contact_name']) ?> (<?= e($c['hr_contact_email']) ?>)</td>
                                            <td class="small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($c['updated_at'])) ?></td>
                                            <td class="text-end">
                                                <a href="<?= app_url('/views/super_admin/company_details.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                                    Manage Account
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION C: System Activity (Two Columns: Super Admin Activities & Recent Users) -->
        <div class="row g-4 mb-4">
            <!-- Left: Recent Super Admin Activities -->
            <div class="col-lg-7">
                <div class="card-table h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Recent Administrative Activities</h3>
                            <p class="supporting-text mb-0">Audit ledger tracking company verifications, limit changes & governance actions</p>
                        </div>
                        <a href="<?= app_url('/views/super_admin/audit_logs.php') ?>" class="btn btn-sm btn-outline-primary">View Ledger</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Action</th>
                                    <th>Admin / Target</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAdminActivities)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted small">No administrative activities recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAdminActivities as $a): ?>
                                        <tr>
                                            <td class="small text-muted font-monospace"><?= date('d M, H:i', strtotime($a['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                                    <?= e($a['action']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold"><?= e($a['actor_name'] ?: 'Super Admin') ?></div>
                                                <div class="micro-text text-muted"><?= e($a['company_name'] ?: 'Platform') ?></div>
                                            </td>
                                            <td class="small text-muted" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= e($a['details_json'] ?? '') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Recent User Creation & Accounts -->
            <div class="col-lg-5">
                <div class="card-table h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0"><i class="bi bi-person-plus me-2 text-primary"></i>Recent User Accounts</h3>
                            <p class="supporting-text mb-0">Newly provisioned platform users</p>
                        </div>
                        <a href="<?= app_url('/views/super_admin/users.php') ?>" class="btn btn-sm btn-outline-primary">All Users</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table-prayas table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role / Organization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentUsersCreated)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted small">No users created recently.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentUsersCreated as $u): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold small"><?= e($u['full_name']) ?></div>
                                                <div class="micro-text text-muted"><?= e($u['email']) ?></div>
                                            </td>
                                            <td>
                                                <div class="small"><?= e($u['role_label']) ?></div>
                                                <div class="micro-text text-muted"><?= e($u['company_name'] ?: 'PRAYAS Academy') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-status <?= ($u['status'] === 'Active') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.7rem;">
                                                    <?= e($u['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
