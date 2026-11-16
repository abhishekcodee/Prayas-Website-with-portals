<?php
/**
 * Centralized Role-Based Access Control (RBAC) & Permission Engine
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function isAuthenticated(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role_name']);
}

function getAuthUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    return [
        'id'                   => (int)$_SESSION['user_id'],
        'role_id'              => (int)($_SESSION['role_id'] ?? 0),
        'role_name'            => (string)($_SESSION['role_name'] ?? ''),
        'role_label'           => (string)($_SESSION['role_label'] ?? ''),
        'company_id'           => isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null,
        'company_name'         => (string)($_SESSION['company_name'] ?? ''),
        'full_name'            => (string)($_SESSION['full_name'] ?? ''),
        'email'                => (string)($_SESSION['email'] ?? ''),
        'must_change_password' => !empty($_SESSION['must_change_password'])
    ];
}

function getUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Retrieve Authenticated Company ID
 * Strict Multi-Tenant Isolation: NEVER trust client input for company association.
 */
function getCompanyId(): ?int
{
    return isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
}

function getRoleName(): ?string
{
    return isset($_SESSION['role_name']) ? (string)$_SESSION['role_name'] : null;
}

function hasRole(string|array $roles): bool
{
    if (!isAuthenticated()) {
        return false;
    }

    $currentRole = getRoleName();
    if ($currentRole === null) {
        return false;
    }

    if (is_string($roles)) {
        return strtolower($currentRole) === strtolower($roles);
    }

    $normalized = array_map('strtolower', $roles);
    return in_array(strtolower($currentRole), $normalized, true);
}

function hasPermission(string $permissionName): bool
{
    if (!isAuthenticated()) {
        return false;
    }

    // Super Admin has universal capability override
    if (hasRole('super_admin')) {
        return true;
    }

    $userPerms = $_SESSION['permissions'] ?? [];
    return in_array($permissionName, $userPerms, true);
}

/**
 * Fetch permissions list from database for a specific role
 */
function loadRolePermissions(int $roleId, PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT p.name 
        FROM `permissions` p 
        JOIN `role_permissions` rp ON p.id = rp.permission_id 
        WHERE rp.role_id = :role_id");
    $stmt->execute([':role_id' => $roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Global HTML Output Sanitization Helper
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

