<?php
/**
 * User Management Controller
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';

class UserController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Get Paginated Users with Filters
     */
    public function getUsers(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(u.full_name LIKE :search1 OR u.email LIKE :search2 OR u.phone LIKE :search3)";
            $searchVal = '%' . trim($filters['search']) . '%';
            $params[':search1'] = $searchVal;
            $params[':search2'] = $searchVal;
            $params[':search3'] = $searchVal;
        }

        if (!empty($filters['role'])) {
            $conditions[] = "r.name = :role";
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "u.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['company_id'])) {
            $conditions[] = "u.company_id = :cid";
            $params[':cid'] = (int)$filters['company_id'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            LEFT JOIN `companies` c ON u.company_id = c.id 
            $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT u.id, u.role_id, u.company_id, u.full_name, u.email, u.phone, 
                u.status, u.must_change_password, u.last_login_at, u.created_at,
                r.name AS role_name, r.label AS role_label,
                c.company_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            LEFT JOIN `companies` c ON u.company_id = c.id
            $whereClause
            ORDER BY u.id DESC
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();

        return [
            'data'        => $users,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Single User By ID
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.role_id, u.company_id, u.full_name, u.email, u.phone, 
                u.status, u.must_change_password, u.last_login_at, u.created_at, u.updated_at,
                r.name AS role_name, r.label AS role_label,
                c.company_name, c.verification_status AS company_status
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            LEFT JOIN `companies` c ON u.company_id = c.id
            WHERE u.id = :id
            LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Save / Update User
     */
    public function saveUser(array $data, ?int $userId = null): array
    {
        $name      = trim((string)($data['full_name'] ?? ''));
        $email     = trim(filter_var((string)($data['email'] ?? ''), FILTER_SANITIZE_EMAIL));
        $phone     = trim((string)($data['phone'] ?? ''));
        $roleId    = (int)($data['role_id'] ?? 0);
        $companyId = !empty($data['company_id']) ? (int)$data['company_id'] : null;
        $status    = (string)($data['status'] ?? 'Active');
        $password  = (string)($data['password'] ?? '');

        if (strlen($name) < 2) return ['success' => false, 'message' => 'Full name is required.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid email address is required.'];
        if ($roleId <= 0) return ['success' => false, 'message' => 'Please select an assigned role.'];

        // Role verification
        $roleStmt = $this->pdo->prepare("SELECT name FROM `roles` WHERE id = :id");
        $roleStmt->execute([':id' => $roleId]);
        $roleName = $roleStmt->fetchColumn();
        if (!$roleName) return ['success' => false, 'message' => 'Invalid role selected.'];

        // If industry_admin, company association is mandatory
        if ($roleName === 'industry_admin' && empty($companyId)) {
            return ['success' => false, 'message' => 'Industry Admin users must be linked to a company.'];
        }

        // Email uniqueness
        $checkSql = "SELECT id FROM `users` WHERE `email` = :email" . ($userId ? " AND id != :id" : "") . " LIMIT 1";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkParams = [':email' => $email];
        if ($userId) $checkParams[':id'] = $userId;
        $checkStmt->execute($checkParams);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'A user account with this email address already exists.'];
        }

        if ($userId) {
            // Update
            $sql = "UPDATE `users` SET 
                    `role_id` = :role_id, `company_id` = :company_id, `full_name` = :name, 
                    `email` = :email, `phone` = :phone, `status` = :status, `updated_at` = NOW()";
            
            $params = [
                ':role_id'    => $roleId,
                ':company_id' => $companyId,
                ':name'       => $name,
                ':email'      => $email,
                ':phone'      => $phone,
                ':status'     => $status,
                ':id'         => $userId
            ];

            if (!empty($password)) {
                if (strlen($password) < 8) return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
                $sql .= ", `password_hash` = :hash";
                $params[':hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $sql .= " WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->logAudit('USER_UPDATED', getUserId(), $companyId, ['target_user_id' => $userId, 'name' => $name, 'role' => $roleName]);
            return ['success' => true, 'message' => 'User account updated successfully.'];
        } else {
            // Create
            if (empty($password) || strlen($password) < 8) {
                return ['success' => false, 'message' => 'Initial password (min. 8 characters) is required for new accounts.'];
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql = "INSERT INTO `users` 
                    (`role_id`, `company_id`, `full_name`, `email`, `phone`, `password_hash`, `must_change_password`, `status`) 
                    VALUES 
                    (:role_id, :company_id, :name, :email, :phone, :hash, 1, :status)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':role_id'    => $roleId,
                ':company_id' => $companyId,
                ':name'       => $name,
                ':email'      => $email,
                ':phone'      => $phone,
                ':hash'       => $passwordHash,
                ':status'     => $status
            ]);
            $newId = (int)$this->pdo->lastInsertId();

            $this->logAudit('USER_CREATED_BY_ADMIN', getUserId(), $companyId, ['target_user_id' => $newId, 'name' => $name, 'role' => $roleName]);
            return ['success' => true, 'message' => 'New user account created successfully with password change enforced.', 'id' => $newId];
        }
    }

    /**
     * Activate or Suspend User Account
     */
    public function setUserStatus(int $userId, string $status): array
    {
        $validStatuses = ['Active', 'Inactive', 'Suspended'];
        if (!in_array($status, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        // Prevent self-lockout
        if ($userId === getUserId()) {
            return ['success' => false, 'message' => 'You cannot deactivate or suspend your own active administrator account.'];
        }

        $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':status' => $status, ':id' => $userId]);

        $this->logAudit('USER_STATUS_CHANGED', getUserId(), null, ['target_user_id' => $userId, 'new_status' => $status]);
        return ['success' => true, 'message' => "User account status set to '{$status}'."];
    }

    /**
     * Admin Direct Password Reset
     */
    public function resetPassword(int $userId, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->pdo->prepare("UPDATE `users` SET `password_hash` = :hash, `must_change_password` = 1, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':hash' => $hash, ':id' => $userId]);

        $this->logAudit('USER_PASSWORD_RESET_BY_ADMIN', getUserId(), null, ['target_user_id' => $userId]);
        return ['success' => true, 'message' => 'Password has been reset. User will be forced to change password on next login.'];
    }

    /**
     * List Available Roles
     */
    public function getRoles(): array
    {
        return $this->pdo->query("SELECT id, name, label, description FROM `roles` ORDER BY id ASC")->fetchAll();
    }

    /**
     * List Verified Companies for Dropdowns
     */
    public function getCompaniesList(): array
    {
        return $this->pdo->query("SELECT id, company_name, district, verification_status FROM `companies` ORDER BY company_name ASC")->fetchAll();
    }

    /**
     * User Activity History from Audit Logs
     */
    public function getUserActivity(int $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("SELECT id, action, ip_address, details_json, created_at 
            FROM `audit_logs` 
            WHERE `user_id` = :uid 
            ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function logAudit(string $action, ?int $userId, ?int $companyId, array $details = []): void
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `company_id`, `action`, `ip_address`, `details_json`) 
                VALUES (:uid, :cid, :act, :ip, :details)");
            $stmt->execute([
                ':uid'     => $userId,
                ':cid'     => $companyId,
                ':act'     => $action,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':details' => json_encode($details)
            ]);
        } catch (Exception $e) {
            // Silently continue
        }
    }
}
