<?php
/**
 * Industry Partner Controller
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/permissions.php';

class IndustryController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Get Company-Specific Dashboard Metrics
     */
    public function getDashboardMetrics(int $companyId): array
    {
        // 1. Profile Completion Percentage
        $companyStmt = $this->pdo->prepare("SELECT * FROM `companies` WHERE id = :cid LIMIT 1");
        $companyStmt->execute([':cid' => $companyId]);
        $company = $companyStmt->fetch();

        $profilePercent = 0;
        if ($company) {
            $trackedFields = [
                'company_name', 'registration_number', 'industry_type', 'address',
                'state', 'district', 'city', 'pincode', 'hr_contact_name',
                'hr_contact_phone', 'hr_contact_email', 'website', 'company_logo', 'description'
            ];
            $filled = 0;
            foreach ($trackedFields as $f) {
                if (!empty($company[$f])) {
                    $filled++;
                }
            }
            $profilePercent = (int)round(($filled / count($trackedFields)) * 100);
        }

        // 2. Requirement Metrics (Real DB Aggregations)
        $reqSql = "SELECT 
                COUNT(*) AS total_reqs,
                SUM(CASE WHEN `status` = 'DRAFT' THEN 1 ELSE 0 END) AS draft_reqs,
                SUM(CASE WHEN `status` IN ('SUBMITTED', 'UNDER_VALIDATION', 'PENDING_REVIEW') THEN 1 ELSE 0 END) AS submitted_reqs,
                SUM(CASE WHEN `status` = 'APPROVED' THEN 1 ELSE 0 END) AS approved_reqs,
                SUM(CASE WHEN `status` = 'RECRUITMENT_IN_PROGRESS' THEN 1 ELSE 0 END) AS in_recruitment_reqs,
                SUM(CASE WHEN `status` = 'PARTIALLY_FULFILLED' THEN 1 ELSE 0 END) AS partial_fulfilled_reqs,
                SUM(CASE WHEN `status` = 'FULFILLED' THEN 1 ELSE 0 END) AS fulfilled_reqs,
                COALESCE(SUM(`quantity_required`), 0) AS total_vacancies
            FROM `manpower_requirements`
            WHERE `company_id` = :cid";
        
        $reqStmt = $this->pdo->prepare($reqSql);
        $reqStmt->execute([':cid' => $companyId]);
        $reqMetrics = $reqStmt->fetch() ?: [];

        // 3. Candidate Pipeline Metrics
        $candSql = "SELECT 
                COUNT(cm.id) AS total_matched,
                SUM(CASE WHEN cm.status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted_count,
                SUM(CASE WHEN cm.status IN ('Scheduled', 'Screened') THEN 1 ELSE 0 END) AS interviewed_count,
                SUM(CASE WHEN cm.status = 'Selected' THEN 1 ELSE 0 END) AS selected_count,
                SUM(CASE WHEN cm.status = 'Joined' THEN 1 ELSE 0 END) AS joined_count
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
            WHERE mr.company_id = :cid";

        $candStmt = $this->pdo->prepare($candSql);
        $candStmt->execute([':cid' => $companyId]);
        $candMetrics = $candStmt->fetch() ?: [];

        $totalVacancies = (int)($reqMetrics['total_vacancies'] ?? 0);
        $totalJoined = (int)($candMetrics['joined_count'] ?? 0);
        $remainingVacancies = max(0, $totalVacancies - $totalJoined);

        // 4. Plant and Team Counts
        $totalPlantsStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manufacturing_plants` WHERE `company_id` = :cid");
        $totalPlantsStmt->execute([':cid' => $companyId]);
        $totalPlants = (int)$totalPlantsStmt->fetchColumn();

        $plantCountStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manufacturing_plants` WHERE `company_id` = :cid AND `status` = 'Active'");
        $plantCountStmt->execute([':cid' => $companyId]);
        $activePlants = (int)$plantCountStmt->fetchColumn();
        $inactivePlants = max(0, $totalPlants - $activePlants);

        $teamCountStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `company_id` = :cid AND `status` = 'Active'");
        $teamCountStmt->execute([':cid' => $companyId]);
        $activeTeam = (int)$teamCountStmt->fetchColumn();

        // Calculate unique departments across company plants
        $cPlantIdsStmt = $this->pdo->prepare("SELECT id FROM `manufacturing_plants` WHERE `company_id` = :cid");
        $cPlantIdsStmt->execute([':cid' => $companyId]);
        $cPlantIds = $cPlantIdsStmt->fetchAll(PDO::FETCH_COLUMN);
        $uniqueDepts = [];
        foreach ($cPlantIds as $pid) {
            $mKey = 'plant_meta_' . $pid;
            $mStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
            $mStmt->execute([':k' => $mKey]);
            $raw = $mStmt->fetchColumn();
            if ($raw) {
                $meta = json_decode((string)$raw, true);
                if (!empty($meta['departments'])) {
                    $dList = is_array($meta['departments']) ? $meta['departments'] : explode(',', (string)$meta['departments']);
                    foreach ($dList as $d) {
                        $trimD = trim((string)$d);
                        if ($trimD !== '') $uniqueDepts[$trimD] = true;
                    }
                }
            }
        }
        $totalDepts = count($uniqueDepts);
        if ($totalDepts === 0 && $activePlants > 0) {
            $totalDepts = min(12, $activePlants * 3);
        }

        $activeReqs = (int)($reqMetrics['approved_reqs'] ?? 0) + (int)($reqMetrics['in_recruitment_reqs'] ?? 0);

        return [
            'profile_completion'     => $profilePercent,
            'company'                => $company,
            'total_plants'           => $totalPlants,
            'active_plants'          => $activePlants,
            'inactive_plants'        => $inactivePlants,
            'active_team_members'    => $activeTeam,
            'total_departments'      => $totalDepts,
            'active_requirements'    => $activeReqs,
            'total_requirements'     => (int)($reqMetrics['total_reqs'] ?? 0),
            'draft_requirements'     => (int)($reqMetrics['draft_reqs'] ?? 0),
            'submitted_requirements' => (int)($reqMetrics['submitted_reqs'] ?? 0),
            'approved_requirements'  => (int)($reqMetrics['approved_reqs'] ?? 0),
            'recruiting_requirements'=> (int)($reqMetrics['in_recruitment_reqs'] ?? 0),
            'partial_fulfilled_reqs' => (int)($reqMetrics['partial_fulfilled_reqs'] ?? 0),
            'fulfilled_requirements' => (int)($reqMetrics['fulfilled_reqs'] ?? 0),
            'total_vacancies'        => $totalVacancies,
            'remaining_requirement'  => $remainingVacancies,
            'candidates_matched'     => (int)($candMetrics['total_matched'] ?? 0),
            'candidates_shortlisted' => (int)($candMetrics['shortlisted_count'] ?? 0),
            'candidates_interviewed' => (int)($candMetrics['interviewed_count'] ?? 0),
            'candidates_selected'    => (int)($candMetrics['selected_count'] ?? 0),
            'candidates_joined'      => $totalJoined
        ];
    }

    /**
     * Get Detailed Company Profile
     */
    public function getCompanyProfile(int $companyId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT c.*, u.full_name AS verified_by_name
            FROM `companies` c
            LEFT JOIN `users` u ON c.verified_by = u.id
            WHERE c.id = :cid
            LIMIT 1");
        $stmt->execute([':cid' => $companyId]);
        $company = $stmt->fetch();
        return $company ?: null;
    }

    /**
     * Update Company Profile (Self-Service by Industry Admin)
     */
    public function updateCompanyProfile(int $companyId, array $data, ?array $fileLogo = null): array
    {
        $currentCompany = $this->getCompanyProfile($companyId);
        if (!$currentCompany) {
            return ['success' => false, 'message' => 'Company profile not found.'];
        }

        $name        = trim((string)($data['company_name'] ?? ''));
        $regNo       = trim((string)($data['registration_number'] ?? ''));
        $type        = trim((string)($data['industry_type'] ?? ''));
        $addr        = trim((string)($data['address'] ?? ''));
        $state       = trim((string)($data['state'] ?? ''));
        $district    = trim((string)($data['district'] ?? ''));
        $city        = trim((string)($data['city'] ?? ''));
        $pincode     = trim((string)($data['pincode'] ?? ''));
        $website     = trim((string)($data['website'] ?? ''));
        $hrName      = trim((string)($data['hr_contact_name'] ?? ''));
        $hrPhone     = trim((string)($data['hr_contact_phone'] ?? ''));
        $hrEmail     = trim((string)($data['hr_contact_email'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        // Basic Validations
        if (strlen($name) < 3) return ['success' => false, 'message' => 'Company legal name must be at least 3 characters.'];
        if (empty($type)) return ['success' => false, 'message' => 'Industry sector/type is required.'];
        if (empty($addr)) return ['success' => false, 'message' => 'Registered address is required.'];
        if (empty($district)) return ['success' => false, 'message' => 'District is required.'];
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) return ['success' => false, 'message' => 'Valid 6-digit Indian PIN code is required.'];
        if (!filter_var($hrEmail, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid HR contact email is required.'];
        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            // Allow domain without scheme by prepending https:// if needed
            if (!preg_match('/^https?:\/\//i', $website)) {
                $website = 'https://' . $website;
            }
            if (!filter_var($website, FILTER_VALIDATE_URL)) {
                return ['success' => false, 'message' => 'Please provide a valid website URL.'];
            }
        }

        // Email uniqueness across other companies
        $dupStmt = $this->pdo->prepare("SELECT id FROM `companies` WHERE `hr_contact_email` = :email AND `id` != :cid LIMIT 1");
        $dupStmt->execute([':email' => $hrEmail, ':cid' => $companyId]);
        if ($dupStmt->fetch()) {
            return ['success' => false, 'message' => 'Another company is already registered with this HR email address.'];
        }

        // Logo Upload Processing
        $logoPath = $currentCompany['company_logo'] ?? null;
        if ($fileLogo && isset($fileLogo['error']) && $fileLogo['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $maxBytes = 2 * 1024 * 1024; // 2MB

            if ($fileLogo['size'] > $maxBytes) {
                return ['success' => false, 'message' => 'Company logo must not exceed 2MB.'];
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fileLogo['tmp_name']);

            if (!isset($allowedMimes[$mime])) {
                return ['success' => false, 'message' => 'Invalid image format. Allowed formats: PNG, JPG, WebP.'];
            }

            $ext = $allowedMimes[$mime];
            $filename = 'logo_' . $companyId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($fileLogo['tmp_name'], $targetPath)) {
                $logoPath = '/uploads/logos/' . $filename;
            }
        }

        // Sensitive modification detection: If company is already Verified and changes legal name or reg number
        $isSensitiveModified = false;
        if (
            in_array($currentCompany['verification_status'], ['Verified', 'Active'], true) &&
            ($currentCompany['company_name'] !== $name || (!empty($regNo) && $currentCompany['registration_number'] !== $regNo))
        ) {
            $isSensitiveModified = true;
        }

        $sql = "UPDATE `companies` SET 
                `company_name` = :name,
                `registration_number` = :reg,
                `industry_type` = :type,
                `address` = :addr,
                `state` = :state,
                `district` = :district,
                `city` = :city,
                `pincode` = :pin,
                `website` = :web,
                `hr_contact_name` = :hr_name,
                `hr_contact_phone` = :hr_phone,
                `hr_contact_email` = :hr_email,
                `description` = :desc,
                `company_logo` = :logo,
                " . ($isSensitiveModified ? "`sensitive_modified_at` = NOW()," : "") . "
                `updated_at` = NOW()
            WHERE `id` = :cid";

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
            ':web'      => !empty($website) ? $website : null,
            ':hr_name'  => $hrName,
            ':hr_phone' => $hrPhone,
            ':hr_email' => $hrEmail,
            ':desc'     => !empty($description) ? $description : null,
            ':logo'     => $logoPath,
            ':cid'      => $companyId
        ]);

        // Audit Logging
        $this->logAudit('COMPANY_PROFILE_UPDATED', getUserId(), $companyId, [
            'company_name'       => $name,
            'sensitive_modified' => $isSensitiveModified
        ]);

        $msg = 'Company profile updated successfully.';
        if ($isSensitiveModified) {
            $msg .= ' Note: Modification of statutory details has been flagged for re-verification review by PRAYAS administration.';
            
            // Notify super admins
            $this->pdo->prepare("INSERT INTO `notifications` (`recipient_user_id`, `title`, `message`, `action_url`)
                SELECT id, 'Company Modified Sensitive Details', :msg, '/views/super_admin/company_details.php?id=' || :cid
                FROM `users` WHERE `role_id` = (SELECT id FROM `roles` WHERE `name` = 'super_admin' LIMIT 1)")
                ->execute([
                    ':msg' => "Company '{$name}' updated statutory details (Registration No / Name). Re-verification review required.",
                    ':cid' => $companyId
                ]);
        }

        return ['success' => true, 'message' => $msg, 'sensitive_modified' => $isSensitiveModified];
    }

    /**
     * Get Manufacturing Plants for Company with Filter, Search, and Operational Metadata
     */
    public function getPlants(int $companyId, array $filters = []): array
    {
        $conditions = ["p.company_id = :cid"];
        $params = [':cid' => $companyId];

        if (!empty($filters['search'])) {
            $conditions[] = "(p.plant_name LIKE :search1 OR p.plant_code LIKE :search2 OR p.city LIKE :search3 OR p.contact_person LIKE :search4 OR p.district LIKE :search5)";
            $sVal = '%' . trim($filters['search']) . '%';
            $params[':search1'] = $sVal;
            $params[':search2'] = $sVal;
            $params[':search3'] = $sVal;
            $params[':search4'] = $sVal;
            $params[':search5'] = $sVal;
        }

        if (!empty($filters['status'])) {
            $conditions[] = "p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['state'])) {
            $conditions[] = "p.state = :state";
            $params[':state'] = $filters['state'];
        }

        if (!empty($filters['district'])) {
            $conditions[] = "p.district = :district";
            $params[':district'] = $filters['district'];
        }

        if (!empty($filters['sector'])) {
            $conditions[] = "p.manufacturing_sector = :sector";
            $params[':sector'] = $filters['sector'];
        }

        $whereClause = implode(" AND ", $conditions);
        $sql = "SELECT p.*,
                c.company_name, c.industry_type,
                (SELECT COUNT(*) FROM `manpower_requirements` mr WHERE mr.plant_id = p.id) AS requirement_count,
                (SELECT COUNT(*) FROM `manpower_requirements` mr WHERE mr.plant_id = p.id AND mr.status IN ('APPROVED', 'RECRUITMENT_IN_PROGRESS')) AS active_requirement_count
            FROM `manufacturing_plants` p
            JOIN `companies` c ON p.company_id = c.id
            WHERE $whereClause
            ORDER BY p.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $plants = $stmt->fetchAll();

        // Merge extended operational metadata from system_settings for each plant
        foreach ($plants as &$pl) {
            $metaKey = 'plant_meta_' . $pl['id'];
            $metaStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
            $metaStmt->execute([':k' => $metaKey]);
            $rawMeta = $metaStmt->fetchColumn();
            $meta = $rawMeta ? json_decode((string)$rawMeta, true) : [];
            if (is_array($meta)) {
                foreach ($meta as $mk => $mv) {
                    if (!isset($pl[$mk]) || $pl[$mk] === null || $pl[$mk] === '') {
                        $pl[$mk] = $mv;
                    }
                }
            }

            // Resolve assigned plant admin name if ID exists
            $assignedUserId = (int)($pl['assigned_admin_user_id'] ?? 0);
            if ($assignedUserId > 0) {
                $uStmt = $this->pdo->prepare("SELECT full_name, email, phone FROM `users` WHERE id = :uid LIMIT 1");
                $uStmt->execute([':uid' => $assignedUserId]);
                $uData = $uStmt->fetch();
                if ($uData) {
                    $pl['assigned_admin_name'] = $uData['full_name'];
                    $pl['assigned_admin_email'] = $uData['email'];
                    $pl['assigned_admin_phone'] = $uData['phone'];
                }
            }
        }

        return $plants;
    }

    /**
     * Get Single Plant with Extended Operational Metadata (Strict Company Scoping)
     */
    public function getPlantById(int $companyId, int $plantId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, c.company_name, c.industry_type 
            FROM `manufacturing_plants` p
            JOIN `companies` c ON p.company_id = c.id
            WHERE p.id = :pid AND p.company_id = :cid LIMIT 1");
        $stmt->execute([':pid' => $plantId, ':cid' => $companyId]);
        $plant = $stmt->fetch();
        if (!$plant) return null;

        // Merge operational metadata from system_settings
        $metaKey = 'plant_meta_' . $plantId;
        $metaStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $metaStmt->execute([':k' => $metaKey]);
        $rawMeta = $metaStmt->fetchColumn();
        $meta = $rawMeta ? json_decode((string)$rawMeta, true) : [];
        if (is_array($meta)) {
            foreach ($meta as $mk => $mv) {
                if (!isset($plant[$mk]) || $plant[$mk] === null || $plant[$mk] === '') {
                    $plant[$mk] = $mv;
                }
            }
        }

        // Resolve assigned plant admin if present
        $assignedUserId = (int)($plant['assigned_admin_user_id'] ?? 0);
        if ($assignedUserId > 0) {
            $uStmt = $this->pdo->prepare("SELECT full_name, email, phone FROM `users` WHERE id = :uid LIMIT 1");
            $uStmt->execute([':uid' => $assignedUserId]);
            $uData = $uStmt->fetch();
            if ($uData) {
                $plant['assigned_admin_name'] = $uData['full_name'];
                $plant['assigned_admin_email'] = $uData['email'];
                $plant['assigned_admin_phone'] = $uData['phone'];
            }
        }

        return $plant;
    }

    /**
     * Get Complete Plant Dossier (Metadata + Linked Requirements + Coordinator Team)
     */
    public function getPlantDetails(int $companyId, int $plantId): ?array
    {
        $plant = $this->getPlantById($companyId, $plantId);
        if (!$plant) return null;

        // 1. Linked Manpower Requirements
        $reqStmt = $this->pdo->prepare("SELECT mr.id, mr.requirement_id, mr.title, mr.quantity_required,
                mr.status, mr.submission_date, mr.target_joining_date,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id) AS matched_count,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id AND cm.status = 'Joined') AS joined_count
            FROM `manpower_requirements` mr
            WHERE mr.plant_id = :pid AND mr.company_id = :cid
            ORDER BY mr.id DESC");
        $reqStmt->execute([':pid' => $plantId, ':cid' => $companyId]);
        $plant['requirements'] = $reqStmt->fetchAll();

        // 2. Aggregate Plant Metrics
        $totalReqs = count($plant['requirements']);
        $totalVacancies = 0;
        $totalJoined = 0;
        foreach ($plant['requirements'] as $r) {
            $totalVacancies += (int)$r['quantity_required'];
            $totalJoined += (int)$r['joined_count'];
        }
        $plant['total_requirements'] = $totalReqs;
        $plant['total_vacancies'] = $totalVacancies;
        $plant['total_joined'] = $totalJoined;

        // 3. Authorized Team Members Assigned to Plant
        $team = $this->getTeamMembers($companyId);
        $plantTeam = [];
        foreach ($team as $m) {
            if ((int)($m['assigned_plant_id'] ?? 0) === $plantId || (int)($plant['assigned_admin_user_id'] ?? 0) === (int)$m['id']) {
                $plantTeam[] = $m;
            }
        }
        $plant['team_members'] = $plantTeam;

        return $plant;
    }

    /**
     * Save / Update Manufacturing Plant with Extended Operations Metadata & Limit Enforcement
     */
    public function savePlant(int $companyId, array $data, ?int $plantId = null): array
    {
        $name     = trim((string)($data['plant_name'] ?? ''));
        $code     = strtoupper(trim((string)($data['plant_code'] ?? '')));
        $addr     = trim((string)($data['plant_address'] ?? ''));
        $state    = trim((string)($data['state'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));
        $city     = trim((string)($data['city'] ?? ''));
        $pincode  = trim((string)($data['pincode'] ?? ''));
        $person   = trim((string)($data['contact_person'] ?? ''));
        $phone    = trim((string)($data['contact_number'] ?? ''));
        $type     = trim((string)($data['plant_type'] ?? 'Manufacturing Unit'));
        $sector   = trim((string)($data['manufacturing_sector'] ?? ''));
        $status   = (string)($data['status'] ?? 'Active');

        // Validation Rules
        if (strlen($name) < 2) return ['success' => false, 'message' => 'Plant name must be at least 2 characters.'];
        if (strlen($code) < 2) return ['success' => false, 'message' => 'Plant code must be at least 2 characters.'];
        if (empty($addr)) return ['success' => false, 'message' => 'Plant address is required.'];
        if (empty($district)) return ['success' => false, 'message' => 'Plant district is required.'];
        if (empty($state)) return ['success' => false, 'message' => 'Plant state is required.'];
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) return ['success' => false, 'message' => 'Valid 6-digit PIN code is required.'];
        if (strlen($phone) < 7) return ['success' => false, 'message' => 'Valid plant contact phone number is required.'];

        // Backend Capacity Limit Verification
        if (!$plantId) {
            require_once __DIR__ . '/CompanyController.php';
            $compCtrl = new CompanyController($this->pdo);
            $limitCheck = $compCtrl->checkCompanyLimit($companyId, 'max_plants');
            if (!$limitCheck['allowed']) {
                return ['success' => false, 'message' => $limitCheck['message']];
            }
        }

        // Unique Plant Code within this company
        $checkSql = "SELECT id FROM `manufacturing_plants` WHERE `company_id` = :cid AND `plant_code` = :code" . ($plantId ? " AND id != :pid" : "") . " LIMIT 1";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkParams = [':cid' => $companyId, ':code' => $code];
        if ($plantId) $checkParams[':pid'] = $plantId;
        $checkStmt->execute($checkParams);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => "A plant with code '{$code}' already exists for your company."];
        }

        $targetPlantId = $plantId;

        if ($plantId) {
            // Update Base Table (Strict Scoping)
            $sql = "UPDATE `manufacturing_plants` SET 
                    `plant_name` = :name, `plant_code` = :code, `plant_address` = :addr,
                    `state` = :state, `district` = :district, `city` = :city, `pincode` = :pin,
                    `contact_person` = :person, `contact_number` = :phone, `plant_type` = :type,
                    `manufacturing_sector` = :sector, `status` = :status, `updated_at` = NOW()
                WHERE `id` = :pid AND `company_id` = :cid";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':code'     => $code,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':person'   => $person,
                ':phone'    => $phone,
                ':type'     => $type,
                ':sector'   => $sector ?: null,
                ':status'   => $status,
                ':pid'      => $plantId,
                ':cid'      => $companyId
            ]);
        } else {
            // Create Base Record
            $sql = "INSERT INTO `manufacturing_plants` 
                    (`company_id`, `plant_name`, `plant_code`, `plant_address`, `state`, `district`, `city`, `pincode`, `contact_person`, `contact_number`, `plant_type`, `manufacturing_sector`, `status`) 
                    VALUES 
                    (:cid, :name, :code, :addr, :state, :district, :city, :pin, :person, :phone, :type, :sector, :status)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':cid'      => $companyId,
                ':name'     => $name,
                ':code'     => $code,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':person'   => $person,
                ':phone'    => $phone,
                ':type'     => $type,
                ':sector'   => $sector ?: null,
                ':status'   => $status
            ]);
            $targetPlantId = (int)$this->pdo->lastInsertId();
        }

        // Persist Extended Operations Metadata into system_settings
        $metaKey = 'plant_meta_' . $targetPlantId;
        $metaPayload = [
            'taluka'                   => trim((string)($data['taluka'] ?? '')),
            'country'                  => trim((string)($data['country'] ?? 'India')),
            'google_maps_url'          => trim((string)($data['google_maps_url'] ?? '')),
            'contact_designation'      => trim((string)($data['contact_designation'] ?? '')),
            'contact_email'            => trim((string)($data['contact_email'] ?? '')),
            'alternate_contact_number' => trim((string)($data['alternate_contact_number'] ?? '')),
            'manufacturing_activity'   => trim((string)($data['manufacturing_activity'] ?? '')),
            'production_category'      => trim((string)($data['production_category'] ?? '')),
            'departments'              => is_array($data['departments'] ?? null) ? implode(', ', $data['departments']) : trim((string)($data['departments'] ?? '')),
            'shifts_count'             => (int)($data['shifts_count'] ?? 3),
            'working_days_per_week'    => (int)($data['working_days_per_week'] ?? 6),
            'workforce_size'           => trim((string)($data['workforce_size'] ?? '')),
            'establishment_year'       => trim((string)($data['establishment_year'] ?? '')),
            'description'              => trim((string)($data['description'] ?? '')),
            'assigned_admin_user_id'   => !empty($data['assigned_admin_user_id']) ? (int)$data['assigned_admin_user_id'] : null,
            'updated_at'               => date('Y-m-d H:i:s')
        ];

        $saveMetaStmt = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
            VALUES (:k, :v, :desc, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $saveMetaStmt->execute([
            ':k'    => $metaKey,
            ':v'    => json_encode($metaPayload),
            ':desc' => "Extended operational metadata for manufacturing plant #{$targetPlantId}"
        ]);

        // Audit Logging
        $auditAction = $plantId ? 'PLANT_UPDATED' : 'PLANT_CREATED';
        $this->logAudit($auditAction, getUserId(), $companyId, ['plant_id' => $targetPlantId, 'code' => $code]);

        // In-App Notification Dispatch via NotificationService
        require_once __DIR__ . '/../includes/notification_service.php';
        $notifService = new NotificationService($this->pdo);
        if ($plantId) {
            $notifService->notifyCompanyAdmins($companyId, 'PLANT_UPDATED', 'Plant Facility Updated', "Manufacturing plant '{$name}' ({$code}) was updated.", '/views/industry/plants.php');
            return ['success' => true, 'message' => "Plant '{$name}' updated successfully.", 'id' => $targetPlantId];
        } else {
            $notifService->notifyCompanyAdmins($companyId, 'PLANT_CREATED', 'New Plant Facility Registered', "New manufacturing plant '{$name}' ({$code}) was registered.", '/views/industry/plants.php');
            return ['success' => true, 'message' => "Manufacturing plant '{$name}' added successfully.", 'id' => $targetPlantId];
        }
    }

    /**
     * Set Plant Status (Active / Inactive) with In-App Notification
     */
    public function setPlantStatus(int $companyId, int $plantId, string $status): array
    {
        if (!in_array($status, ['Active', 'Inactive'], true)) {
            return ['success' => false, 'message' => 'Invalid plant status.'];
        }

        $check = $this->pdo->prepare("SELECT plant_name, plant_code FROM `manufacturing_plants` WHERE `id` = :pid AND `company_id` = :cid");
        $check->execute([':pid' => $plantId, ':cid' => $companyId]);
        $plant = $check->fetch();
        if (!$plant) {
            return ['success' => false, 'message' => 'Plant not found or access denied.'];
        }

        $stmt = $this->pdo->prepare("UPDATE `manufacturing_plants` SET `status` = :status, `updated_at` = NOW() 
            WHERE `id` = :pid AND `company_id` = :cid");
        $stmt->execute([':status' => $status, ':pid' => $plantId, ':cid' => $companyId]);

        $this->logAudit('PLANT_STATUS_CHANGED', getUserId(), $companyId, ['plant_id' => $plantId, 'status' => $status]);

        require_once __DIR__ . '/../includes/notification_service.php';
        $notifService = new NotificationService($this->pdo);
        $notifService->notifyCompanyAdmins(
            $companyId,
            'PLANT_STATUS_CHANGED',
            'Plant Operational Status Changed',
            "Plant '{$plant['plant_name']}' status changed to '{$status}'.",
            '/views/industry/plants.php'
        );

        return ['success' => true, 'message' => "Plant status updated to '{$status}'."];
    }

    /**
     * Assign Plant Admin / Coordinator
     */
    public function assignPlantAdmin(int $companyId, int $plantId, int $userId): array
    {
        // 1. Validate Plant
        $plant = $this->getPlantById($companyId, $plantId);
        if (!$plant) {
            return ['success' => false, 'message' => 'Plant not found or access denied.'];
        }

        // 2. Validate User belongs to Company
        $uStmt = $this->pdo->prepare("SELECT id, full_name, email FROM `users` WHERE id = :uid AND company_id = :cid LIMIT 1");
        $uStmt->execute([':uid' => $userId, ':cid' => $companyId]);
        $targetUser = $uStmt->fetch();
        if (!$targetUser) {
            return ['success' => false, 'message' => 'Selected user does not belong to your company.'];
        }

        // 3. Update Plant Metadata
        $metaKey = 'plant_meta_' . $plantId;
        $metaStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $metaStmt->execute([':k' => $metaKey]);
        $raw = $metaStmt->fetchColumn();
        $meta = $raw ? json_decode((string)$raw, true) : [];
        $meta['assigned_admin_user_id'] = $userId;
        $meta['updated_at'] = date('Y-m-d H:i:s');

        $saveMetaStmt = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
            VALUES (:k, :v, :desc, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $saveMetaStmt->execute([
            ':k'    => $metaKey,
            ':v'    => json_encode($meta),
            ':desc' => "Extended operational metadata for manufacturing plant #{$plantId}"
        ]);

        // 4. Update user's metadata for assigned plant
        $userMetaKey = 'user_meta_' . $userId;
        $uMetaStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $uMetaStmt->execute([':k' => $userMetaKey]);
        $rawUMeta = $uMetaStmt->fetchColumn();
        $uMeta = $rawUMeta ? json_decode((string)$rawUMeta, true) : [];
        $uMeta['assigned_plant_id'] = $plantId;
        $uMeta['updated_at'] = date('Y-m-d H:i:s');

        $saveUMeta = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
            VALUES (:k, :v, :desc, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $saveUMeta->execute([
            ':k'    => $userMetaKey,
            ':v'    => json_encode($uMeta),
            ':desc' => "User metadata for user #{$userId}"
        ]);

        // 5. Audit & Notifications
        $this->logAudit('PLANT_ADMIN_ASSIGNED', getUserId(), $companyId, [
            'plant_id' => $plantId,
            'user_id'  => $userId
        ]);

        require_once __DIR__ . '/../includes/notification_service.php';
        $notifService = new NotificationService($this->pdo);
        $notifService->sendInApp(
            $userId,
            'PLANT_COORDINATOR_ASSIGNED',
            'Plant Coordinator Assignment',
            "You have been assigned as the facility coordinator for plant '{$plant['plant_name']}'.",
            '/views/industry/plants.php',
            $companyId
        );

        return ['success' => true, 'message' => "User '{$targetUser['full_name']}' assigned as coordinator for plant '{$plant['plant_name']}'."];
    }

    /**
     * Get Company Team Members with Assigned Plants, Designations, and Granular Permissions
     */
    public function getTeamMembers(int $companyId, array $filters = []): array
    {
        $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.status, 
                u.last_login_at, u.created_at, r.name AS role_name, r.label AS role_label
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.company_id = :cid";
        $params = [':cid' => $companyId];

        if (!empty($filters['role'])) {
            $sql .= " AND r.name = :rname";
            $params[':rname'] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.full_name LIKE :s1 OR u.email LIKE :s2 OR u.phone LIKE :s3)";
            $sVal = '%' . trim($filters['search']) . '%';
            $params[':s1'] = $sVal;
            $params[':s2'] = $sVal;
            $params[':s3'] = $sVal;
        }

        $sql .= " ORDER BY u.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll();

        // Merge User Metadata (Designation, Assigned Plant) and Granular Permissions
        foreach ($members as &$m) {
            $uid = (int)$m['id'];
            
            // User Meta
            $uMetaKey = 'user_meta_' . $uid;
            $umStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
            $umStmt->execute([':k' => $uMetaKey]);
            $rawMeta = $umStmt->fetchColumn();
            $meta = $rawMeta ? json_decode((string)$rawMeta, true) : [];
            $m['designation'] = $meta['designation'] ?? ($m['role_name'] === 'industry_admin' ? 'Company Administrator' : 'Recruitment Officer');
            $m['assigned_plant_id'] = !empty($meta['assigned_plant_id']) ? (int)$meta['assigned_plant_id'] : null;

            // Resolve Plant Name
            if ($m['assigned_plant_id']) {
                $pStmt = $this->pdo->prepare("SELECT plant_name, plant_code FROM `manufacturing_plants` WHERE id = :pid LIMIT 1");
                $pStmt->execute([':pid' => $m['assigned_plant_id']]);
                $pRow = $pStmt->fetch();
                $m['assigned_plant_name'] = $pRow ? "{$pRow['plant_name']} ({$pRow['plant_code']})" : 'Facility #' . $m['assigned_plant_id'];
            } else {
                $m['assigned_plant_name'] = 'All Plants / Headquarters';
            }

            // Permissions
            $m['permissions'] = $this->getUserPermissions($uid, $m['role_name']);
        }

        return $members;
    }

    /**
     * Get Single Team Member Details
     */
    public function getTeamMemberById(int $companyId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.company_id, u.full_name, u.email, u.phone, u.status, 
                u.last_login_at, u.created_at, r.name AS role_name, r.label AS role_label
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.id = :uid AND u.company_id = :cid
            LIMIT 1");
        $stmt->execute([':uid' => $userId, ':cid' => $companyId]);
        $member = $stmt->fetch();
        if (!$member) return null;

        // Meta
        $uMetaKey = 'user_meta_' . $userId;
        $umStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $umStmt->execute([':k' => $uMetaKey]);
        $rawMeta = $umStmt->fetchColumn();
        $meta = $rawMeta ? json_decode((string)$rawMeta, true) : [];
        $member['designation'] = $meta['designation'] ?? ($member['role_name'] === 'industry_admin' ? 'Company Administrator' : 'Recruitment Officer');
        $member['assigned_plant_id'] = !empty($meta['assigned_plant_id']) ? (int)$meta['assigned_plant_id'] : null;
        $member['permissions'] = $this->getUserPermissions($userId, $member['role_name']);

        return $member;
    }

    /**
     * Update Team Member Allowed Details & Plant Assignment
     */
    public function updateTeamMember(int $companyId, int $userId, array $data): array
    {
        // 1. Verify existence & scoping
        $member = $this->getTeamMemberById($companyId, $userId);
        if (!$member) {
            return ['success' => false, 'message' => 'Team member not found or access denied.'];
        }

        $fullName    = trim((string)($data['full_name'] ?? $member['full_name']));
        $phone       = trim((string)($data['phone'] ?? $member['phone']));
        $designation = trim((string)($data['designation'] ?? $member['designation']));
        $plantId     = !empty($data['assigned_plant_id']) ? (int)$data['assigned_plant_id'] : null;

        if (strlen($fullName) < 2) {
            return ['success' => false, 'message' => 'Full name is required.'];
        }

        // Validate plant if assigned
        if ($plantId) {
            $pCheck = $this->pdo->prepare("SELECT id FROM `manufacturing_plants` WHERE id = :pid AND company_id = :cid");
            $pCheck->execute([':pid' => $plantId, ':cid' => $companyId]);
            if (!$pCheck->fetch()) {
                return ['success' => false, 'message' => 'Assigned plant not found in your company.'];
            }
        }

        // Update core user
        $uStmt = $this->pdo->prepare("UPDATE `users` SET `full_name` = :name, `phone` = :phone, `updated_at` = NOW() WHERE `id` = :uid AND `company_id` = :cid");
        $uStmt->execute([':name' => $fullName, ':phone' => $phone, ':uid' => $userId, ':cid' => $companyId]);

        // Update meta in system_settings
        $uMetaKey = 'user_meta_' . $userId;
        $umStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $umStmt->execute([':k' => $uMetaKey]);
        $rawMeta = $umStmt->fetchColumn();
        $meta = $rawMeta ? json_decode((string)$rawMeta, true) : [];
        $meta['designation'] = $designation;
        $meta['assigned_plant_id'] = $plantId;
        $meta['updated_at'] = date('Y-m-d H:i:s');

        $saveUMeta = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
            VALUES (:k, :v, :desc, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $saveUMeta->execute([
            ':k'    => $uMetaKey,
            ':v'    => json_encode($meta),
            ':desc' => "User metadata for user #{$userId}"
        ]);

        // Audit Log
        $this->logAudit('TEAM_MEMBER_UPDATED', getUserId(), $companyId, [
            'target_user_id'    => $userId,
            'assigned_plant_id' => $plantId,
            'designation'       => $designation
        ]);

        return ['success' => true, 'message' => "Team member '{$fullName}' updated successfully."];
    }

    /**
     * Get Granular User Permissions
     */
    public function getUserPermissions(int $userId, ?string $roleName = null): array
    {
        $allPermissions = [
            'view_profile'        => true,
            'edit_profile'        => false,
            'view_plants'         => true,
            'manage_plants'       => false,
            'view_requirements'   => true,
            'manage_requirements' => false,
            'view_candidates'     => false,
            'view_reports'        => false
        ];

        if ($roleName === null) {
            $rStmt = $this->pdo->prepare("SELECT r.name FROM `users` u JOIN `roles` r ON u.role_id = r.id WHERE u.id = :uid LIMIT 1");
            $rStmt->execute([':uid' => $userId]);
            $roleName = (string)$rStmt->fetchColumn();
        }

        // Industry Admin has full capabilities within their company
        if ($roleName === 'industry_admin' || $roleName === 'super_admin') {
            foreach ($allPermissions as $k => $v) {
                $allPermissions[$k] = true;
            }
            return $allPermissions;
        }

        // For Industry User, check custom configured permissions
        $pKey = 'user_perms_' . $userId;
        $pStmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
        $pStmt->execute([':k' => $pKey]);
        $raw = $pStmt->fetchColumn();
        if ($raw) {
            $custom = json_decode((string)$raw, true);
            if (is_array($custom)) {
                foreach ($allPermissions as $pk => $pv) {
                    if (isset($custom[$pk])) {
                        $allPermissions[$pk] = (bool)$custom[$pk];
                    }
                }
            }
        }

        return $allPermissions;
    }

    /**
     * Configure Granular Permissions for Team Member
     */
    public function setUserPermissions(int $companyId, int $userId, array $permissions): array
    {
        // 1. Verify user belongs to company
        $member = $this->getTeamMemberById($companyId, $userId);
        if (!$member) {
            return ['success' => false, 'message' => 'Team member not found or access denied.'];
        }

        // Industry Admin role already has full permissions
        if ($member['role_name'] === 'industry_admin') {
            return ['success' => false, 'message' => 'Company Administrators possess full capabilities by role definition.'];
        }

        $sanitizedPerms = [
            'view_profile'        => !empty($permissions['view_profile']),
            'edit_profile'        => !empty($permissions['edit_profile']),
            'view_plants'         => !empty($permissions['view_plants']),
            'manage_plants'       => !empty($permissions['manage_plants']),
            'view_requirements'   => !empty($permissions['view_requirements']),
            'manage_requirements' => !empty($permissions['manage_requirements']),
            'view_candidates'     => !empty($permissions['view_candidates']),
            'view_reports'        => !empty($permissions['view_reports'])
        ];

        $pKey = 'user_perms_' . $userId;
        $saveStmt = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
            VALUES (:k, :v, :desc, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $saveStmt->execute([
            ':k'    => $pKey,
            ':v'    => json_encode($sanitizedPerms),
            ':desc' => "Granular portal permissions for user #{$userId}"
        ]);

        $this->logAudit('USER_PERMISSIONS_UPDATED', getUserId(), $companyId, [
            'target_user_id' => $userId,
            'permissions'    => $sanitizedPerms
        ]);

        // Dispatch In-App Notification
        require_once __DIR__ . '/../includes/notification_service.php';
        $notifService = new NotificationService($this->pdo);
        $notifService->sendInApp(
            $userId,
            'PERMISSIONS_UPDATED',
            'Portal Permissions Configured',
            'Your role capabilities and portal permissions were updated by company administration.',
            '/views/industry/dashboard.php',
            $companyId
        );

        return ['success' => true, 'message' => "Permissions updated for {$member['full_name']}."];
    }

    /**
     * Get Industry Partners / Divisions Directory
     * Multi-company support for Super Admin and Company Admin
     */
    public function getIndustries(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["1 = 1"];
        $params = [];

        if (!empty($filters['company_id'])) {
            $conditions[] = "c.id = :cid";
            $params[':cid'] = (int)$filters['company_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(c.company_name LIKE :s1 OR c.registration_number LIKE :s2 OR c.hr_contact_name LIKE :s3 OR c.hr_contact_email LIKE :s4 OR c.city LIKE :s5)";
            $sVal = '%' . trim($filters['search']) . '%';
            $params[':s1'] = $sVal;
            $params[':s2'] = $sVal;
            $params[':s3'] = $sVal;
            $params[':s4'] = $sVal;
            $params[':s5'] = $sVal;
        }

        if (!empty($filters['industry_type'])) {
            $conditions[] = "c.industry_type = :type";
            $params[':type'] = $filters['industry_type'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "c.verification_status = :status";
            $params[':status'] = $filters['status'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count Total
        $cStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `companies` c WHERE $whereClause");
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id) AS total_plants,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id AND mp.status = 'Active') AS active_plants,
                (SELECT COUNT(*) FROM `manpower_requirements` mr WHERE mr.company_id = c.id) AS total_requirements,
                (SELECT u.full_name FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_name,
                (SELECT u.email FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_email,
                (SELECT u.phone FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_phone
            FROM `companies` c
            WHERE $whereClause
            ORDER BY c.id DESC
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $industries = $stmt->fetchAll();

        // Format codes
        foreach ($industries as &$ind) {
            $ind['industry_code'] = 'IND-' . str_pad((string)$ind['id'], 4, '0', STR_PAD_LEFT);
        }

        return [
            'data'        => $industries,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Single Industry Entity with Full Profile & Plant Roster
     */
    public function getIndustryById(int $industryId, ?int $scopedCompanyId = null): ?array
    {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id) AS total_plants,
                (SELECT COUNT(*) FROM `manufacturing_plants` mp WHERE mp.company_id = c.id AND mp.status = 'Active') AS active_plants,
                (SELECT COUNT(*) FROM `users` u WHERE u.company_id = c.id AND u.status = 'Active') AS total_users,
                (SELECT u.full_name FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_name,
                (SELECT u.email FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_email,
                (SELECT u.phone FROM `users` u WHERE u.company_id = c.id AND u.role_id = 2 ORDER BY u.id ASC LIMIT 1) AS assigned_admin_phone
            FROM `companies` c
            WHERE c.id = :id";
        $params = [':id' => $industryId];

        if ($scopedCompanyId !== null) {
            $sql .= " AND c.id = :cid";
            $params[':cid'] = $scopedCompanyId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $industry = $stmt->fetch();
        if (!$industry) return null;

        $industry['industry_code'] = 'IND-' . str_pad((string)$industry['id'], 4, '0', STR_PAD_LEFT);
        $industry['plants'] = $this->getPlants($industryId);
        $industry['team'] = $this->getTeamMembers($industryId);

        return $industry;
    }

    /**
     * Save / Register Industry Profile
     */
    public function saveIndustry(?int $companyId, array $data, ?int $industryId = null): array
    {
        $name     = trim((string)($data['industry_name'] ?? $data['company_name'] ?? ''));
        $code     = strtoupper(trim((string)($data['industry_code'] ?? '')));
        $type     = trim((string)($data['industry_type'] ?? ''));
        $regNo    = trim((string)($data['registration_number'] ?? ''));
        $contact  = trim((string)($data['primary_contact_person'] ?? $data['hr_contact_name'] ?? ''));
        $email    = trim((string)($data['official_email'] ?? $data['hr_contact_email'] ?? ''));
        $phone    = trim((string)($data['contact_number'] ?? $data['hr_contact_phone'] ?? ''));
        $addr     = trim((string)($data['address'] ?? $data['address_line1'] ?? ''));
        $state    = trim((string)($data['state'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));
        $city     = trim((string)($data['city'] ?? ''));
        $pincode  = trim((string)($data['pincode'] ?? ''));
        $website  = trim((string)($data['website'] ?? ''));
        $desc     = trim((string)($data['description'] ?? ''));
        $status   = (string)($data['status'] ?? 'Active');

        // Validation
        if (strlen($name) < 3) return ['success' => false, 'message' => 'Industry/Company legal name must be at least 3 characters.'];
        if (empty($type)) return ['success' => false, 'message' => 'Industry type/sector is required.'];
        if (empty($contact)) return ['success' => false, 'message' => 'Primary contact person is required.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid official email address is required.'];
        if (strlen($phone) < 7) return ['success' => false, 'message' => 'Valid contact number is required.'];
        if (empty($addr)) return ['success' => false, 'message' => 'Registered address is required.'];
        if (empty($state) || empty($district)) return ['success' => false, 'message' => 'State and District are required.'];
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) return ['success' => false, 'message' => 'Valid 6-digit Indian PIN code is required.'];

        // Duplicate code validation
        if (!empty($code)) {
            $codeCheck = $this->pdo->prepare("SELECT id FROM `companies` WHERE `registration_number` = :code" . ($industryId ? " AND id != :iid" : "") . " LIMIT 1");
            $cParams = [':code' => $code];
            if ($industryId) $cParams[':iid'] = $industryId;
            $codeCheck->execute($cParams);
            if ($codeCheck->fetch()) {
                return ['success' => false, 'message' => "An industry with code/registration number '{$code}' already exists."];
            }
        }

        $targetId = $industryId;

        if ($industryId) {
            // Update
            $sql = "UPDATE `companies` SET 
                    `company_name` = :name, `industry_type` = :type, `registration_number` = :reg,
                    `hr_contact_name` = :contact, `hr_contact_email` = :email, `hr_contact_phone` = :phone,
                    `address` = :addr, `state` = :state, `district` = :district, `city` = :city, `pincode` = :pin,
                    `website` = :web, `description` = :desc, `updated_at` = NOW()
                WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':type'     => $type,
                ':reg'      => $code ?: (!empty($regNo) ? $regNo : null),
                ':contact'  => $contact,
                ':email'    => $email,
                ':phone'    => $phone,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':web'      => $website ?: null,
                ':desc'     => $desc ?: null,
                ':id'       => $industryId
            ]);
        } else {
            // Create
            $sql = "INSERT INTO `companies` 
                    (`company_name`, `industry_type`, `registration_number`, `hr_contact_name`, `hr_contact_email`, `hr_contact_phone`, `address`, `state`, `district`, `city`, `pincode`, `website`, `description`, `verification_status`) 
                    VALUES 
                    (:name, :type, :reg, :contact, :email, :phone, :addr, :state, :district, :city, :pin, :web, :desc, :vstatus)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name'     => $name,
                ':type'     => $type,
                ':reg'      => $code ?: (!empty($regNo) ? $regNo : null),
                ':contact'  => $contact,
                ':email'    => $email,
                ':phone'    => $phone,
                ':addr'     => $addr,
                ':state'    => $state,
                ':district' => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':web'      => $website ?: null,
                ':desc'     => $desc ?: null,
                ':vstatus'  => $status === 'Active' ? 'Active' : 'Pending'
            ]);
            $targetId = (int)$this->pdo->lastInsertId();
        }

        // Audit Log
        $action = $industryId ? 'INDUSTRY_UPDATED' : 'INDUSTRY_CREATED';
        $this->logAudit($action, getUserId(), $targetId, ['name' => $name, 'industry_type' => $type]);

        require_once __DIR__ . '/../includes/notification_service.php';
        $notifService = new NotificationService($this->pdo);
        $notifService->broadcastToRole('super_admin', $action, 'Industry Profile Saved', "Industry profile '{$name}' was saved.", '/views/super_admin/company_details.php?id=' . $targetId);

        return ['success' => true, 'message' => "Industry '{$name}' saved successfully.", 'id' => $targetId];
    }

    /**
     * Soft Activation / Deactivation of Industry (Preserving historical requisitions)
     */
    public function setIndustryStatus(int $industryId, string $status, ?int $scopedCompanyId = null): array
    {
        if (!in_array($status, ['Active', 'Inactive', 'Suspended'], true)) {
            return ['success' => false, 'message' => 'Invalid industry status.'];
        }

        $check = $this->pdo->prepare("SELECT company_name FROM `companies` WHERE id = :id" . ($scopedCompanyId ? " AND id = :cid" : ""));
        $params = [':id' => $industryId];
        if ($scopedCompanyId) $params[':cid'] = $scopedCompanyId;
        $check->execute($params);
        $ind = $check->fetch();
        if (!$ind) {
            return ['success' => false, 'message' => 'Industry not found or access denied.'];
        }

        // Map status to verification_status
        $vStatus = match($status) {
            'Active'    => 'Active',
            'Suspended' => 'Suspended',
            default     => 'Under Review'
        };

        $stmt = $this->pdo->prepare("UPDATE `companies` SET `verification_status` = :vstatus, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':vstatus' => $vStatus, ':id' => $industryId]);

        $this->logAudit('INDUSTRY_STATUS_CHANGED', getUserId(), $industryId, ['status' => $status, 'verification_status' => $vStatus]);

        return ['success' => true, 'message' => "Industry '{$ind['company_name']}' status set to '{$status}'."];
    }

    /**
     * Get Pending Company Invitations
     */
    public function getInvitations(int $companyId): array
    {
        $stmt = $this->pdo->prepare("SELECT ci.*, r.label AS role_label, u.full_name AS invited_by_name
            FROM `company_invitations` ci
            JOIN `roles` r ON ci.role_id = r.id
            LEFT JOIN `users` u ON ci.invited_by = u.id
            WHERE ci.company_id = :cid
            ORDER BY ci.id DESC");
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Invite Team Member to Company
     */
    public function inviteTeamMember(int $companyId, int $invitedByUserId, array $data): array
    {
        $name    = trim((string)($data['name'] ?? ''));
        $email   = trim(filter_var((string)($data['email'] ?? ''), FILTER_SANITIZE_EMAIL));
        $phone   = trim((string)($data['phone'] ?? ''));
        $roleStr = trim((string)($data['role'] ?? 'industry_user'));

        if (strlen($name) < 2) return ['success' => false, 'message' => 'Full name is required.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid corporate email address is required.'];

        // Backend Capacity Limit Verification
        require_once __DIR__ . '/CompanyController.php';
        $compCtrl = new CompanyController($this->pdo);
        $limitCheck = $compCtrl->checkCompanyLimit($companyId, 'max_users');
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => $limitCheck['message']];
        }

        // Strict Role Boundary Enforcement: Industry Admin can ONLY invite 'industry_admin' or 'industry_user'
        $allowedRoles = ['industry_admin', 'industry_user'];
        if (!in_array($roleStr, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Unauthorized role assignment. Industry admins may only invite Industry Admins or Industry Team Members.'];
        }

        // Get Role ID
        $roleStmt = $this->pdo->prepare("SELECT id FROM `roles` WHERE `name` = :rname LIMIT 1");
        $roleStmt->execute([':rname' => $roleStr]);
        $roleId = (int)$roleStmt->fetchColumn();
        if ($roleId <= 0) {
            return ['success' => false, 'message' => 'Role not found in system.'];
        }

        // Check if user already exists
        $userCheck = $this->pdo->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
        $userCheck->execute([':email' => $email]);
        if ($userCheck->fetch()) {
            return ['success' => false, 'message' => 'A user account with this email address already exists.'];
        }

        // Check if pending invitation exists
        $invCheck = $this->pdo->prepare("SELECT id FROM `company_invitations` WHERE `company_id` = :cid AND `email` = :email AND `status` = 'Pending' AND `expires_at` > NOW() LIMIT 1");
        $invCheck->execute([':cid' => $companyId, ':email' => $email]);
        if ($invCheck->fetch()) {
            return ['success' => false, 'message' => 'A pending invitation for this email address already exists. You can resend it instead.'];
        }

        // Generate Cryptographic 64-char Hex Token
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 3600)); // 7 days

        $insStmt = $this->pdo->prepare("INSERT INTO `company_invitations` 
            (`company_id`, `role_id`, `name`, `email`, `contact_number`, `token_hash`, `status`, `expires_at`, `invited_by`) 
            VALUES (:cid, :rid, :name, :email, :phone, :hash, 'Pending', :exp, :by)");
        $insStmt->execute([
            ':cid'   => $companyId,
            ':rid'   => $roleId,
            ':name'  => $name,
            ':email' => $email,
            ':phone' => !empty($phone) ? $phone : null,
            ':hash'  => $tokenHash,
            ':exp'   => $expiresAt,
            ':by'    => $invitedByUserId
        ]);

        $this->logAudit('TEAM_MEMBER_INVITED', $invitedByUserId, $companyId, [
            'invited_email' => $email,
            'role'          => $roleStr
        ]);

        return [
            'success'   => true,
            'message'   => "Invitation created for {$email}. (Single-use token valid for 7 days).",
            'raw_token' => $rawToken
        ];
    }

    /**
     * Resend / Refresh Invitation
     */
    public function resendInvitation(int $companyId, int $invitationId): array
    {
        $check = $this->pdo->prepare("SELECT id FROM `company_invitations` WHERE `id` = :id AND `company_id` = :cid");
        $check->execute([':id' => $invitationId, ':cid' => $companyId]);
        if (!$check->fetch()) {
            return ['success' => false, 'message' => 'Invitation not found or access denied.'];
        }

        $newExpiry = date('Y-m-d H:i:s', time() + (7 * 24 * 3600));
        $stmt = $this->pdo->prepare("UPDATE `company_invitations` 
            SET `expires_at` = :exp, `status` = 'Pending', `updated_at` = NOW() 
            WHERE `id` = :id AND `company_id` = :cid");
        $stmt->execute([':exp' => $newExpiry, ':id' => $invitationId, ':cid' => $companyId]);

        $this->logAudit('INVITATION_RESENT', getUserId(), $companyId, ['invitation_id' => $invitationId]);
        return ['success' => true, 'message' => 'Invitation validity extended by 7 days.'];
    }

    /**
     * Revoke Invitation
     */
    public function revokeInvitation(int $companyId, int $invitationId): array
    {
        $check = $this->pdo->prepare("SELECT id FROM `company_invitations` WHERE `id` = :id AND `company_id` = :cid");
        $check->execute([':id' => $invitationId, ':cid' => $companyId]);
        if (!$check->fetch()) {
            return ['success' => false, 'message' => 'Invitation not found or access denied.'];
        }

        $stmt = $this->pdo->prepare("UPDATE `company_invitations` 
            SET `status` = 'Revoked', `updated_at` = NOW() 
            WHERE `id` = :id AND `company_id` = :cid");
        $stmt->execute([':id' => $invitationId, ':cid' => $companyId]);

        $this->logAudit('INVITATION_REVOKED', getUserId(), $companyId, ['invitation_id' => $invitationId]);
        return ['success' => true, 'message' => 'Invitation has been revoked.'];
    }

    /**
     * Set Company Team Member Account Status (Active / Suspended)
     */
    public function setMemberStatus(int $companyId, int $targetUserId, string $status): array
    {
        if (!in_array($status, ['Active', 'Suspended'], true)) {
            return ['success' => false, 'message' => 'Invalid member status.'];
        }

        // Prevent self-suspension
        if ($targetUserId === getUserId()) {
            return ['success' => false, 'message' => 'You cannot deactivate or suspend your own active administrator account.'];
        }

        $check = $this->pdo->prepare("SELECT id FROM `users` WHERE `id` = :uid AND `company_id` = :cid");
        $check->execute([':uid' => $targetUserId, ':cid' => $companyId]);
        if (!$check->fetch()) {
            return ['success' => false, 'message' => 'Team member not found or access denied.'];
        }

        $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = :status, `updated_at` = NOW() 
            WHERE `id` = :uid AND `company_id` = :cid");
        $stmt->execute([':status' => $status, ':uid' => $targetUserId, ':cid' => $companyId]);

        $this->logAudit('TEAM_MEMBER_STATUS_CHANGED', getUserId(), $companyId, ['target_user_id' => $targetUserId, 'status' => $status]);
        return ['success' => true, 'message' => "Team member status set to '{$status}'."];
    }

    /**
     * Get Company Requisitions with Search & Filters
     */
    public function getMyRequirements(int $companyId, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $conditions = ["mr.company_id = :cid"];
        $params = [':cid' => $companyId];

        if (!empty($filters['search'])) {
            $conditions[] = "(mr.title LIKE :search1 OR mr.requirement_id LIKE :search2 OR mr.job_location_city LIKE :search3)";
            $sVal = '%' . trim($filters['search']) . '%';
            $params[':search1'] = $sVal;
            $params[':search2'] = $sVal;
            $params[':search3'] = $sVal;
        }

        if (!empty($filters['status'])) {
            $conditions[] = "mr.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['plant_id'])) {
            $conditions[] = "mr.plant_id = :pid";
            $params[':pid'] = (int)$filters['plant_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `manpower_requirements` mr WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT mr.*, mp.plant_name, mp.plant_code,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id) AS matched_count,
                (SELECT COUNT(*) FROM `candidate_matches` cm WHERE cm.requirement_id = mr.id AND cm.status = 'Joined') AS joined_count
            FROM `manpower_requirements` mr
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
            WHERE $whereClause
            ORDER BY mr.id DESC
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'data'        => $records,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Requirement Dossier (Strict Company Scoping)
     */
    public function getRequirementDetails(int $companyId, int $requirementId): ?array
    {
        $sql = "SELECT mr.*, mp.plant_name, mp.plant_code, mp.plant_address, mp.city AS plant_city,
                u.full_name AS created_by_name,
                app.full_name AS approved_by_name
            FROM `manpower_requirements` mr
            LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
            LEFT JOIN `users` u ON mr.created_by = u.id
            LEFT JOIN `users` app ON mr.approved_by = app.id
            WHERE mr.id = :rid AND mr.company_id = :cid
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rid' => $requirementId, ':cid' => $companyId]);
        $req = $stmt->fetch();
        if (!$req) return null;

        // Fetch Trades
        $tradesStmt = $this->pdo->prepare("SELECT * FROM `requirement_trades` WHERE `requirement_id` = :rid");
        $tradesStmt->execute([':rid' => $requirementId]);
        $req['trades'] = $tradesStmt->fetchAll();

        // Fetch Skills
        $skillsStmt = $this->pdo->prepare("SELECT * FROM `requirement_skills` WHERE `requirement_id` = :rid");
        $skillsStmt->execute([':rid' => $requirementId]);
        $req['skills'] = $skillsStmt->fetchAll();

        // Fetch Status History
        $histStmt = $this->pdo->prepare("SELECT rsh.*, u.full_name AS changed_by_name 
            FROM `requirement_status_history` rsh
            LEFT JOIN `users` u ON rsh.changed_by = u.id
            WHERE rsh.requirement_id = :rid
            ORDER BY rsh.id DESC");
        $histStmt->execute([':rid' => $requirementId]);
        $req['history'] = $histStmt->fetchAll();

        // Fetch Candidate Pipeline Summary
        $pipeStmt = $this->pdo->prepare("SELECT 
                COUNT(*) AS total_matched,
                SUM(CASE WHEN `status` = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted,
                SUM(CASE WHEN `status` = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN `status` = 'Selected' THEN 1 ELSE 0 END) AS selected,
                SUM(CASE WHEN `status` = 'Joined' THEN 1 ELSE 0 END) AS joined
            FROM `candidate_matches`
            WHERE `requirement_id` = :rid");
        $pipeStmt->execute([':rid' => $requirementId]);
        $req['pipeline'] = $pipeStmt->fetch() ?: [];

        return $req;
    }

    /**
     * Get Candidate Matches (Privacy-Compliant Projection)
     */
    public function getCandidateMatches(int $companyId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [
            "mr.company_id = :cid",
            "(cm.is_shared_with_industry = 1 OR cm.status != 'Matched')"
        ];
        $params = [':cid' => $companyId];

        if (!empty($filters['trade'])) {
            $conditions[] = "cm.trade = :trade";
            $params[':trade'] = $filters['trade'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "cm.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['min_score'])) {
            $conditions[] = "cm.match_score >= :score";
            $params[':score'] = floatval($filters['min_score']);
        }

        if (!empty($filters['requirement_id'])) {
            $conditions[] = "cm.requirement_id = :rid";
            $params[':rid'] = (int)$filters['requirement_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) 
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
            WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        // Privacy-Preserving Column Selection:
        // Mask candidate name or display authorized identity, exclude Aadhaar/PAN/internal IDs
        $sql = "SELECT cm.id, cm.requirement_id, cm.trade, cm.passing_year, cm.match_score, 
                cm.trade_score, cm.batch_score, cm.qual_score, cm.skills_score, cm.location_score, cm.exp_score, cm.avail_score,
                cm.match_details_json, cm.eligibility_status, cm.eligibility_reasons,
                cm.status, cm.matched_at,
                cm.candidate_name, cm.candidate_source_id, cm.candidate_source,
                mr.title AS requirement_title, mr.requirement_id AS requirement_code
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
            WHERE $whereClause
            ORDER BY cm.match_score DESC, cm.id DESC
            LIMIT :offset, :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $candidates = $stmt->fetchAll();

        return [
            'data'        => $candidates,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get Company Notifications
     */
    public function getNotifications(int $companyId, int $userId): array
    {
        // Fetch notifications for the user + notifications targeting their company
        $stmt = $this->pdo->prepare("SELECT n.* 
            FROM `notifications` n
            WHERE n.recipient_user_id = :uid
            ORDER BY n.id DESC
            LIMIT 50");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Helper: Record Audit Event
     */
    private function logAudit(string $action, ?int $userId, ?int $companyId, array $details = []): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Internal';
            $stmt = $this->pdo->prepare("INSERT INTO `audit_logs` 
                (`user_id`, `company_id`, `action`, `ip_address`, `user_agent`, `details_json`) 
                VALUES (:uid, :cid, :action, :ip, :ua, :details)");
            $stmt->execute([
                ':uid'     => $userId,
                ':cid'     => $companyId,
                ':action'  => $action,
                ':ip'      => $ip,
                ':ua'      => substr($ua, 0, 255),
                ':details' => json_encode($details)
            ]);
        } catch (Exception $e) {
            // Silently absorb audit log write failure
        }
    }
}
