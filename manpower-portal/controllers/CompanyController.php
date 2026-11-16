<?php
/**
 * Company Management Controller
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/notification_service.php';

class CompanyController
{
    private PDO $pdo;

    public const DEFAULT_LIMITS = [
        'max_users'        => 5,
        'max_requirements' => 10,
        'max_plants'       => 5,
        'max_vacancies'    => 250
    ];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Get Paginated Companies with Search, Filters, and Sorting
     */
    public function getCompanies(array $filters = [], int $page = 1, int $perPage = 15, string $sort = 'id', string $dir = 'DESC'): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(c.company_name LIKE :search1 OR c.registration_number LIKE :search2 OR c.hr_contact_name LIKE :search3 OR c.hr_contact_email LIKE :search4 OR CONCAT('CMP-', LPAD(c.id, 4, '0')) LIKE :search5)";
            $searchVal = '%' . trim((string)$filters['search']) . '%';
            $params[':search1'] = $searchVal;
            $params[':search2'] = $searchVal;
            $params[':search3'] = $searchVal;
            $params[':search4'] = $searchVal;
            $params[':search5'] = $searchVal;
        }

        if (!empty($filters['status'])) {
            $conditions[] = "c.verification_status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['industry_type'])) {
            $conditions[] = "c.industry_type = :type";
            $params[':type'] = $filters['industry_type'];
        }

        if (!empty($filters['district'])) {
            $conditions[] = "c.district = :district";
            $params[':district'] = $filters['district'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(c.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(c.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `companies` c $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Safe sorting whitelist
        $sortColumn = match ($sort) {
            'name'   => 'c.company_name',
            'status' => 'c.verification_status',
            'date'   => 'c.created_at',
            default  => 'c.id'
        };
        $sortDirection = (strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC';

        // Pagination offset
        $offset = max(0, ($page - 1) * $perPage);

        // Fetch records with subqueries for admin and activity
        $sql = "SELECT c.*, u.full_name AS verified_by_name,
                CONCAT('CMP-', LPAD(c.id, 4, '0')) AS company_code,
                (SELECT COUNT(*) FROM `manpower_requirements` mr WHERE mr.company_id = c.id) AS total_requirements,
                (SELECT COUNT(*) FROM `users` usr WHERE usr.company_id = c.id) AS total_users,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id AND mp.status = 'Active') AS total_plants,
                (SELECT u2.full_name FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_name,
                (SELECT u2.email FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_email,
                (SELECT u2.phone FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_phone,
                (SELECT u2.status FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_status
            FROM `companies` c
            LEFT JOIN `users` u ON c.verified_by = u.id
            $whereClause
            ORDER BY {$sortColumn} {$sortDirection}
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $companies = $stmt->fetchAll();

        return [
            'data'        => $companies,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Single Company By ID with Detailed Metrics
     */
    public function getCompanyById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT c.*, u.full_name AS verified_by_name,
                CONCAT('CMP-', LPAD(c.id, 4, '0')) AS company_code,
                (SELECT COUNT(*) FROM `manpower_requirements` mr WHERE mr.company_id = c.id) AS total_requirements,
                (SELECT COUNT(*) FROM `users` usr WHERE usr.company_id = c.id) AS total_users,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id AND mp.status = 'Active') AS total_plants,
                (SELECT u2.full_name FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_name,
                (SELECT u2.email FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_email,
                (SELECT u2.phone FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_phone,
                (SELECT u2.status FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_status,
                (SELECT u2.last_login_at FROM `users` u2 JOIN `roles` r2 ON u2.role_id = r2.id WHERE u2.company_id = c.id AND r2.name = 'industry_admin' LIMIT 1) AS assigned_admin_last_login
            FROM `companies` c
            LEFT JOIN `users` u ON c.verified_by = u.id
            WHERE c.id = :id
            LIMIT 1");
        $stmt->execute([':id' => $id]);
        $company = $stmt->fetch();
        return $company ?: null;
    }

    /**
     * Update Company Verification Status with Remarks
     */
    public function updateVerificationStatus(int $companyId, string $status, ?string $rejectionReason = null, ?string $approvalRemarks = null): array
    {
        $validStatuses = ['Pending', 'Under Review', 'Verified', 'Active', 'Rejected', 'Suspended'];
        if (!in_array($status, $validStatuses, true)) {
            return ['success' => false, 'message' => "Invalid status: {$status}"];
        }

        if ($status === 'Rejected' && (empty($rejectionReason) || strlen(trim($rejectionReason)) < 10)) {
            return ['success' => false, 'message' => 'A detailed rejection reason (min 10 characters) is mandatory.'];
        }

        $company = $this->getCompanyById($companyId);
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found.'];
        }

        $oldStatus = $company['verification_status'];
        $verifierId = getUserId();

        $sql = "UPDATE `companies` SET 
                `verification_status` = :status,
                `verified_by` = :verified_by,
                `verified_at` = NOW(),
                `rejection_reason` = :reason,
                `updated_at` = NOW()
            WHERE `id` = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status'      => $status,
            ':verified_by' => $verifierId,
            ':reason'      => ($status === 'Rejected') ? trim((string)$rejectionReason) : null,
            ':id'          => $companyId
        ]);

        // Dispatch Notifications & Email via Centralized Service
        $notifService = new NotificationService($this->pdo);
        $notifService->dispatchCompanyStatusChanged($companyId, $status, $rejectionReason, $verifierId);

        // Audit Log
        $this->logAudit('COMPANY_STATUS_CHANGED', $verifierId, $companyId, [
            'company_name'     => $company['company_name'],
            'old_status'       => $oldStatus,
            'new_status'       => $status,
            'rejection_reason' => $rejectionReason,
            'approval_remarks' => $approvalRemarks
        ]);

        return ['success' => true, 'message' => "Company '{$company['company_name']}' status updated to '{$status}'."];
    }

    /**
     * Save / Update Company Record
     */
    public function saveCompany(array $data, ?int $companyId = null): array
    {
        $name     = trim((string)($data['company_name'] ?? ''));
        $regNo    = trim((string)($data['registration_number'] ?? ''));
        $type     = trim((string)($data['industry_type'] ?? ''));
        $addr     = trim((string)($data['address'] ?? ''));
        $state    = trim((string)($data['state'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));
        $city     = trim((string)($data['city'] ?? ''));
        $pincode  = trim((string)($data['pincode'] ?? ''));
        $website  = trim((string)($data['website'] ?? ''));
        $desc     = trim((string)($data['description'] ?? ''));
        $hrName   = trim((string)($data['hr_contact_name'] ?? ''));
        $hrPhone  = trim((string)($data['hr_contact_phone'] ?? ''));
        $hrEmail  = trim((string)($data['hr_contact_email'] ?? ''));
        $status   = (string)($data['verification_status'] ?? 'Pending');

        if (strlen($name) < 3) return ['success' => false, 'message' => 'Company name must be at least 3 characters.'];
        if (empty($type)) return ['success' => false, 'message' => 'Industry type is required.'];
        if (empty($addr)) return ['success' => false, 'message' => 'Address is required.'];
        if (empty($district)) return ['success' => false, 'message' => 'District is required.'];
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) return ['success' => false, 'message' => 'Valid 6-digit pincode is required.'];
        if (!filter_var($hrEmail, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid HR contact email is required.'];

        // Email uniqueness check
        $checkSql = "SELECT id FROM `companies` WHERE `hr_contact_email` = :email" . ($companyId ? " AND id != :id" : "") . " LIMIT 1";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkParams = [':email' => $hrEmail];
        if ($companyId) $checkParams[':id'] = $companyId;
        $checkStmt->execute($checkParams);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'A company with this HR contact email already exists.'];
        }

        if ($companyId) {
            // Update
            $sql = "UPDATE `companies` SET 
                    `company_name` = :name, `registration_number` = :reg, `industry_type` = :type,
                    `address` = :addr, `state` = :state, `district` = :district, `city` = :city,
                    `pincode` = :pin, `website` = :website, `description` = :desc,
                    `hr_contact_name` = :hr_name, `hr_contact_phone` = :hr_phone,
                    `hr_contact_email` = :hr_email, `verification_status` = :status, `updated_at` = NOW()
                WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':reg'      => !empty($regNo) ? $regNo : null,
                ':type'     => $type,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':website'  => !empty($website) ? $website : null,
                ':desc'     => !empty($desc) ? $desc : null,
                ':hr_name'  => $hrName,
                ':hr_phone' => $hrPhone,
                ':hr_email' => $hrEmail,
                ':status'   => $status,
                ':id'       => $companyId
            ]);
            $this->logAudit('COMPANY_UPDATED', getUserId(), $companyId, ['company_name' => $name]);
            return ['success' => true, 'message' => 'Company profile updated successfully.', 'id' => $companyId];
        } else {
            // Create
            $sql = "INSERT INTO `companies` 
                    (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `website`, `description`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
                    VALUES 
                    (:name, :reg, :type, :addr, :state, :district, :city, :pin, :website, :desc, :hr_name, :hr_phone, :hr_email, :status)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':reg'      => !empty($regNo) ? $regNo : null,
                ':type'     => $type,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':website'  => !empty($website) ? $website : null,
                ':desc'     => !empty($desc) ? $desc : null,
                ':hr_name'  => $hrName,
                ':hr_phone' => $hrPhone,
                ':hr_email' => $hrEmail,
                ':status'   => $status
            ]);
            $newId = (int)$this->pdo->lastInsertId();
            $this->logAudit('COMPANY_CREATED_BY_ADMIN', getUserId(), $newId, ['company_name' => $name]);
            return ['success' => true, 'message' => 'Company created successfully.', 'id' => $newId];
        }
    }

    /**
     * Get Requirements Submitted by Company
     */
    public function getCompanyRequirements(int $companyId): array
    {
        $stmt = $this->pdo->prepare("SELECT mr.*, 
                (SELECT GROUP_CONCAT(trade_name SEPARATOR ', ') FROM `requirement_trades` rt WHERE rt.requirement_id = mr.id) AS trades,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id) AS total_matched,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id AND cm.status = 'Joined') AS total_joined
            FROM `manpower_requirements` mr
            WHERE mr.company_id = :cid
            ORDER BY mr.id DESC");
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // COMPANY LIMIT MANAGEMENT SUBSYSTEM (SECTION 5)
    // =========================================================================

    /**
     * Retrieve Company Limits, Real-Time Usage & Remaining Capacity
     */
    public function getCompanyLimits(int $companyId): array
    {
        // 1. Fetch configured limits from system_settings or apply defaults
        $settingKey = 'company_limits_' . $companyId;
        $stmt = $this->pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = :key LIMIT 1");
        $stmt->execute([':key' => $settingKey]);
        $val = $stmt->fetchColumn();

        $limits = self::DEFAULT_LIMITS;
        if ($val) {
            $parsed = json_decode((string)$val, true);
            if (is_array($parsed)) {
                $limits = array_merge(self::DEFAULT_LIMITS, $parsed);
            }
        }

        // 2. Fetch real-time usage for this specific company
        // Users usage
        $userStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `company_id` = :cid AND `status` != 'Inactive'");
        $userStmt->execute([':cid' => $companyId]);
        $currentUsers = (int)$userStmt->fetchColumn();

        // Requirements usage (active & submitted)
        $reqStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manpower_requirements` WHERE `company_id` = :cid AND `status` NOT IN ('CANCELLED', 'DRAFT')");
        $reqStmt->execute([':cid' => $companyId]);
        $currentRequirements = (int)$reqStmt->fetchColumn();

        // Active Manufacturing Plants
        $plantStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manufacturing_plants` WHERE `company_id` = :cid AND `status` = 'Active'");
        $plantStmt->execute([':cid' => $companyId]);
        $currentPlants = (int)$plantStmt->fetchColumn();

        // Total Headcount / Vacancies required
        $vacStmt = $this->pdo->prepare("SELECT COALESCE(SUM(quantity_required), 0) FROM `manpower_requirements` WHERE `company_id` = :cid AND `status` NOT IN ('CANCELLED', 'DRAFT')");
        $vacStmt->execute([':cid' => $companyId]);
        $currentVacancies = (int)$vacStmt->fetchColumn();

        $maxUsers        = max(1, (int)$limits['max_users']);
        $maxRequirements = max(1, (int)$limits['max_requirements']);
        $maxPlants       = max(1, (int)$limits['max_plants']);
        $maxVacancies    = max(1, (int)$limits['max_vacancies']);

        return [
            'company_id' => $companyId,
            'limits' => [
                'max_users'        => $maxUsers,
                'max_requirements' => $maxRequirements,
                'max_plants'       => $maxPlants,
                'max_vacancies'    => $maxVacancies
            ],
            'usage' => [
                'users'        => $currentUsers,
                'requirements' => $currentRequirements,
                'plants'       => $currentPlants,
                'vacancies'    => $currentVacancies
            ],
            'remaining' => [
                'users'        => max(0, $maxUsers - $currentUsers),
                'requirements' => max(0, $maxRequirements - $currentRequirements),
                'plants'       => max(0, $maxPlants - $currentPlants),
                'vacancies'    => max(0, $maxVacancies - $currentVacancies)
            ],
            'percentages' => [
                'users'        => min(100, (int)round(($currentUsers / $maxUsers) * 100)),
                'requirements' => min(100, (int)round(($currentRequirements / $maxRequirements) * 100)),
                'plants'       => min(100, (int)round(($currentPlants / $maxPlants) * 100)),
                'vacancies'    => min(100, (int)round(($currentVacancies / $maxVacancies) * 100))
            ]
        ];
    }

    /**
     * Configure Company Limits (Super Admin Only)
     */
    public function setCompanyLimits(int $companyId, array $limits): array
    {
        $company = $this->getCompanyById($companyId);
        if (!$company) {
            return ['success' => false, 'message' => 'Company record not found.'];
        }

        $maxUsers     = max(1, (int)($limits['max_users'] ?? self::DEFAULT_LIMITS['max_users']));
        $maxReqs      = max(1, (int)($limits['max_requirements'] ?? self::DEFAULT_LIMITS['max_requirements']));
        $maxPlants    = max(1, (int)($limits['max_plants'] ?? self::DEFAULT_LIMITS['max_plants']));
        $maxVacancies = max(1, (int)($limits['max_vacancies'] ?? self::DEFAULT_LIMITS['max_vacancies']));

        $payload = [
            'max_users'        => $maxUsers,
            'max_requirements' => $maxReqs,
            'max_plants'       => $maxPlants,
            'max_vacancies'    => $maxVacancies
        ];

        $oldLimits = $this->getCompanyLimits($companyId)['limits'];

        $settingKey = 'company_limits_' . $companyId;
        $json = json_encode($payload);

        $stmt = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) 
            VALUES (:key, :val, :desc, NOW()) 
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()");
        
        $desc = "Allocated feature limits for company #{$companyId} ({$company['company_name']})";
        $stmt->execute([
            ':key'  => $settingKey,
            ':val'  => $json,
            ':desc' => $desc
        ]);

        // Audit Log
        $this->logAudit('COMPANY_LIMITS_CHANGED', getUserId(), $companyId, [
            'company_name' => $company['company_name'],
            'old_limits'   => $oldLimits,
            'new_limits'   => $payload
        ]);

        // Notify Company Admins
        try {
            $notifService = new NotificationService($this->pdo);
            $notifMsg = "Your platform capacity limits have been updated by PRAYAS administration. Maximum vacancies: {$maxVacancies}, Requisitions: {$maxReqs}, Team Members: {$maxUsers}, Manufacturing Plants: {$maxPlants}.";
            $notifService->notifyCompanyAdmins(
                $companyId,
                'Company Limits Updated',
                $notifMsg,
                '/views/industry/company_profile.php'
            );
        } catch (Exception $e) {
            // Silently continue if notification fails
        }

        return [
            'success' => true,
            'message' => "Capacity limits for '{$company['company_name']}' updated successfully.",
            'limits'  => $payload
        ];
    }

    /**
     * Backend Limit Gate Checker
     * Returns true if operation is permitted within assigned capacity, false otherwise.
     */
    public function checkCompanyLimit(int $companyId, string $limitType, int $increment = 1): array
    {
        $data = $this->getCompanyLimits($companyId);
        $limits = $data['limits'];
        $usage = $data['usage'];

        $limitMap = [
            'max_users'        => ['usage_key' => 'users', 'label' => 'Team Members / Users'],
            'max_requirements' => ['usage_key' => 'requirements', 'label' => 'Manpower Requisitions'],
            'max_plants'       => ['usage_key' => 'plants', 'label' => 'Manufacturing Plants'],
            'max_vacancies'    => ['usage_key' => 'vacancies', 'label' => 'Total Vacancy Headcount']
        ];

        if (!isset($limitMap[$limitType])) {
            return ['allowed' => true, 'message' => 'Unrestricted'];
        }

        $meta = $limitMap[$limitType];
        $current = $usage[$meta['usage_key']];
        $limit = $limits[$limitType];
        $projected = $current + $increment;

        if ($projected > $limit) {
            $rem = max(0, $limit - $current);
            return [
                'allowed'   => false,
                'message'   => "Capacity Limit Reached: Your company has reached the maximum allocated limit of {$meta['label']} ({$current}/{$limit}). Remaining capacity: {$rem}. Please contact PRAYAS administration to upgrade your account limits.",
                'current'   => $current,
                'limit'     => $limit,
                'remaining' => $rem
            ];
        }

        return [
            'allowed'   => true,
            'message'   => 'Within allocated capacity limit.',
            'current'   => $current,
            'limit'     => $limit,
            'remaining' => max(0, $limit - $projected)
        ];
    }

    /**
     * Retrieve All Companies with Their Limits (For Company Limits Console)
     */
    public function getAllCompaniesWithLimits(): array
    {
        $companies = $this->pdo->query("SELECT id, company_name, industry_type, verification_status, created_at FROM `companies` ORDER BY id DESC")->fetchAll();
        $result = [];
        foreach ($companies as $c) {
            $limitsData = $this->getCompanyLimits((int)$c['id']);
            $c['limits_data'] = $limitsData;
            $c['company_code'] = 'CMP-' . str_pad((string)$c['id'], 4, '0', STR_PAD_LEFT);
            $result[] = $c;
        }
        return $result;
    }

    /**
     * Fetch Company-Specific Audit History
     */
    public function getCompanyAuditHistory(int $companyId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare("SELECT a.id, a.action, a.ip_address, a.details_json, a.created_at, u.full_name AS actor_name
            FROM `audit_logs` a
            LEFT JOIN `users` u ON a.user_id = u.id
            WHERE a.company_id = :cid
            ORDER BY a.id DESC LIMIT :limit");
        $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Fetch All Users / Administrators for a Company
     */
    public function getCompanyAdmins(int $companyId): array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.full_name, u.email, u.phone, u.status, u.last_login_at, u.created_at, r.name AS role_name, r.label AS role_label
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.company_id = :cid
            ORDER BY u.id ASC");
        $stmt->execute([':cid' => $companyId]);
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
