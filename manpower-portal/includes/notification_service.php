<?php
/**
 * Centralized Notification & Email Dispatch Service
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL
 */

declare(strict_types=1);

class NotificationService
{
    private PDO $pdo;
    private array $settingsCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieve system setting with in-memory caching.
     */
    public function getSetting(string $key, ?string $default = null): ?string
    {
        if (isset($this->settingsCache[$key])) {
            return $this->settingsCache[$key];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM `system_settings` WHERE setting_key = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();
            $this->settingsCache[$key] = ($val !== false) ? (string)$val : $default;
            return $this->settingsCache[$key];
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Send an In-App Notification to a specific user.
     */
    public function sendInApp(
        int $recipientUserId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?int $companyId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `notifications` 
                (`recipient_user_id`, `company_id`, `notification_type`, `title`, `message`, `action_url`, `entity_type`, `entity_id`, `is_read`, `status`, `created_at`) 
                VALUES 
                (:uid, :cid, :type, :title, :msg, :url, :etype, :eid, 0, 'unread', NOW())");
            
            $stmt->execute([
                ':uid'   => $recipientUserId,
                ':cid'   => $companyId,
                ':type'  => $type,
                ':title' => $title,
                ':msg'   => $message,
                ':url'   => $actionUrl,
                ':etype' => $entityType,
                ':eid'   => $entityId
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to send in-app notification: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Broadcast an in-app notification to all active users with a specified role.
     */
    public function broadcastToRole(
        string $roleName,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?int $companyId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        try {
            $stmt = $this->pdo->prepare("SELECT u.id, u.email, u.company_id 
                FROM `users` u 
                JOIN `roles` r ON r.id = u.role_id 
                WHERE r.name = :rname AND u.status = 'Active'");
            $stmt->execute([':rname' => $roleName]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($users as $user) {
                $targetCid = $companyId ?? $user['company_id'];
                $notifId = $this->sendInApp((int)$user['id'], $type, $title, $message, $actionUrl, $targetCid, $entityType, $entityId);
                if ($notifId > 0) $count++;
            }
            return $count;
        } catch (Exception $e) {
            error_log("Failed to broadcast notification to role {$roleName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notify all administrators belonging to a specific company.
     */
    public function notifyCompanyAdmins(
        int $companyId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): int {
        try {
            $stmt = $this->pdo->prepare("SELECT u.id, u.email 
                FROM `users` u 
                JOIN `roles` r ON r.id = u.role_id 
                WHERE u.company_id = :cid AND r.name IN ('industry_admin', 'industry_user') AND u.status = 'Active'");
            $stmt->execute([':cid' => $companyId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($users as $user) {
                $notifId = $this->sendInApp((int)$user['id'], $type, $title, $message, $actionUrl, $companyId, $entityType, $entityId);
                if ($notifId > 0) $count++;
            }
            return $count;
        } catch (Exception $e) {
            error_log("Failed to notify company admins for company {$companyId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sends an email using configured SMTP / mail transport or logs simulation.
     * Records all attempts in `email_logs`.
     */
    public function sendEmail(
        string $recipientEmail,
        string $subject,
        string $templateName,
        array $templateData,
        ?int $recipientUserId = null
    ): array {
        $mailEnabled = ($this->getSetting('mail_enabled', '0') === '1');
        $fromEmail   = $this->getSetting('mail_from_address', 'noreply@prayas-academy.org');
        $fromName    = $this->getSetting('mail_from_name', 'PRAYAS – The Automation Academy');

        // Render HTML and Plain-Text templates
        $bodyHtml = $this->renderHtmlTemplate($templateName, $templateData, $subject);
        $bodyText = $this->renderPlainTextFallback($templateName, $templateData, $subject);

        $deliveryStatus = 'QUEUED';
        $errorMessage = null;

        if (!$mailEnabled) {
            // Simulated delivery when live SMTP is disabled in settings
            $deliveryStatus = 'SIMULATED';
            $errorMessage = 'Email simulation active (mail_enabled=0). Message recorded to audit log.';
        } else {
            try {
                // Attempt standard PHP mail() transport
                $headers = [];
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
                $headers[] = "From: {$fromName} <{$fromEmail}>";
                $headers[] = "Reply-To: {$fromEmail}";
                $headers[] = 'X-Mailer: PRAYAS-Portal/1.0';

                $mailSent = @mail($recipientEmail, $subject, $bodyHtml, implode("\r\n", $headers));
                if ($mailSent) {
                    $deliveryStatus = 'SENT';
                } else {
                    $deliveryStatus = 'FAILED';
                    $errorMessage = 'Local mail transport returned false. Verify MTA / sendmail path configuration.';
                }
            } catch (Exception $e) {
                $deliveryStatus = 'FAILED';
                $errorMessage = $e->getMessage();
            }
        }

        // Persist to `email_logs`
        $logId = 0;
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `email_logs` 
                (`recipient_email`, `recipient_user_id`, `subject`, `template_name`, `body_html`, `body_text`, `delivery_status`, `error_message`, `sent_at`, `created_at`) 
                VALUES 
                (:email, :uid, :sub, :tpl, :html, :text, :status, :err, " . ($deliveryStatus === 'SENT' ? "NOW()" : "NULL") . ", NOW())");
            
            $stmt->execute([
                ':email'  => $recipientEmail,
                ':uid'    => $recipientUserId,
                ':sub'    => $subject,
                ':tpl'    => $templateName,
                ':html'   => $bodyHtml,
                ':text'   => $bodyText,
                ':status' => $deliveryStatus,
                ':err'    => $errorMessage
            ]);
            $logId = (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to log email to email_logs: " . $e->getMessage());
        }

        return [
            'success'         => ($deliveryStatus === 'SENT' || $deliveryStatus === 'SIMULATED'),
            'delivery_status' => $deliveryStatus,
            'log_id'          => $logId,
            'error'           => $errorMessage
        ];
    }

    // =========================================================================
    // EVENT DISPATCHERS
    // =========================================================================

    /**
     * Requirement Submitted Event Dispatcher
     */
    public function dispatchRequirementSubmitted(array $req, int $userId, int $companyId): void
    {
        $code = $req['requirement_id'] ?? 'REQ-DRAFT';
        $title = $req['title'] ?? 'Technical Requisition';
        $status = $req['status'] ?? 'SUBMITTED';

        if ($status === 'APPROVED') {
            // Auto-approved path
            $this->sendInApp(
                $userId,
                'REQUIREMENT_APPROVED',
                "Requirement Auto-Approved: {$code}",
                "Your requisition '{$title}' ({$code}) passed all validation criteria and has been approved for candidate matching.",
                "/views/industry/requirements.php",
                $companyId,
                'requirement',
                (int)($req['id'] ?? 0)
            );

            // Alert Super Admin & Recruitment Desk
            $this->broadcastToRole(
                'super_admin',
                'NEW_APPROVED_REQUIREMENT',
                "Requirement Auto-Approved: {$code}",
                "Requisition '{$title}' ({$code}) from company #{$companyId} was auto-approved and queued for ITI matching.",
                "/views/super_admin/dashboard.php",
                $companyId,
                'requirement',
                (int)($req['id'] ?? 0)
            );
        } else {
            // Under manual review path
            $this->sendInApp(
                $userId,
                'REQUIREMENT_UNDER_MANUAL_REVIEW',
                "Requirement Under Review: {$code}",
                "Your requisition '{$title}' ({$code}) was submitted and is pending verification by PRAYAS administrators.",
                "/views/industry/requirements.php",
                $companyId,
                'requirement',
                (int)($req['id'] ?? 0)
            );

            // Alert Super Admin
            $this->broadcastToRole(
                'super_admin',
                'REQUIREMENT_NEEDS_MANUAL_REVIEW',
                "Action Required: Requirement {$code} Needs Review",
                "Requisition '{$title}' ({$code}) from company #{$companyId} requires administrative vetting before sourcing begins.",
                "/views/super_admin/dashboard.php",
                $companyId,
                'requirement',
                (int)($req['id'] ?? 0)
            );
        }

        // Email Notification
        $userStmt = $this->pdo->prepare("SELECT email, full_name FROM `users` WHERE id = :uid LIMIT 1");
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();
        if ($user && !empty($user['email'])) {
            $tpl = ($status === 'APPROVED') ? 'requirement_approval' : 'requirement_submission';
            $subj = ($status === 'APPROVED') 
                ? "Requirement Auto-Approved: {$code} — PRAYAS" 
                : "Requirement Submitted for Review: {$code} — PRAYAS";

            $this->sendEmail($user['email'], $subj, $tpl, [
                'full_name'        => $user['full_name'],
                'requirement_code' => $code,
                'title'            => $title,
                'status'           => $status,
                'vacancies'        => $req['quantity_required'] ?? 0,
                'action_url'       => '/views/industry/requirements.php'
            ], $userId);
        }
    }

    /**
     * Requirement Needs Correction Dispatcher
     */
    public function dispatchRequirementNeedsCorrection(array $req, int $userId, int $companyId, array $errors): void
    {
        $code = $req['requirement_id'] ?? 'REQ-DRAFT';
        $title = $req['title'] ?? 'Technical Requisition';
        $errCount = count($errors);

        $this->sendInApp(
            $userId,
            'REQUIREMENT_NEEDS_CORRECTION',
            "Correction Required: {$code}",
            "Your requisition '{$title}' ({$code}) contains {$errCount} validation defect(s). Please review and correct the marked fields.",
            "/views/industry/requirement_form.php?id=" . ($req['id'] ?? 0),
            $companyId,
            'requirement',
            (int)($req['id'] ?? 0)
        );

        $userStmt = $this->pdo->prepare("SELECT email, full_name FROM `users` WHERE id = :uid LIMIT 1");
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();
        if ($user && !empty($user['email'])) {
            $this->sendEmail($user['email'], "Action Required: Requisition {$code} Needs Correction — PRAYAS", 'requirement_correction', [
                'full_name'        => $user['full_name'],
                'requirement_code' => $code,
                'title'            => $title,
                'errors'           => $errors,
                'action_url'       => '/views/industry/requirement_form.php?id=' . ($req['id'] ?? 0)
            ], $userId);
        }
    }

    /**
     * Validation Failed Alert Dispatcher
     * Triggered when automatic validation fails with critical/high severity errors.
     */
    public function dispatchValidationFailed(array $req, int $userId, int $companyId, array $errors): void
    {
        $code = $req['requirement_id'] ?? 'REQ-DRAFT';
        $title = $req['title'] ?? 'Technical Requisition';
        $reqId = (int)($req['id'] ?? 0);
        $errCount = count($errors);

        $actionUrl = "/views/industry/validation_summary.php?id={$reqId}";

        $this->sendInApp(
            $userId,
            'VALIDATION_FAILED',
            "Validation Failed: {$code}",
            "Automated validation failed for '{$title}' ({$code}) with {$errCount} critical issue(s). Review suggestions and correct errors to proceed.",
            $actionUrl,
            $companyId,
            'requirement',
            $reqId
        );

        $userStmt = $this->pdo->prepare("SELECT email, full_name FROM `users` WHERE id = :uid LIMIT 1");
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();
        if ($user && !empty($user['email'])) {
            $this->sendEmail($user['email'], "Validation Alert: Issues Detected in {$code} — PRAYAS", 'validation_failed', [
                'full_name'        => $user['full_name'],
                'requirement_code' => $code,
                'title'            => $title,
                'errors'           => $errors,
                'action_url'       => $actionUrl
            ], $userId);
        }
    }

    /**
     * Validation Warning Alert Dispatcher
     * Triggered when automatic validation passes with non-blocking advisory warnings.
     */
    public function dispatchValidationWarning(array $req, int $userId, int $companyId, array $warnings): void
    {
        $code = $req['requirement_id'] ?? 'REQ-DRAFT';
        $title = $req['title'] ?? 'Technical Requisition';
        $reqId = (int)($req['id'] ?? 0);
        $warnCount = count($warnings);

        $actionUrl = "/views/industry/validation_summary.php?id={$reqId}";

        $this->sendInApp(
            $userId,
            'VALIDATION_WARNING',
            "Validation Advisory: {$code}",
            "Requisition '{$title}' ({$code}) generated {$warnCount} advisory warning(s). Recommended to review suggestions before dispatching.",
            $actionUrl,
            $companyId,
            'requirement',
            $reqId
        );
    }

    /**
     * Requirement Under Manual Review Dispatcher
     * Triggered when volume > 50, salary < 12k, or turnaround < 7 days requires human vetting.
     */
    public function dispatchRequirementUnderReview(array $req, int $userId, int $companyId, array $reasons = []): void
    {
        $code = $req['requirement_id'] ?? 'REQ';
        $title = $req['title'] ?? 'Technical Requisition';
        $reqId = (int)($req['id'] ?? 0);
        $reasonStr = !empty($reasons) ? implode("; ", $reasons) : 'High volume or priority allocation audit.';

        // Alert Submitting User
        $this->sendInApp(
            $userId,
            'REQUIREMENT_UNDER_MANUAL_REVIEW',
            "Requirement Under Administrative Review: {$code}",
            "Your requisition '{$title}' ({$code}) is under review by PRAYAS administrators. Criteria: {$reasonStr}",
            "/views/industry/requirement_details.php?id={$reqId}",
            $companyId,
            'requirement',
            $reqId
        );

        // Alert Super Admin
        $this->broadcastToRole(
            'super_admin',
            'REQUIREMENT_NEEDS_MANUAL_REVIEW',
            "Action Required: Requisition {$code} Needs Review",
            "Requisition '{$title}' ({$code}) flagged for administrative vetting. Trigger: {$reasonStr}",
            "/views/super_admin/requirements.php?id={$reqId}",
            $companyId,
            'requirement',
            $reqId
        );
    }

    /**
     * Requirement Revised Dispatcher
     * Triggered when an existing requirement is edited and re-validated.
     */
    public function dispatchRequirementRevised(array $req, int $userId, int $companyId): void
    {
        $code = $req['requirement_id'] ?? 'REQ';
        $title = $req['title'] ?? 'Technical Requisition';
        $reqId = (int)($req['id'] ?? 0);

        $this->sendInApp(
            $userId,
            'REQUIREMENT_REVISED',
            "Requirement Revised: {$code}",
            "Requisition '{$title}' ({$code}) specifications were updated and re-validated.",
            "/views/industry/requirement_details.php?id={$reqId}",
            $companyId,
            'requirement',
            $reqId
        );
    }

    /**
     * Requirement Overdue Dispatcher
     * Triggered when application deadline or target joining date has elapsed.
     */
    public function dispatchRequirementOverdue(array $req, int $recipientUserId): void
    {
        $code = $req['requirement_id'] ?? 'REQ';
        $title = $req['title'] ?? 'Technical Requisition';
        $reqId = (int)($req['id'] ?? 0);
        $cid = (int)($req['company_id'] ?? 0);

        $this->sendInApp(
            $recipientUserId,
            'REQUIREMENT_OVERDUE',
            "Action Required: Requirement Expired/Overdue: {$code}",
            "Application deadline or milestone for '{$title}' ({$code}) has elapsed. Please review candidates or close the requisition.",
            "/views/industry/requirement_details.php?id={$reqId}",
            $cid,
            'requirement',
            $reqId
        );
    }

    /**
     * Company Verification Decision Dispatcher
     */
    public function dispatchCompanyStatusChanged(int $companyId, string $status, ?string $reason = null, ?int $changedBy = null): void
    {
        $compStmt = $this->pdo->prepare("SELECT company_name FROM `companies` WHERE id = :cid LIMIT 1");
        $compStmt->execute([':cid' => $companyId]);
        $comp = $compStmt->fetch();
        $compName = $comp['company_name'] ?? 'Industrial Partner';

        if ($status === 'Verified' || $status === 'Active') {
            $this->notifyCompanyAdmins(
                $companyId,
                'COMPANY_APPROVED',
                "Company Accreditation Approved",
                "Congratulations! '{$compName}' has been formally accredited on the PRAYAS Manpower Portal. You can now post live manpower requirements.",
                "/views/industry/dashboard.php",
                'company',
                $companyId
            );

            // Email primary HR
            $this->emailCompanyContact($companyId, "Company Accreditation Approved — PRAYAS", 'company_approval', [
                'company_name' => $compName,
                'status'       => $status,
                'action_url'   => '/views/industry/dashboard.php'
            ]);
        } elseif ($status === 'Rejected') {
            $this->notifyCompanyAdmins(
                $companyId,
                'COMPANY_REJECTED',
                "Company Accreditation Update",
                "Your registration for '{$compName}' could not be verified. Reason: " . ($reason ?? 'Incomplete documentation.'),
                "/views/industry/company_profile.php",
                'company',
                $companyId
            );

            $this->emailCompanyContact($companyId, "Company Accreditation Update — PRAYAS", 'company_rejection', [
                'company_name' => $compName,
                'reason'       => $reason ?? 'Incomplete statutory documentation.',
                'action_url'   => '/views/industry/company_profile.php'
            ]);
        }
    }

    /**
     * Deadline Reminder Dispatcher
     */
    public function dispatchDeadlineReminder(array $req, string $reminderType, string $targetDate, int $recipientUserId): bool
    {
        $code = $req['requirement_id'] ?? 'REQ';
        $title = $req['title'] ?? 'Requirement';
        $companyId = (int)($req['company_id'] ?? 0);
        $reqId = (int)($req['id'] ?? 0);

        $titles = [
            'DEADLINE_7_DAYS'      => "Application Deadline in 7 Days: {$code}",
            'DEADLINE_3_DAYS'      => "Application Deadline in 3 Days: {$code}",
            'DEADLINE_1_DAY'       => "Urgent: Application Deadline Tomorrow: {$code}",
            'DEADLINE_TODAY'       => "Action Required: Application Deadline Today: {$code}",
            'DEADLINE_APPROACHING' => "Application Deadline Approaching: {$code}",
            'INTERVIEW_3_DAYS'     => "Interview Round in 3 Days: {$code}",
            'INTERVIEW_TODAY'      => "Action Required: Interviews Scheduled Today: {$code}",
            'INTERVIEW_UPCOMING'   => "Interview Round Scheduled: {$code}",
            'JOINING_7_DAYS'       => "Target Joining Milestone in 7 Days: {$code}",
            'JOINING_TODAY'        => "Candidate Joining Date Today: {$code}",
            'JOINING_UPCOMING'     => "Candidate Joining Milestone: {$code}",
            'DEADLINE_OVERDUE'     => "Action Required: Requisition Expired: {$code}",
            'REQUIREMENT_EXPIRY'   => "Requisition Deadline Expired: {$code}"
        ];

        $messages = [
            'DEADLINE_7_DAYS'      => "The application deadline for '{$title}' ({$code}) is in 7 days ({$targetDate}). Review incoming applications.",
            'DEADLINE_3_DAYS'      => "The application deadline for '{$title}' ({$code}) is in 3 days ({$targetDate}). Prepare candidate shortlists.",
            'DEADLINE_1_DAY'       => "Urgent: The application window for '{$title}' ({$code}) closes tomorrow ({$targetDate}).",
            'DEADLINE_TODAY'       => "Action Required: The application deadline for '{$title}' ({$code}) is today ({$targetDate}).",
            'DEADLINE_APPROACHING' => "The application deadline for '{$title}' ({$code}) is on {$targetDate}. Please ensure shortlisting is finalized.",
            'INTERVIEW_3_DAYS'     => "Interviews for '{$title}' ({$code}) are scheduled in 3 days ({$targetDate}). Confirm plant panel availability.",
            'INTERVIEW_TODAY'      => "Action Required: Candidate interviews for '{$title}' ({$code}) are scheduled today ({$targetDate}).",
            'INTERVIEW_UPCOMING'   => "Interviews for '{$title}' ({$code}) are scheduled for {$targetDate}. Verify candidate invitations and plant venue.",
            'JOINING_7_DAYS'       => "Target candidate onboarding for '{$title}' ({$code}) is scheduled in 7 days ({$targetDate}).",
            'JOINING_TODAY'        => "Milestone: Candidate joining date for '{$title}' ({$code}) is today ({$targetDate}).",
            'JOINING_UPCOMING'     => "Target joining date for selected candidates on '{$title}' ({$code}) is {$targetDate}.",
            'DEADLINE_OVERDUE'     => "The application window for '{$title}' ({$code}) has expired on {$targetDate}. Please review or close requisition.",
            'REQUIREMENT_EXPIRY'   => "The application window for '{$title}' ({$code}) expired on {$targetDate}."
        ];

        $notifTitle = $titles[$reminderType] ?? "Milestone Alert: {$code}";
        $notifMsg   = $messages[$reminderType] ?? "Requirement milestone notice for {$code}.";

        $notifId = $this->sendInApp(
            $recipientUserId,
            $reminderType,
            $notifTitle,
            $notifMsg,
            "/views/industry/requirements.php",
            $companyId,
            'requirement',
            $reqId
        );

        return ($notifId > 0);
    }

    // =========================================================================
    // QUERY & MUTATION OPERATIONS
    // =========================================================================

    /**
     * Retrieve paginated notifications for a user with filters.
     */
    public function getUserNotifications(int $userId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, company_id, notification_type, title, message, action_url, entity_type, entity_id, is_read, read_at, status, created_at 
                FROM `notifications` 
                WHERE recipient_user_id = :uid";
        $params = [':uid' => $userId];

        if (isset($filters['status']) && in_array($filters['status'], ['unread', 'read'], true)) {
            $sql .= " AND is_read = :is_read";
            $params[':is_read'] = ($filters['status'] === 'read') ? 1 : 0;
        }

        if (!empty($filters['type'])) {
            $sql .= " AND notification_type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search1 OR message LIKE :search2)";
            $params[':search1'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total unread notifications for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE recipient_user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark a specific notification as read with strict ownership check.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `notifications` 
            SET `is_read` = 1, `status` = 'read', `read_at` = NOW() 
            WHERE `id` = :id AND `recipient_user_id` = :uid");
        $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->pdo->prepare("UPDATE `notifications` 
            SET `is_read` = 1, `status` = 'read', `read_at` = NOW() 
            WHERE `recipient_user_id` = :uid AND `is_read` = 0");
        $stmt->execute([':uid' => $userId]);
        return $stmt->rowCount();
    }

    /**
     * Delete notification with strict ownership verification.
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `notifications` WHERE `id` = :id AND `recipient_user_id` = :uid");
        $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================================
    // TEMPLATE RENDERING
    // =========================================================================

    private function renderHtmlTemplate(string $template, array $data, string $subject): string
    {
        $name = htmlspecialchars((string)($data['full_name'] ?? $data['company_name'] ?? 'Partner'));
        $bodyContent = '';

        switch ($template) {
            case 'company_approval':
                $bodyContent = "<p>Dear {$name},</p>
                <p>We are delighted to inform you that your enterprise profile for <strong>" . htmlspecialchars($data['company_name'] ?? '') . "</strong> has been officially approved and accredited by <strong>PRAYAS – The Automation Academy</strong>.</p>
                <p>You can now immediately log in to create and publish technical manpower requisitions across our accredited ITI student pool.</p>";
                break;

            case 'company_rejection':
                $reason = htmlspecialchars($data['reason'] ?? 'Statutory or documentation non-compliance.');
                $bodyContent = "<p>Dear {$name},</p>
                <p>Thank you for submitting your partnership application for <strong>" . htmlspecialchars($data['company_name'] ?? '') . "</strong>.</p>
                <p>During our preliminary accreditation audit, the application could not be verified due to the following rationale:</p>
                <blockquote style='background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; font-size: 14px;'>{$reason}</blockquote>
                <p>Please update your statutory credentials in the company profile portal to request re-evaluation.</p>";
                break;

            case 'requirement_approval':
                $code = htmlspecialchars($data['requirement_code'] ?? '');
                $title = htmlspecialchars($data['title'] ?? '');
                $vac = (int)($data['vacancies'] ?? 0);
                $bodyContent = "<p>Dear {$name},</p>
                <p>Your technical manpower requisition <strong>{$title} ({$code})</strong> has passed all multi-stage validation checks and has been <strong>APPROVED</strong>.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Requisition ID:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$code}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd; background: #f8f9fa;'><strong>Total Vacancies:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$vac} Technicians</td></tr>
                </table>
                <p>The candidate matching algorithm is actively scanning qualified ITI batches for your requisition.</p>";
                break;

            case 'requirement_submission':
            case 'requirement_manual_review':
                $code = htmlspecialchars($data['requirement_code'] ?? '');
                $title = htmlspecialchars($data['title'] ?? '');
                $bodyContent = "<p>Dear {$name},</p>
                <p>Your technical manpower requisition <strong>{$title} ({$code})</strong> has been successfully received by PRAYAS.</p>
                <p>Our academic recruitment committee is currently conducting final allocation checks. You will receive an immediate in-app and email alert upon final signoff.</p>";
                break;

            case 'requirement_correction':
            case 'validation_failed':
                $code = htmlspecialchars($data['requirement_code'] ?? '');
                $title = htmlspecialchars($data['title'] ?? '');
                $errList = '';
                if (!empty($data['errors']) && is_array($data['errors'])) {
                    $errList = '<ul>';
                    foreach ($data['errors'] as $fld => $msg) {
                        $errList .= '<li><strong>' . htmlspecialchars((string)$fld) . ':</strong> ' . htmlspecialchars((string)$msg) . '</li>';
                    }
                    $errList .= '</ul>';
                }
                $bodyContent = "<p>Dear {$name},</p>
                <p>Your technical requisition <strong>{$title} ({$code})</strong> encountered validation issues and requires attention before it can proceed to candidate sourcing.</p>
                {$errList}
                <p>Please click below to inspect the suggested corrections and update your requisition.</p>";
                break;

            default:
                $bodyContent = "<p>Dear {$name},</p><p>You have a new administrative notification on the PRAYAS Manpower Portal.</p>";
                break;
        }

        $actionBtn = '';
        if (!empty($data['action_url'])) {
            $url = htmlspecialchars($data['action_url']);
            $actionBtn = "<div style='text-align: center; margin: 25px 0;'>
                <a href='{$url}' style='background: #0d2847; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>View in PRAYAS Portal</a>
            </div>";
        }

        return "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333333;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'>
                <tr>
                    <td style='background: #0d2847; padding: 25px 30px; text-align: left;'>
                        <h2 style='color: #d4af37; margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px;'>PRAYAS</h2>
                        <div style='color: #ffffff; font-size: 12px; margin-top: 4px; text-transform: uppercase;'>The Automation Academy &bull; Industry Manpower Portal</div>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 30px; line-height: 1.6; font-size: 15px;'>
                        <h3 style='margin-top: 0; color: #0d2847; font-size: 18px;'>{$subject}</h3>
                        {$bodyContent}
                        {$actionBtn}
                        <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;'>
                        <p style='font-size: 12px; color: #777777; margin-bottom: 0;'>
                            This is an automated system notification from PRAYAS Industry Manpower Portal. Please do not reply directly to this email. For assistance, reach out to support@prayas-academy.org.
                        </p>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    private function renderPlainTextFallback(string $template, array $data, string $subject): string
    {
        $name = $data['full_name'] ?? $data['company_name'] ?? 'Partner';
        return "PRAYAS – The Automation Academy\n"
            . "Industry Manpower Requirement Portal\n"
            . "========================================\n\n"
            . "Subject: {$subject}\n\n"
            . "Dear {$name},\n\n"
            . "Please log in to the PRAYAS Manpower Portal to review your notifications and requirement updates.\n\n"
            . "Portal URL: " . ($data['action_url'] ?? '/views/auth/login.php') . "\n\n"
            . "Regards,\n"
            . "PRAYAS Academic Administration\n";
    }

    private function emailCompanyContact(int $companyId, string $subject, string $template, array $data): void
    {
        $stmt = $this->pdo->prepare("SELECT hr_contact_email, hr_contact_name FROM `companies` WHERE id = :cid LIMIT 1");
        $stmt->execute([':cid' => $companyId]);
        $comp = $stmt->fetch();
        if ($comp && !empty($comp['hr_contact_email'])) {
            $data['full_name'] = $comp['hr_contact_name'] ?? 'HR Manager';
            $this->sendEmail($comp['hr_contact_email'], $subject, $template, $data);
        }
    }
}
