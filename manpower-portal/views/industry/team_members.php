<?php
/**
 * Company Team Members & Collaborators Management — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Industry Team & Permission Management
 */

declare(strict_types=1);

$pageTitle = 'Team Members — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole('industry_admin');

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$industryCtrl = new IndustryController();

// Handle Member Actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_member_status') {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? 'Active');
        $res = $industryCtrl->setMemberStatus($companyId, $targetUserId, $newStatus);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'update_member') {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $res = $industryCtrl->updateTeamMember($companyId, $targetUserId, [
            'full_name'         => $_POST['full_name'] ?? '',
            'phone'             => $_POST['phone'] ?? '',
            'designation'       => $_POST['designation'] ?? '',
            'assigned_plant_id' => !empty($_POST['assigned_plant_id']) ? (int)$_POST['assigned_plant_id'] : null
        ]);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'update_permissions') {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $perms = [
            'view_profile'        => isset($_POST['perm_view_profile']),
            'edit_profile'        => isset($_POST['perm_edit_profile']),
            'view_plants'         => isset($_POST['perm_view_plants']),
            'manage_plants'       => isset($_POST['perm_manage_plants']),
            'view_requirements'   => isset($_POST['perm_view_requirements']),
            'manage_requirements' => isset($_POST['perm_manage_requirements']),
            'view_candidates'     => isset($_POST['perm_view_candidates']),
            'view_reports'        => isset($_POST['perm_view_reports'])
        ];
        $res = $industryCtrl->setUserPermissions($companyId, $targetUserId, $perms);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'resend_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $res = $industryCtrl->resendInvitation($companyId, $invId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'revoke_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $res = $industryCtrl->revokeInvitation($companyId, $invId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    }

    header("Location: " . app_url('/views/industry/team_members.php'));
    exit;
}

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$roleFilter = trim((string)($_GET['role'] ?? ''));

