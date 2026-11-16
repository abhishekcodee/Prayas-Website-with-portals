<?php
/**
 * CLI Scheduled Task: Manpower Requirement Deadline & Milestone Reminders
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP CLI + PDO + MySQL
 * 
 * Usage:
 *   php database/cron/requirement_deadline_reminders.php [--dry-run] [--verbose]
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'prayas_cron_secure_token')) {
    http_response_code(403);
    echo "Access denied. This script can only be executed via CLI or authorized cron webhook.\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// Lock File Protection: Single-Instance Execution Guard
// -----------------------------------------------------------------------------
$lockFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prayas_cron_reminders.lock';
$lockFp = fopen($lockFilePath, 'c+');

if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[!] Another instance of the PRAYAS Deadline Reminder Cron is currently active. Exiting cleanly.\n";
    if ($lockFp) fclose($lockFp);
    exit(0);
}

// Register cleanup handler to release lock
register_shutdown_function(function() use ($lockFp, $lockFilePath) {
    if (is_resource($lockFp)) {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
    if (file_exists($lockFilePath)) {
        @unlink($lockFilePath);
    }
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/notification_service.php';

$isDryRun = in_array('--dry-run', $argv ?? [], true);
$isVerbose = in_array('--verbose', $argv ?? [], true);

echo "========================================================\n";
echo " PRAYAS Portal — Requirement Deadline & Milestone Monitor\n";
echo " Started at: " . date('Y-m-d H:i:s') . "\n";
echo " Lock acquired: {$lockFilePath}\n";
echo " Mode: " . ($isDryRun ? "DRY-RUN (Simulated)" : "LIVE DISPATCH") . "\n";
echo "========================================================\n";

try {
    $pdo = Database::getConnection();
    $notifService = new NotificationService($pdo);

    // Active requirement statuses eligible for monitoring
    // Terminal statuses (DRAFT, NEEDS_CORRECTION, CANCELLED, CLOSED, FULFILLED) are strictly excluded
    $activeStatuses = ['APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED'];
    $placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));

    $today = new DateTime('today');
    $todayStr = $today->format('Y-m-d');

    $sql = "SELECT mr.id, mr.requirement_id, mr.company_id, mr.title, mr.status, 
                   mr.application_deadline, mr.interview_date, mr.target_joining_date,
                   mr.created_by, c.company_name, c.hr_contact_email, c.hr_contact_name
            FROM `manpower_requirements` mr
            JOIN `companies` c ON c.id = mr.company_id
            WHERE mr.status IN ($placeholders)
            ORDER BY mr.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($activeStatuses);
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[+] Scanned " . count($requirements) . " active requisitions.\n";

    $stats = [
        'scanned'            => count($requirements),
        'reminders_sent'     => 0,
        'duplicates_skipped' => 0,
        'errors'             => 0
    ];

    foreach ($requirements as $req) {
        $reqId = (int)$req['id'];
        $code  = $req['requirement_id'];
        $title = $req['title'];
        $cid   = (int)$req['company_id'];
        $creatorId = (int)($req['created_by'] ?? 0);

        // Find recipient user IDs (creator + active company admins)
        $recipients = [];
        if ($creatorId > 0) {
            $recipients[] = $creatorId;
        }

        $adminStmt = $pdo->prepare("SELECT u.id FROM `users` u 
            JOIN `roles` r ON r.id = u.role_id 
            WHERE u.company_id = :cid AND r.name = 'industry_admin' AND u.status = 'Active'");
        $adminStmt->execute([':cid' => $cid]);
        $compAdmins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($compAdmins as $caId) {
            $recipients[] = (int)$caId;
        }
        $recipients = array_unique($recipients);

        if (empty($recipients)) {
            continue;
        }

        // 1. Application Deadline Windows
        if (!empty($req['application_deadline']) && $req['application_deadline'] !== '0000-00-00') {
            $deadDate = new DateTime($req['application_deadline']);
            $diffDays = (int)$today->diff($deadDate)->format('%r%a'); // positive if in future, negative if past
            $deadStr = $req['application_deadline'];

            if ($diffDays === 7) {
                processReminder($pdo, $notifService, $req, 'DEADLINE_7_DAYS', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays === 3) {
                processReminder($pdo, $notifService, $req, 'DEADLINE_3_DAYS', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays === 1) {
                processReminder($pdo, $notifService, $req, 'DEADLINE_1_DAY', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays === 0) {
                processReminder($pdo, $notifService, $req, 'DEADLINE_TODAY', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays < 0) {
                processReminder($pdo, $notifService, $req, 'DEADLINE_OVERDUE', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays > 0 && $diffDays <= 3) {
                // Generic fallback window
                processReminder($pdo, $notifService, $req, 'DEADLINE_APPROACHING', $deadStr, $recipients, $isDryRun, $isVerbose, $stats);
            }
        }

        // 2. Interview Date Windows
        if (!empty($req['interview_date']) && $req['interview_date'] !== '0000-00-00') {
            $intDate = new DateTime($req['interview_date']);
            $diffDays = (int)$today->diff($intDate)->format('%r%a');
            $intStr = $req['interview_date'];

            if ($diffDays === 3) {
                processReminder($pdo, $notifService, $req, 'INTERVIEW_3_DAYS', $intStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays === 0) {
                processReminder($pdo, $notifService, $req, 'INTERVIEW_TODAY', $intStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays > 0 && $diffDays <= 2) {
                processReminder($pdo, $notifService, $req, 'INTERVIEW_UPCOMING', $intStr, $recipients, $isDryRun, $isVerbose, $stats);
            }
        }

        // 3. Joining Date Windows
        if (!empty($req['target_joining_date']) && $req['target_joining_date'] !== '0000-00-00') {
            $joinDate = new DateTime($req['target_joining_date']);
            $diffDays = (int)$today->diff($joinDate)->format('%r%a');
            $joinStr = $req['target_joining_date'];

            if ($diffDays === 7) {
                processReminder($pdo, $notifService, $req, 'JOINING_7_DAYS', $joinStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays === 0) {
                processReminder($pdo, $notifService, $req, 'JOINING_TODAY', $joinStr, $recipients, $isDryRun, $isVerbose, $stats);
            } elseif ($diffDays > 0 && $diffDays <= 3) {
                processReminder($pdo, $notifService, $req, 'JOINING_UPCOMING', $joinStr, $recipients, $isDryRun, $isVerbose, $stats);
            }
        }
    }

    echo "========================================================\n";
    echo " Execution Complete Summary:\n";
    echo "   Requisitions Scanned: {$stats['scanned']}\n";
    echo "   Reminders Sent:       {$stats['reminders_sent']}\n";
    echo "   Duplicates Skipped:   {$stats['duplicates_skipped']}\n";
    echo "   Errors:               {$stats['errors']}\n";
    echo " Finished at: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================================\n";
    exit(0);

} catch (Exception $e) {
    echo "[-] FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Idempotent Reminder Processor.
 * Guarantees zero duplicate alerts for same (requirement_id, reminder_type, target_date, recipient_user_id).
 */
