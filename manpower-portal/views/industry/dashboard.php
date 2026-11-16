<?php
/**
 * Industry Partner Admin Dashboard — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Industry Administration & Manufacturing Plant Management
 */

declare(strict_types=1);

$pageTitle = 'Industry Dashboard — PRAYAS Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    echo "<div class='container p-5 text-center'><h4>Error: No company affiliated with this account. Please contact PRAYAS administration.</h4></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$industryCtrl = new IndustryController();
$metrics = $industryCtrl->getDashboardMetrics($companyId);
$company = $metrics['company'] ?? [];
$plants = $industryCtrl->getPlants($companyId);
$teamMembers = $industryCtrl->getTeamMembers($companyId);
$notifications = $industryCtrl->getNotifications($companyId, getUserId());
$recentReqs = $industryCtrl->getMyRequirements($companyId, [], 1, 5);

// Fetch Recent Audit Activities Scoped to Company
$auditStmt = Database::getConnection()->prepare("SELECT * FROM `audit_logs` WHERE company_id = :cid ORDER BY id DESC LIMIT 6");
$auditStmt->execute([':cid' => $companyId]);
$recentActivities = $auditStmt->fetchAll();

$verStatus = $company['verification_status'] ?? 'Pending';
$badgeStyle = match ($verStatus) {
    'Verified', 'Active' => 'badge-verified',
    'Under Review'       => 'badge-under-review',
    'Rejected'           => 'badge-rejected',
    'Suspended'          => 'badge-suspended',
    default              => 'badge-pending'
};
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- SECTION 1: Industry Profile Summary Banner -->
        <div class="card-enterprise mb-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #ffffff; border: none;">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-2">
                            <?php if (!empty($company['company_logo'])): ?>
                                <img src="<?= e($company['company_logo']) ?>" alt="Logo" class="rounded me-3" style="max-height: 52px; max-width: 80px; object-fit: contain; background: #fff; padding: 4px;">
                            <?php else: ?>
                                <div class="rounded me-3 d-flex align-items-center justify-content-center bg-white text-dark fw-bold" style="width: 52px; height: 52px; font-size: 1.35rem; color: #0F172A;">
                                    <?= strtoupper(substr($company['company_name'] ?? 'C', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h1 class="dashboard-title text-white mb-0"><?= e($company['company_name'] ?? 'Enterprise Partner') ?></h1>
                                    <span class="badge bg-secondary bg-opacity-50 text-white font-monospace small">IND-<?= str_pad((string)$companyId, 4, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <div class="supporting-text text-white-50 mt-1">
                                    <span class="me-3"><i class="bi bi-tag me-1 text-primary"></i><?= e($company['industry_type'] ?? 'Manufacturing') ?></span>
                                    <span class="me-3"><i class="bi bi-geo-alt me-1 text-info"></i><?= e($company['city'] ?? '') ?>, <?= e($company['state'] ?? '') ?></span>
                                    <span><i class="bi bi-person-badge me-1 text-warning"></i>CIN: <?= e($company['registration_number'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-inline-block text-start bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-25">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-white-50 me-3">Account Status:</span>
                                <span class="badge-status <?= $badgeStyle ?>"><?= e($verStatus) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="micro-text text-white-50 me-3">Profile Completion:</span>
                                <span class="fw-bold text-white small"><?= $metrics['profile_completion'] ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; width: 180px; background: rgba(255,255,255,0.15);">
                                <div class="progress-bar" style="background-color: var(--primary); width: <?= $metrics['profile_completion'] ?>%" role="progressbar" aria-valuenow="<?= $metrics['profile_completion'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($verStatus === 'Pending' || $verStatus === 'Under Review'): ?>
                    <div class="alert alert-warning d-flex align-items-center mt-3 mb-0 py-2 small" role="alert">
                        <i class="bi bi-info-circle-fill me-2 flex-shrink-0 fs-5"></i>
                        <div>
                            <strong>Account Under Verification:</strong> Your corporate profile is currently being vetted by PRAYAS administration. You can configure manufacturing plants and draft requisitions while candidate allocation will be unlocked upon formal approval.
                        </div>
                    </div>
                <?php elseif ($verStatus === 'Rejected'): ?>
                    <div class="alert alert-danger d-flex align-items-center mt-3 mb-0 py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0 fs-5"></i>
                        <div>
                            <strong>Verification Incomplete:</strong> <?= e($company['rejection_reason'] ?? 'Documentation needs update.') ?> Please update your company profile or contact the academic desk.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK ACTIONS BAR -->
        <div class="card-enterprise mb-4 py-2">
            <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark small text-uppercase me-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Actions:</span>
                    <a href="<?= app_url('/views/industry/company_profile.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-building me-1"></i>View Industry Profile
                    </a>
                    <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-geo-alt me-1"></i>Manage Manufacturing Plants
                    </a>
                    <a href="<?= app_url('/views/industry/team_members.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-people me-1"></i>Manage Industry Team
                    </a>
                    <a href="<?= app_url('/views/industry/notifications.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-bell me-1"></i>View Notifications
                    </a>
                </div>
                <div>
                    <a href="<?= app_url('/views/industry/plant_form.php') ?>" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-plus-circle me-1"></i>Add Plant
                    </a>
                    <a href="<?= app_url('/views/industry/requirement_form.php') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-file-earmark-plus me-1"></i>New Requirement
                    </a>
                </div>
            </div>
        </div>

        <!-- SUMMARY CARDS: EXACT 6 REAL DATABASE METRICS -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Manufacturing Plants -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Total Plants</div>
                    <div class="metric-value text-dark"><?= (int)$metrics['total_plants'] ?></div>
                    <div class="supporting-text mt-1 text-muted">
                        <span>All registered units</span>
                    </div>
                </div>
            </div>

            <!-- 2. Active Manufacturing Plants -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Active Plants</div>
                    <div class="metric-value text-success"><?= (int)$metrics['active_plants'] ?></div>
                    <div class="supporting-text mt-1 text-success fw-semibold">
                        <span><i class="bi bi-check-circle me-1"></i>Operational</span>
                    </div>
                </div>
            </div>

            <!-- 3. Inactive Manufacturing Plants -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Inactive Plants</div>
                    <div class="metric-value text-secondary"><?= (int)$metrics['inactive_plants'] ?></div>
                    <div class="supporting-text mt-1 text-muted">
                        <span>Standby / Idle</span>
                    </div>
                </div>
            </div>

            <!-- 4. Industry Team Members -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Team Members</div>
                    <div class="metric-value text-primary"><?= (int)$metrics['active_team_members'] ?></div>
                    <div class="supporting-text mt-1 text-primary fw-semibold">
                        <span>Active accounts</span>
                    </div>
                </div>
            </div>

            <!-- 5. Total Departments -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Total Departments</div>
                    <div class="metric-value text-info"><?= (int)$metrics['total_departments'] ?></div>
                    <div class="supporting-text mt-1 text-muted">
                        <span>Across all facilities</span>
                    </div>
                </div>
            </div>

            <!-- 6. Active Manpower Requirements -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="summary-card h-100">
                    <div class="metric-label">Active Requisitions</div>
                    <div class="metric-value text-danger"><?= (int)$metrics['active_requirements'] ?></div>
                    <div class="supporting-text mt-1 text-danger fw-semibold">
                        <span><?= (int)$metrics['total_vacancies'] ?> Vacancies</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN DASHBOARD CONTENT: PLANTS OVERVIEW & STATUS BREAKDOWN -->
        <div class="row g-4 mb-4">
            <!-- Left: Manufacturing Plant Overview -->
            <div class="col-lg-8">
                <div class="card-enterprise h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Manufacturing Plant Overview</h2>
                        <div>
                            <a href="<?= app_url('/views/industry/plant_form.php') ?>" class="btn btn-sm btn-primary me-1"><i class="bi bi-plus-circle me-1"></i>Add Plant</a>
                            <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-prayas table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Plant Code</th>
                                        <th>Facility Name</th>
                                        <th>Location</th>
                                        <th>Contact Person</th>
                                        <th>Requisitions</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($plants)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="empty-state py-2">
                                                    <i class="bi bi-geo-alt text-muted fs-3 mb-2 d-block"></i>
                                                    <div class="fw-semibold">No manufacturing plants configured</div>
                                                    <div class="small text-muted mb-2">Register your physical plant facilities to assign recruitment coordinators.</div>
                                                    <a href="<?= app_url('/views/industry/plant_form.php') ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-plus-circle me-1"></i>Add Plant
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($plants, 0, 5) as $p): ?>
                                            <tr>
                                                <td class="fw-bold font-monospace text-primary"><?= e($p['plant_code']) ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($p['plant_name']) ?></div>
                                                    <div class="micro-text text-muted"><?= e($p['plant_type'] ?? 'Manufacturing Unit') ?></div>
                                                </td>
                                                <td class="small">
                                                    <div><?= e($p['city']) ?>, <?= e($p['district']) ?></div>
                                                    <div class="micro-text text-muted"><?= e($p['state']) ?></div>
                                                </td>
                                                <td class="small">
                                                    <div><?= e($p['contact_person']) ?></div>
                                                    <div class="micro-text text-muted"><?= e($p['contact_number']) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border"><?= (int)($p['requirement_count'] ?? 0) ?></span>
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
                                                    </div>
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

            <!-- Right: Plant Status & Capacity Overview -->
            <div class="col-lg-4">
                <div class="card-enterprise h-100">
                    <div class="card-header">
                        <h2 class="section-title mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>Plant Status Overview</h2>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded-3 mb-3 border text-center">
                            <div class="small text-muted mb-1">Operational Utilization</div>
                            <div class="fs-2 fw-bold text-success mb-1">
                                <?= $metrics['total_plants'] > 0 ? round(($metrics['active_plants'] / $metrics['total_plants']) * 100) : 0 ?>%
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?= $metrics['total_plants'] > 0 ? round(($metrics['active_plants'] / $metrics['total_plants']) * 100) : 0 ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between micro-text text-muted mt-2">
                                <span><?= $metrics['active_plants'] ?> Active</span>
                                <span><?= $metrics['inactive_plants'] ?> Standby</span>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold">Total Manufacturing Sectors:</span>
                                <span class="badge bg-secondary"><?= e($company['industry_type'] ?? 'Industrial') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold">Distinct Departments:</span>
                                <span class="fw-bold text-dark"><?= (int)$metrics['total_departments'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold">Authorized Industry Users:</span>
                                <span class="fw-bold text-primary"><?= (int)$metrics['active_team_members'] ?></span>
                            </div>
                        </div>

                        <div class="text-center pt-2">
                            <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-sliders me-1"></i>Manage All Facility Units
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TEAM OVERVIEW & RECENT ACTIVITIES -->
        <div class="row g-4 mb-4">
            <!-- Industry Team Overview -->
            <div class="col-lg-6">
                <div class="card-enterprise h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0"><i class="bi bi-people me-2 text-primary"></i>Industry Team Overview</h2>
                        <a href="<?= app_url('/views/industry/team_members.php') ?>" class="btn btn-sm btn-outline-primary">Manage Team</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-prayas table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role / Designation</th>
                                        <th>Assigned Facility</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teamMembers)): ?>
                                        <tr><td colspan="4" class="text-center py-3 text-muted">No team members registered yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($teamMembers, 0, 4) as $tm): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($tm['full_name']) ?></div>
                                                    <div class="micro-text text-muted"><?= e($tm['email']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= e($tm['role_label']) ?></span>
                                                    <div class="micro-text text-muted"><?= e($tm['designation'] ?? 'Staff') ?></div>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= e($tm['assigned_plant_name'] ?? 'All Facilities') ?>
                                                </td>
                                                <td>
                                                    <?php if ($tm['status'] === 'Active'): ?>
                                                        <span class="badge-status badge-verified">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge-status badge-suspended">Suspended</span>
                                                    <?php endif; ?>
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

            <!-- Recent Industry Activities (Audit Feed) -->
            <div class="col-lg-6">
                <div class="card-enterprise h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Recent Industry Activities</h2>
                        <span class="badge bg-light text-dark border small">Audit Trail</span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-4 text-muted small">No audit log records for this company yet.</div>
                        <?php else: ?>
                            <div class="timeline-feed">
                                <?php foreach ($recentActivities as $act): ?>
                                    <div class="d-flex align-items-start mb-3 pb-2 border-bottom">
                                        <div class="rounded-circle p-2 bg-light text-primary me-3 flex-shrink-0" style="width: 36px; height: 36px; text-align: center;">
                                            <i class="bi bi-activity"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-dark small"><?= e(str_replace('_', ' ', $act['action'])) ?></span>
                                                <span class="micro-text text-muted"><?= date('M d, H:i', strtotime($act['created_at'])) ?></span>
                                            </div>
                                            <div class="micro-text text-muted mt-1 font-monospace">
                                                <?= e(substr($act['details_json'] ?? '{}', 0, 90)) ?>...
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RELEVANT NOTIFICATIONS & RECENT REQUISITIONS -->
        <div class="row g-4 mb-4">
            <!-- Relevant Notifications -->
            <div class="col-lg-4">
                <div class="card-enterprise h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0"><i class="bi bi-bell me-2 text-primary"></i>Relevant Notifications</h2>
                        <a href="<?= app_url('/views/industry/notifications.php') ?>" class="btn btn-sm btn-outline-secondary">All</a>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-4 text-muted small">No unread notifications at this time.</div>
                        <?php else: ?>
                            <div class="notification-list">
                                <?php foreach (array_slice($notifications, 0, 4) as $notif): ?>
                                    <div class="p-2 mb-2 rounded bg-light border-start border-3 border-primary">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark small"><?= e($notif['title']) ?></span>
                                            <span class="micro-text text-muted"><?= date('M d', strtotime($notif['created_at'])) ?></span>
                                        </div>
                                        <div class="micro-text text-secondary mt-1"><?= e($notif['message']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Requisitions -->
            <div class="col-lg-8">
                <div class="card-enterprise h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>Recent Manpower Requisitions</h2>
                        <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-prayas table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Requisition Code</th>
                                        <th>Job Title</th>
                                        <th>Plant Facility</th>
                                        <th>Vacancies</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentReqs['data'])): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No manpower requisitions submitted yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentReqs['data'] as $r): ?>
                                            <tr>
                                                <td class="fw-bold font-monospace text-primary"><?= e($r['requirement_id']) ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($r['title']) ?></div>
                                                    <div class="micro-text text-muted"><?= e($r['job_location_city']) ?>, <?= e($r['job_location_district']) ?></div>
                                                </td>
                                                <td class="small text-muted"><?= e($r['plant_name'] ?? 'Main Facility') ?></td>
                                                <td class="fw-bold"><?= (int)$r['quantity_required'] ?></td>
                                                <td class="small text-muted"><?= e($r['application_deadline']) ?></td>
                                                <td><span class="badge-status badge-approved"><?= e($r['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