$members = $industryCtrl->getTeamMembers($companyId, [
    'search' => $search,
    'status' => $statusFilter,
    'role'   => $roleFilter
]);
$invitations = $industryCtrl->getInvitations($companyId);
$plants = $industryCtrl->getPlants($companyId);

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
                <li class="breadcrumb-item active" aria-current="page">Team Members</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="dashboard-title mb-1"><i class="bi bi-people me-2 text-primary"></i>Company & Industry Team Management</h1>
                <p class="supporting-text mb-0">Collaborate with plant HR coordinators, recruitment leads, and interview officers within your authorized company scope.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/industry/invite_member.php') ?>" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Invite Team Member
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/industry/team_members.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, email, phone..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select form-select-sm">
                            <option value="">All Roles</option>
                            <option value="industry_admin" <?= $roleFilter === 'industry_admin' ? 'selected' : '' ?>>Company Administrators</option>
                            <option value="industry_user" <?= $roleFilter === 'industry_user' ? 'selected' : '' ?>>Industry Team Members</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="Suspended" <?= $statusFilter === 'Suspended' ? 'selected' : '' ?>>Suspended Only</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button type="submit" class="btn btn-sm btn-primary me-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="<?= app_url('/views/industry/team_members.php') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Team Members Table -->
        <div class="card-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-person-check me-2 text-success"></i>Active Company Users (<?= count($members) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name & Contact</th>
                            <th>Email Address</th>
                            <th>Designation</th>
                            <th>Assigned Facility</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Created Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No team members found matching your search.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td class="font-monospace text-muted small">#<?= (int)$m['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($m['full_name']) ?></div>
                                        <div class="micro-text text-muted"><?= e($m['phone'] ?? 'No phone') ?></div>
                                    </td>
                                    <td class="small"><?= e($m['email']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($m['designation'] ?? 'Staff') ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= e($m['assigned_plant_name'] ?? 'All Facilities') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= e($m['role_label']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($m['status'] === 'Active'): ?>
                                            <span class="badge-status badge-verified">Active</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-suspended">Suspended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary" title="Edit Member & Facility Assignment" data-bs-toggle="modal" data-bs-target="#editModal<?= $m['id'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($m['role_name'] !== 'industry_admin'): ?>
                                                <button type="button" class="btn btn-outline-primary" title="Configure Granular Permissions" data-bs-toggle="modal" data-bs-target="#permModal<?= $m['id'] ?>">
                                                    <i class="bi bi-shield-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ((int)$m['id'] !== getUserId()): ?>
                                                <form method="POST" action="<?= app_url('/views/industry/team_members.php') ?>" class="d-inline" onsubmit="return confirm('Toggle status for this user?');">
                                                    <?= renderCSRFField() ?>
                                                    <input type="hidden" name="action" value="toggle_member_status">
                                                    <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $m['status'] === 'Active' ? 'Suspended' : 'Active' ?>">
                                                    <button type="submit" class="btn <?= $m['status'] === 'Active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $m['status'] === 'Active' ? 'Suspend Account' : 'Reactivate Account' ?>">
                                                        <?= $m['status'] === 'Active' ? '<i class="bi bi-person-x"></i>' : '<i class="bi bi-person-check"></i>' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Edit Member Modal -->
                                        <div class="modal fade text-start" id="editModal<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="<?= app_url('/views/industry/team_members.php') ?>">
                                                        <?= renderCSRFField() ?>
                                                        <input type="hidden" name="action" value="update_member">
                                                        <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">

                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-6 fw-bold">
                                                                <i class="bi bi-pencil me-2 text-primary"></i>Edit Member: <?= e($m['full_name']) ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="full_name" class="form-control form-control-sm" value="<?= e($m['full_name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Contact Phone Number</label>
                                                                <input type="text" name="phone" class="form-control form-control-sm" value="<?= e($m['phone'] ?? '') ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Corporate Designation</label>
                                                                <input type="text" name="designation" class="form-control form-control-sm" value="<?= e($m['designation'] ?? '') ?>" placeholder="e.g. Lead Talent Officer / Plant HR">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Assigned Manufacturing Plant</label>
                                                                <select name="assigned_plant_id" class="form-select form-select-sm">
                                                                    <option value="">-- All Facilities / Central HQ --</option>
                                                                    <?php foreach ($plants as $p): ?>
                                                                        <option value="<?= $p['id'] ?>" <?= ((int)($m['assigned_plant_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                                                            <?= e($p['plant_name']) ?> (<?= e($p['plant_code']) ?> &bull; <?= e($p['city']) ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="micro-text text-muted mt-1">Assigning a specific facility scopes the coordinator to candidate interviews at that physical site.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Configure Permissions Modal -->
                                        <?php if ($m['role_name'] !== 'industry_admin'): ?>
                                            <div class="modal fade text-start" id="permModal<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST" action="<?= app_url('/views/industry/team_members.php') ?>">
                                                            <?= renderCSRFField() ?>
                                                            <input type="hidden" name="action" value="update_permissions">
                                                            <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">

                                                            <div class="modal-header">
                                                                <h5 class="modal-title fs-6 fw-bold">
                                                                    <i class="bi bi-shield-lock me-2 text-primary"></i>Configure Permissions: <?= e($m['full_name']) ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small text-muted mb-3">
                                                                    Grant or restrict granular portal capabilities for this industry team member. Permissions are enforced on the backend.
                                                                </p>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_view_profile" id="pvp<?= $m['id'] ?>" <?= !empty($m['permissions']['view_profile']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pvp<?= $m['id'] ?>">View Company Profile</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_edit_profile" id="pep<?= $m['id'] ?>" <?= !empty($m['permissions']['edit_profile']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pep<?= $m['id'] ?>">Edit Company Profile</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_view_plants" id="pvpl<?= $m['id'] ?>" <?= !empty($m['permissions']['view_plants']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pvpl<?= $m['id'] ?>">View Plants</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_manage_plants" id="pmp<?= $m['id'] ?>" <?= !empty($m['permissions']['manage_plants']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pmp<?= $m['id'] ?>">Manage Plants</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_view_requirements" id="pvr<?= $m['id'] ?>" <?= !empty($m['permissions']['view_requirements']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pvr<?= $m['id'] ?>">View Requirements</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_manage_requirements" id="pmr<?= $m['id'] ?>" <?= !empty($m['permissions']['manage_requirements']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pmr<?= $m['id'] ?>">Manage Requirements</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_view_candidates" id="pvc<?= $m['id'] ?>" <?= !empty($m['permissions']['view_candidates']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pvc<?= $m['id'] ?>">View Candidates</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-check form-switch mb-2">
                                                                            <input class="form-check-input" type="checkbox" name="perm_view_reports" id="pvrp<?= $m['id'] ?>" <?= !empty($m['permissions']['view_reports']) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small" for="pvrp<?= $m['id'] ?>">View Reports</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-sm btn-primary">Save Permissions</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Invitations Table -->
        <div class="card-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-envelope-open me-2 text-warning"></i>Pending Team Invitations (<?= count($invitations) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invited Name</th>
                            <th>Email Address</th>
                            <th>Designated Role</th>
                            <th>Invited By</th>
                            <th>Status</th>
                            <th>Expires On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invitations)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No pending team invitations.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invitations as $inv): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= e($inv['name']) ?></td>
                                    <td class="small"><?= e($inv['email']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($inv['role_label']) ?></span></td>
                                    <td class="small text-muted"><?= e($inv['invited_by_name'] ?? 'Company Admin') ?></td>
                                    <td>
                                        <?php if ($inv['status'] === 'Pending'): ?>
                                            <span class="badge-status badge-pending">Pending</span>
                                        <?php elseif ($inv['status'] === 'Accepted'): ?>
                                            <span class="badge-status badge-verified">Accepted</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-rejected"><?= e($inv['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y H:i', strtotime($inv['expires_at'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($inv['status'] === 'Pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" action="<?= app_url('/views/industry/team_members.php') ?>" class="d-inline">
                                                    <?= renderCSRFField() ?>
                                                    <input type="hidden" name="action" value="resend_invitation">
                                                    <input type="hidden" name="invitation_id" value="<?= $inv['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-primary" title="Resend 7-Day Token">
                                                        <i class="bi bi-arrow-repeat"></i> Resend
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= app_url('/views/industry/team_members.php') ?>" class="d-inline" onsubmit="return confirm('Revoke this pending invitation?');">
                                                    <?= renderCSRFField() ?>
                                                    <input type="hidden" name="action" value="revoke_invitation">
                                                    <input type="hidden" name="invitation_id" value="<?= $inv['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Revoke Invitation">
                                                        <i class="bi bi-x-circle"></i> Revoke
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
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