function processReminder(
    PDO $pdo,
    NotificationService $notifService,
    array $req,
    string $type,
    string $targetDate,
    array $recipients,
    bool $isDryRun,
    bool $isVerbose,
    array &$stats
): void {
    $reqId = (int)$req['id'];
    $code  = $req['requirement_id'];

    foreach ($recipients as $uid) {
        // Idempotency check via deadline_reminder_logs
        $checkStmt = $pdo->prepare("SELECT id FROM `deadline_reminder_logs` 
            WHERE requirement_id = :rid AND reminder_type = :type AND target_date = :dt AND recipient_user_id = :uid LIMIT 1");
        $checkStmt->execute([
            ':rid'  => $reqId,
            ':type' => $type,
            ':dt'   => $targetDate,
            ':uid'  => $uid
        ]);

        if ($checkStmt->fetch()) {
            $stats['duplicates_skipped']++;
            if ($isVerbose) {
                echo "    [~] Skipped duplicate {$type} for req #{$code} user #{$uid}\n";
            }
            continue;
        }

        if ($isDryRun) {
            echo "    [SIMULATED] Would dispatch {$type} for req '{$code}' on {$targetDate} to user #{$uid}\n";
            $stats['reminders_sent']++;
            continue;
        }

        try {
            // Record in deadline_reminder_logs first to enforce atomic uniqueness
            $logStmt = $pdo->prepare("INSERT INTO `deadline_reminder_logs` 
                (`requirement_id`, `reminder_type`, `target_date`, `recipient_user_id`, `sent_at`) 
                VALUES (:rid, :type, :dt, :uid, NOW())");
            $logStmt->execute([
                ':rid'  => $reqId,
                ':type' => $type,
                ':dt'   => $targetDate,
                ':uid'  => $uid
            ]);

            // Dispatch in-app alert
            $notifService->dispatchDeadlineReminder($req, $type, $targetDate, $uid);
            $stats['reminders_sent']++;

            if ($isVerbose) {
                echo "    [+] Dispatched {$type} for req '{$code}' on {$targetDate} to user #{$uid}\n";
            }
        } catch (Exception $e) {
            $stats['errors']++;
            echo "    [-] Failed to send {$type} for req #{$code} to user #{$uid}: " . $e->getMessage() . "\n";
        }
    }
}
