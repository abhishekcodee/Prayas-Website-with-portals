<?php
/**
 * Authentication Controller
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

class AuthController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * User Login Process
     */
    public function login(string $email, string $password): array
    {
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Please provide both email and password.'];
        }

        // Rate Limiting Guard: Max 5 failed attempts per target account (or non-local IP) in 15 minutes
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $isLocalhost = in_array($ip, ['127.0.0.1', '::1', 'localhost', 'CLI/System', 'UNKNOWN'], true);
        
        $sql = "SELECT COUNT(*) FROM `audit_logs` 
            WHERE `action` = 'LOGIN_FAILED' 
              AND (details_json LIKE :emailPattern" . (!$isLocalhost ? " OR ip_address = :ip" : "") . ") 
              AND `created_at` >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $rateStmt = $this->pdo->prepare($sql);
        
        $params = [':emailPattern' => '%' . $email . '%'];
        if (!$isLocalhost) {
            $params[':ip'] = $ip;
        }
        $rateStmt->execute($params);
        $failedAttempts = (int)$rateStmt->fetchColumn();

        if ($failedAttempts >= 5) {
            $this->logAudit('LOGIN_RATE_LIMITED', null, null, [
                'email'    => $email,
                'ip'       => $ip,
                'attempts' => $failedAttempts
            ]);
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. For security reasons, please try again in 15 minutes.'
            ];
        }

        // Query user with joined role and company information
        $stmt = $this->pdo->prepare("SELECT 
                u.id, u.role_id, u.company_id, u.full_name, u.email, u.password_hash, 
                u.must_change_password, u.status,
                r.name AS role_name, r.label AS role_label,
                c.company_name, c.verification_status AS company_verification_status
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            LEFT JOIN `companies` c ON u.company_id = c.id
            WHERE u.email = :email
            LIMIT 1");
        
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failed login attempt
            $this->logAudit('LOGIN_FAILED', null, null, ['email' => $email, 'reason' => 'Invalid credentials']);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if ($user['status'] !== 'Active') {
            $this->logAudit('LOGIN_BLOCKED_INACTIVE', (int)$user['id'], $user['company_id'] ? (int)$user['company_id'] : null, ['email' => $email, 'status' => $user['status']]);
            return ['success' => false, 'message' => "Your account is currently {$user['status']}. Please contact PRAYAS administration."];
        }

        // Session fixation protection: regenerate session ID if session is active
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }

        // Populate session state
        $_SESSION['user_id']              = (int)$user['id'];
        $_SESSION['role_id']              = (int)$user['role_id'];
        $_SESSION['role_name']            = (string)$user['role_name'];
        $_SESSION['role_label']           = (string)$user['role_label'];
        $_SESSION['company_id']           = $user['company_id'] ? (int)$user['company_id'] : null;
        $_SESSION['company_name']         = (string)($user['company_name'] ?? '');
        $_SESSION['company_verified']     = ($user['company_verification_status'] === 'Verified');
        $_SESSION['full_name']            = (string)$user['full_name'];
        $_SESSION['email']                = (string)$user['email'];
        $_SESSION['must_change_password'] = (int)$user['must_change_password'];
        $_SESSION['permissions']          = loadRolePermissions((int)$user['role_id'], $this->pdo);
        $_SESSION['last_activity']        = time();

        // Update last login timestamp
        $updateStmt = $this->pdo->prepare("UPDATE `users` SET `last_login_at` = NOW() WHERE `id` = :id");
        $updateStmt->execute([':id' => $user['id']]);

        // Reset prior failed login attempts on successful authentication
        $clearLockStmt = $this->pdo->prepare("DELETE FROM `audit_logs` WHERE `action` = 'LOGIN_FAILED' AND details_json LIKE :pattern");
        $clearLockStmt->execute([':pattern' => '%' . $email . '%']);

        // Audit log success
        $this->logAudit('LOGIN_SUCCESS', (int)$user['id'], $_SESSION['company_id'], ['role' => $user['role_name']]);

        // Determine destination redirect
        $redirectUrl = $this->resolveDashboardUrl((string)$user['role_name'], (int)$user['must_change_password']);

        return [
            'success'  => true,
            'message'  => 'Authentication successful.',
            'redirect' => $redirectUrl
        ];
    }

    /**
     * User Logout Process
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logAudit('LOGOUT', (int)$_SESSION['user_id'], $_SESSION['company_id'] ?? null);
        }

        destroySecureSession();
    }

    /**
     * Industry Partner Self-Registration
     */
    public function registerIndustry(array $data): array
    {
        $errors = [];

        // Required company and user fields
        $companyName = trim((string)($data['company_name'] ?? ''));
        $regNumber   = trim((string)($data['registration_number'] ?? ''));
        $indType     = trim((string)($data['industry_type'] ?? ''));
        $address     = trim((string)($data['address'] ?? ''));
        $state       = trim((string)($data['state'] ?? ''));
        $district    = trim((string)($data['district'] ?? ''));
        $city        = trim((string)($data['city'] ?? ''));
        $pincode     = trim((string)($data['pincode'] ?? ''));
        $hrName      = trim((string)($data['hr_contact_name'] ?? ''));
        $hrPhone     = trim((string)($data['hr_contact_phone'] ?? ''));
        $hrEmail     = trim((string)($data['hr_contact_email'] ?? ''));
        $password    = (string)($data['password'] ?? '');
        $confirmPass = (string)($data['confirm_password'] ?? '');

        if (strlen($companyName) < 3) $errors['company_name'] = 'Company name must be at least 3 characters.';
        if (empty($indType)) $errors['industry_type'] = 'Please select an industry sector.';
        if (empty($address)) $errors['address'] = 'Company plant/registered address is required.';
        if (empty($state)) $errors['state'] = 'State is required.';
        if (empty($district)) $errors['district'] = 'District is required.';
        if (empty($city)) $errors['city'] = 'City is required.';
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) $errors['pincode'] = 'Pincode must be a valid 6-digit number.';
        if (strlen($hrName) < 2) $errors['hr_contact_name'] = 'HR Contact Name is required.';
        if (!preg_match('/^[6-9][0-9]{9}$/', $hrPhone)) $errors['hr_contact_phone'] = 'Please provide a valid 10-digit mobile number.';
        if (!filter_var($hrEmail, FILTER_VALIDATE_EMAIL)) $errors['hr_contact_email'] = 'Valid official email address is required.';

        // Password strength rules: min 8 characters, at least 1 letter and 1 number
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must be at least 8 characters and contain both letters and numbers.';
        }
        if ($password !== $confirmPass) {
            $errors['confirm_password'] = 'Password confirmation does not match.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Please resolve the highlighted validation errors.', 'errors' => $errors];
        }

        // Duplicate checks
        $checkStmt = $this->pdo->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
        $checkStmt->execute([':email' => $hrEmail]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'An account with this email address already exists.', 'errors' => ['hr_contact_email' => 'Email already registered.']];
        }

        if (!empty($regNumber)) {
            $regStmt = $this->pdo->prepare("SELECT id FROM `companies` WHERE `registration_number` = :reg LIMIT 1");
            $regStmt->execute([':reg' => $regNumber]);
            if ($regStmt->fetch()) {
                return ['success' => false, 'message' => 'A company with this Registration / CIN number is already registered.', 'errors' => ['registration_number' => 'Registration number already exists.']];
            }
        }

        // Retrieve industry_admin role
        $roleStmt = $this->pdo->prepare("SELECT id FROM `roles` WHERE `name` = 'industry_admin' LIMIT 1");
        $roleStmt->execute();
        $roleId = (int)$roleStmt->fetchColumn();

        if (!$roleId) {
            return ['success' => false, 'message' => 'System configuration error: industry_admin role not defined.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Insert Company
            $compStmt = $this->pdo->prepare("INSERT INTO `companies` 
                (`company_name`, `registration_number`, `industry_type`, `address`, `state`, `district`, `city`, `pincode`, `hr_contact_name`, `hr_contact_phone`, `hr_contact_email`, `verification_status`) 
                VALUES 
                (:name, :reg, :type, :addr, :state, :dist, :city, :pin, :hr_name, :hr_phone, :hr_email, 'Pending')");

            $compStmt->execute([
                ':name'     => $companyName,
                ':reg'      => !empty($regNumber) ? $regNumber : null,
                ':type'     => $indType,
                ':addr'     => $address,
                ':state'    => $state,
                ':dist'     => $district,
                ':city'     => $city,
                ':pin'      => $pincode,
                ':hr_name'  => $hrName,
                ':hr_phone' => $hrPhone,
                ':hr_email' => $hrEmail
            ]);

            $companyId = (int)$this->pdo->lastInsertId();

            // Insert User
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $userStmt = $this->pdo->prepare("INSERT INTO `users` 
                (`role_id`, `company_id`, `full_name`, `email`, `phone`, `password_hash`, `must_change_password`, `status`) 
                VALUES 
                (:role_id, :company_id, :name, :email, :phone, :hash, 0, 'Active')");

            $userStmt->execute([
                ':role_id'    => $roleId,
                ':company_id' => $companyId,
                ':name'       => $hrName,
                ':email'      => $hrEmail,
                ':phone'      => $hrPhone,
                ':hash'       => $passwordHash
            ]);

            $userId = (int)$this->pdo->lastInsertId();

            // Insert Notification for Super Admin
            $notifStmt = $this->pdo->prepare("INSERT INTO `notifications` (`recipient_user_id`, `title`, `message`, `action_url`) 
                SELECT id, 'New Industry Registered', :msg, '/views/super_admin/companies.php' 
                FROM `users` WHERE `role_id` = (SELECT id FROM `roles` WHERE `name` = 'super_admin')");
            $notifStmt->execute([':msg' => "Company '{$companyName}' has registered and is pending verification."]);

            $this->pdo->commit();

            // Log event
            $this->logAudit('INDUSTRY_REGISTERED', $userId, $companyId, ['company_name' => $companyName]);

            return [
                'success' => true,
                'message' => 'Registration successful! Your company profile is pending verification by PRAYAS administration. You can now log in.'
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("[Registration Error] " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed due to a system error. Please try again.'];
        }
    }

    /**
     * Change Password Process
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        if (empty($currentPassword) || empty($newPassword)) {
            return ['success' => false, 'message' => 'All password fields are required.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New password confirmation does not match.'];
        }

        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters with both letters and numbers.'];
        }

        $stmt = $this->pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $existingHash = $stmt->fetchColumn();

        if (!$existingHash || !password_verify($currentPassword, $existingHash)) {
            return ['success' => false, 'message' => 'Current password verification failed.'];
        }

        if (password_verify($newPassword, $existingHash)) {
            return ['success' => false, 'message' => 'New password must be different from your current password.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = $this->pdo->prepare("UPDATE `users` SET `password_hash` = :hash, `must_change_password` = 0, `updated_at` = NOW() WHERE `id` = :id");
        $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

        // Update active session flag
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
            $_SESSION['must_change_password'] = 0;
        }

        $this->logAudit('PASSWORD_CHANGED', $userId, getCompanyId());

        return ['success' => true, 'message' => 'Password updated successfully.'];
    }

    /**
     * Password Reset Request Token Generation
     */
    public function requestPasswordReset(string $email): array
    {
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM `users` WHERE `email` = :email AND `status` = 'Active' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Always return positive message to prevent user enumeration
        $genericMessage = 'If an active account exists with that email, instructions have been generated.';

        if (!$user) {
            return ['success' => true, 'message' => $genericMessage];
        }

        // Generate token and record in password_resets
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $insertStmt = $this->pdo->prepare("INSERT INTO `password_resets` (`email`, `token_hash`, `expires_at`) 
            VALUES (:email, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $insertStmt->execute([
            ':email' => $email,
            ':hash'  => $tokenHash
        ]);

        $resetUrl = "/views/auth/reset_password.php?token=" . urlencode($rawToken) . "&email=" . urlencode($email);

        $this->logAudit('PASSWORD_RESET_REQUESTED', (int)$user['id'], null, ['email' => $email]);

        return [
            'success'   => true,
            'message'   => $genericMessage,
            'dev_link'  => $resetUrl // For developer testing when SMTP is not configured
        ];
    }

    /**
     * Finalize Password Reset
     */
    public function resetPassword(string $email, string $token, string $newPassword, string $confirmPassword): array
    {
        if (empty($email) || empty($token) || empty($newPassword)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Password confirmation does not match.'];
        }

        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters and contain both letters and numbers.'];
        }

        $tokenHash = hash('sha256', $token);

        $checkStmt = $this->pdo->prepare("SELECT id FROM `password_resets` 
            WHERE `email` = :email AND `token_hash` = :hash AND `expires_at` > NOW() AND `used_at` IS NULL 
            ORDER BY id DESC LIMIT 1");
        $checkStmt->execute([':email' => $email, ':hash' => $tokenHash]);
        $resetRecord = $checkStmt->fetch();

        if (!$resetRecord) {
            return ['success' => false, 'message' => 'Invalid or expired password reset link. Please request a new one.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $this->pdo->beginTransaction();

            $updateUser = $this->pdo->prepare("UPDATE `users` SET `password_hash` = :hash, `must_change_password` = 0, `updated_at` = NOW() WHERE `email` = :email");
            $updateUser->execute([':hash' => $newHash, ':email' => $email]);

            $markUsed = $this->pdo->prepare("UPDATE `password_resets` SET `used_at` = NOW() WHERE `id` = :id");
            $markUsed->execute([':id' => $resetRecord['id']]);

            $this->pdo->commit();

            $this->logAudit('PASSWORD_RESET_COMPLETED', null, null, ['email' => $email]);

            return ['success' => true, 'message' => 'Password has been reset successfully. You can now log in.'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to reset password due to a system error.'];
        }
    }

    private function resolveDashboardUrl(string $roleName, int $mustChangePassword): string
    {
        if ($mustChangePassword === 1) {
            return '/views/auth/change_password.php';
        }

        return match ($roleName) {
            'super_admin'                     => '/views/super_admin/dashboard.php',
            'industry_admin', 'industry_user' => '/views/industry/dashboard.php',
            'recruitment_admin'               => '/views/recruitment_admin/dashboard.php',
            'recruiter'                       => '/views/recruiter/dashboard.php',
            default                           => '/views/auth/login.php'
        };
    }

    private function logAudit(string $action, ?int $userId, ?int $companyId = null, array $details = []): void
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `company_id`, `action`, `ip_address`, `user_agent`, `details_json`) 
                VALUES (:uid, :cid, :act, :ip, :ua, :details)");
            $stmt->execute([
                ':uid'     => $userId,
                ':cid'     => $companyId,
                ':act'     => $action,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 255),
                ':details' => !empty($details) ? json_encode($details) : null
            ]);
        } catch (Exception $e) {
            error_log("[Audit Logging Failure] " . $e->getMessage());
        }
    }
}
