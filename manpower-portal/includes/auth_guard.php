<?php
/**
 * Authentication & Route Protection Barrier
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/database.php';

function requireAuth(): void
{
    if (!isAuthenticated()) {
        $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header("Location: " . app_url('/views/auth/login.php?error=' . urlencode("Authentication required. Please sign in.") . "&return_url={$returnUrl}"));
        exit;
    }

    // Force password change if flagged (excluding change password and logout endpoints)
    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($_SESSION['must_change_password']) && !in_array($currentScript, ['change_password.php', 'logout.php'])) {
        header("Location: " . app_url('/views/auth/change_password.php?warning=' . urlencode("For your security, you must update your initial password before proceeding.")));
        exit;
    }
}

function requireRole(string|array $roles): void
{
    requireAuth();

    if (!hasRole($roles)) {
        renderForbiddenResponse("Access Denied: Your account role does not have authorization to view this resource.");
    }
}

function requirePermission(string $permissionName): void
{
    requireAuth();

    if (!hasPermission($permissionName)) {
        renderForbiddenResponse("Access Denied: Missing required permission [{$permissionName}].");
    }
}

/**
 * Multi-Tenant Data Isolation Guard
 * Strictly prevents IDOR and cross-company unauthorized data access
 */
function requireCompanyAccess(int|string|null $targetCompanyId): void
{
    requireAuth();

    // Super Admin has universal cross-company platform governance
    if (hasRole('super_admin')) {
        return;
    }

    // Recruitment admins / recruiters have cross-industry recruitment access
    if (hasRole(['recruitment_admin', 'recruiter'])) {
        return;
    }

    $authCompanyId = getCompanyId();
    $targetId = ($targetCompanyId !== null && $targetCompanyId !== '') ? (int)$targetCompanyId : null;

    // For industry roles (industry_admin, industry_user), target company must strictly match session
    if ($targetId === null || $authCompanyId === null || $targetId !== $authCompanyId) {
        // Log isolation violation attempt
        require_once __DIR__ . '/../config/database.php';
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `company_id`, `action`, `ip_address`, `details_json`) 
                VALUES (:uid, :cid, 'TENANT_ISOLATION_VIOLATION', :ip, :details)");
            $stmt->execute([
                ':uid'     => getUserId(),
                ':cid'     => $authCompanyId,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                ':details' => json_encode([
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'attempted_target_company' => $targetId,
                    'authenticated_company'    => $authCompanyId
                ])
            ]);
        } catch (Exception $e) {
            // Silently continue to forbidden display
        }

        renderForbiddenResponse("Tenant Data Isolation Violation: Unauthorized attempt to access cross-company resources.");
    }
}

function requireCompanyVerified(): void
{
    requireAuth();

    if (hasRole(['industry_admin', 'industry_user'])) {
        $cid = getCompanyId();
        if (!$cid) {
            renderForbiddenResponse("Account configuration defect: No industrial company affiliation found.");
        }

        require_once __DIR__ . '/../config/database.php';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT `verification_status` FROM `companies` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $cid]);
        $status = $stmt->fetchColumn();

        if ($status !== 'Verified') {
            header("Location: " . app_url('/views/industry/company_profile.php?warning=' . urlencode("Company verification pending. Manpower requirements can only be submitted after verification by PRAYAS administration.")));
            exit;
        }
    }
}

function renderForbiddenResponse(string $reason): void
{
    http_response_code(403);

    // Audit log security violation
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `company_id`, `action`, `ip_address`, `details_json`) 
            VALUES (:uid, :cid, 'ROUTE_ACCESS_FORBIDDEN', :ip, :details)");
        $stmt->execute([
            ':uid'     => getUserId(),
            ':cid'     => getCompanyId(),
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ':details' => json_encode(['uri' => $_SERVER['REQUEST_URI'] ?? '', 'reason' => $reason])
        ]);
    } catch (Exception $e) {
        // Silently continue to forbidden display
    }

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403 Forbidden - Access Denied</title>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    echo '<style>body{background:#0F2042;color:#fff;}.card{border-radius:12px;background:#ffffff;color:#1e293b;}</style>';
    echo '</head><body class="d-flex align-items-center min-vh-100">';
    echo '<div class="container text-center"><div class="card p-5 shadow-lg mx-auto" style="max-width:550px;">';
    echo '<div class="text-danger mb-3"><svg width="64" height="64" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 13A6 6 0 1 1 8 2a6 6 0 0 1 0 12z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg></div>';
    echo '<h3 class="fw-bold mb-2 text-danger">403 - Access Forbidden</h3>';
    echo '<p class="text-secondary mb-4">' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<div class="d-flex justify-content-center gap-2">';
    echo '<a href="javascript:history.back()" class="btn btn-outline-secondary px-4">Go Back</a>';
    echo '<a href="' . app_url('/index.php') . '" class="btn btn-primary px-4" style="background:#0F2042;border-color:#0F2042;">Portal Home</a>';
    echo '</div></div></div></body></html>';
    exit;
}
