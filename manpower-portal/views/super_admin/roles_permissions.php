<?php
/**
 * Super Admin Roles & Permission Matrix Management
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Roles & Permissions — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

requireRole('super_admin');

$pdo = Database::getConnection();

// Handle Permission Matrix Updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $roleId = (int)($_POST['role_id'] ?? 0);
    $submittedPerms = array_map('intval', $_POST['permissions'] ?? []);

    // Verify role exists
    $roleStmt = $pdo->prepare("SELECT name FROM `roles` WHERE id = :id");
    $roleStmt->execute([':id' => $roleId]);
    $roleName = $roleStmt->fetchColumn();

    if ($roleName) {
        // Critical Safety Guard: Super Admin must always retain core administrative permissions
        if ($roleName === 'super_admin') {
            // Ensure core permission IDs are kept
            $coreStmt = $pdo->query("SELECT id FROM `permissions` WHERE `name` IN ('settings.manage', 'audit.view', 'user.update', 'company.verify')");
            $coreIds = $coreStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($coreIds as $cid) {
                if (!in_array((int)$cid, $submittedPerms, true)) {
                    $submittedPerms[] = (int)$cid;
                }
            }
        }

        try {
            $pdo->beginTransaction();

            // Clear existing role permissions
            $delStmt = $pdo->prepare("DELETE FROM `role_permissions` WHERE `role_id` = :rid");
            $delStmt->execute([':rid' => $roleId]);

            // Re-insert submitted permissions
            if (!empty($submittedPerms)) {
                $insStmt = $pdo->prepare("INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:rid, :pid)");
                foreach ($submittedPerms as $pid) {
                    $insStmt->execute([':rid' => $roleId, ':pid' => $pid]);
                }
            }

            // Audit Log
            $auditStmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`, `details_json`) 
                VALUES (:uid, 'ROLE_PERMISSIONS_UPDATED', :ip, :details)");
            $auditStmt->execute([
                ':uid'     => getUserId(),
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':details' => json_encode(['role_id' => $roleId, 'role_name' => $roleName, 'assigned_count' => count($submittedPerms)])
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Permissions for role '{$roleName}' updated successfully.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = "Failed to update permissions: " . $e->getMessage();
        }
    }

    header("Location: /views/super_admin/roles_permissions.php?selected_role=" . $roleId);
    exit;
}

// Fetch all roles
$roles = $pdo->query("SELECT id, name, label, description FROM `roles` ORDER BY id ASC")->fetchAll();

$selectedRoleId = (int)($_GET['selected_role'] ?? ($roles[0]['id'] ?? 1));

// Fetch all permissions grouped by module
$allPerms = $pdo->query("SELECT id, name, module, description FROM `permissions` ORDER BY module ASC, name ASC")->fetchAll();
$groupedPerms = [];
foreach ($allPerms as $p) {
    $groupedPerms[$p['module']][] = $p;
}

// Fetch currently assigned permission IDs for selected role
$assignedStmt = $pdo->prepare("SELECT permission_id FROM `role_permissions` WHERE `role_id` = :rid");
$assignedStmt->execute([':rid' => $selectedRoleId]);
$assignedPermIds = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);
$assignedPermIds = array_map('intval', $assignedPermIds);
// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="p-4">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Title & Action Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--prayas-navy);">Role-Based Access Control (RBAC) Matrix</h3>
                <p class="text-muted small mb-0">Configure operational capabilities and authorization boundaries for all portal user tiers</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Role Selector -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <span class="fw-bold small text-uppercase" style="color: var(--prayas-navy);">
                            <i class="bi bi-shield-check me-1"></i> System Roles
                        </span>
                    </div>
                    <div class="list-group list-group-flush rounded-bottom-3">
                        <?php foreach ($roles as $r): ?>
                            <a href="?selected_role=<?= $r['id'] ?>" class="list-group-item list-group-item-action py-3 <?= ($selectedRoleId === (int)$r['id']) ? 'active' : '' ?>">
                                <div class="fw-bold small"><?= e($r['label']) ?></div>
                                <div class="font-monospace small text-truncate" style="font-size: 0.72rem;"><?= e($r['name']) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Security Note -->
                <div class="alert alert-info small shadow-sm">
                    <i class="bi bi-shield-lock-fill me-1"></i>
                    <strong>Lockout Protection:</strong> Essential administrative governance permissions for the Super Administrator cannot be revoked.
                </div>
            </div>

            <!-- Right Column: Permission Matrix -->
            <div class="col-lg-9">
                <form method="POST" action="">
                    <?= renderCSRFField() ?>
                    <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold small text-uppercase" style="color: var(--prayas-navy);">
                                    Configuring: <?php 
                                        $currRoleObj = array_values(array_filter($roles, fn($r) => (int)$r['id'] === $selectedRoleId))[0] ?? null;
                                        echo e($currRoleObj['label'] ?? 'Role');
                                    ?>
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary small ms-2">
                                    <?= count($assignedPermIds) ?> Capabilities Assigned
                                </span>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary px-3" style="background: var(--prayas-navy); border-color: var(--prayas-navy);">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>

                        <div class="card-body p-4">
                            <?php foreach ($groupedPerms as $module => $perms): ?>
                                <div class="mb-4">
                                    <h6 class="fw-bold text-uppercase pb-1 mb-3" style="color: var(--prayas-blue); font-size: 0.82rem; border-bottom: 2px solid #e2e8f0;">
                                        <i class="bi bi-folder2 me-1"></i> <?= strtoupper(e($module)) ?> MODULE
                                    </h6>
                                    <div class="row g-2">
                                        <?php foreach ($perms as $p): ?>
                                            <?php $isAssigned = in_array((int)$p['id'], $assignedPermIds, true); ?>
                                            <div class="col-md-6 col-xl-4">
                                                <div class="form-check p-2 border rounded-2 bg-light d-flex align-items-start gap-2 h-100">
                                                    <input class="form-check-input mt-1 ms-1" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm<?= $p['id'] ?>" <?= $isAssigned ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="perm<?= $p['id'] ?>">
                                                        <div class="fw-bold text-dark" style="font-size: 0.8rem;"><?= e($p['name']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.72rem;"><?= e($p['description']) ?></div>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="card-footer bg-light d-flex justify-content-end p-3 border-top">
                            <button type="submit" class="btn btn-primary px-4" style="background: var(--prayas-navy); border-color: var(--prayas-navy);">
                                <i class="bi bi-check-lg me-1"></i> Save Permission Matrix
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

