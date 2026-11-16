<?php
/**
 * Super Admin System Settings & Platform Configuration
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'System Settings — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

requireRole('super_admin');

$pdo = Database::getConnection();

// Handle Settings Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $settingsToUpdate = [
        'org_name'                    => trim((string)($_POST['org_name'] ?? 'PRAYAS – The Automation Academy')),
        'portal_title'                => trim((string)($_POST['portal_title'] ?? '')),
        'min_candidate_age'           => (string)max(18, (int)($_POST['min_candidate_age'] ?? 18)),
        'auto_approval_max_quantity'  => (string)max(1, (int)($_POST['auto_approval_max_quantity'] ?? 50)),
        'session_timeout_minutes'     => (string)max(5, (int)($_POST['session_timeout_minutes'] ?? 30)),
        'matching_threshold_tier1'    => (string)floatval($_POST['matching_threshold_tier1'] ?? 85.0),
        'matching_threshold_tier2'    => (string)floatval($_POST['matching_threshold_tier2'] ?? 70.0),
        'matching_threshold_min'      => (string)floatval($_POST['matching_threshold_min'] ?? 50.0),
        'buffer_selection_percentage' => (string)max(0, (int)($_POST['buffer_selection_percentage'] ?? 20))
    ];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");

        foreach ($settingsToUpdate as $k => $v) {
            $stmt->execute([':key' => $k, ':val' => $v]);
        }

        // Audit Log
        $auditStmt = $pdo->prepare("INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`, `details_json`) 
            VALUES (:uid, 'SYSTEM_SETTINGS_UPDATED', :ip, :details)");
        $auditStmt->execute([
            ':uid'     => getUserId(),
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':details' => json_encode($settingsToUpdate)
        ]);

        $pdo->commit();
        $_SESSION['flash_success'] = 'System settings updated and saved successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_error'] = 'Failed to update system settings: ' . $e->getMessage();
    }

    header("Location: /views/super_admin/system_settings.php");
    exit;
}

// Fetch all settings
$rawSettings = $pdo->query("SELECT setting_key, setting_value, description FROM `system_settings`")->fetchAll();
$settings = [];
foreach ($rawSettings as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="p-4">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--prayas-navy);">Platform Settings & Governance Thresholds</h3>
                <p class="text-muted small mb-0">Configure operational business rules, compliance cutoffs, matching tiers and branding identity</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="">
                            <?= renderCSRFField() ?>

                            <!-- Branding Configuration -->
                            <h5 class="fw-bold mb-3" style="color: var(--prayas-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                1. Organization & Portal Branding
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Organization Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="org_name" value="<?= e($settings['org_name'] ?? 'PRAYAS – The Automation Academy') ?>" required>
                                    <div class="form-text small">Global organization ownership and legal entity.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Portal Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="portal_title" value="<?= e($settings['portal_title'] ?? 'Industry Manpower Requirement & Recruitment Portal') ?>" required>
                                </div>
                            </div>

                            <!-- Statutory & Operational Safeguards -->
                            <h5 class="fw-bold mb-3" style="color: var(--prayas-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                2. Statutory Compliance & Workflow Controls
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Min. Candidate Age (Years) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="min_candidate_age" value="<?= e($settings['min_candidate_age'] ?? '18') ?>" min="18" max="25" required>
                                    <div class="form-text small">Factories Act statutory minimum (min 18).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Auto-Approval Ceiling (Openings)</label>
                                    <input type="number" class="form-control" name="auto_approval_max_quantity" value="<?= e($settings['auto_approval_max_quantity'] ?? '50') ?>" min="1" max="200" required>
                                    <div class="form-text small">Requisitions &gt; this limit require manual review.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Idle Session Timeout (Minutes)</label>
                                    <input type="number" class="form-control" name="session_timeout_minutes" value="<?= e($settings['session_timeout_minutes'] ?? '30') ?>" min="5" max="120" required>
                                </div>
                            </div>

                            <!-- Matching Algorithm Cutoffs -->
                            <h5 class="fw-bold mb-3" style="color: var(--prayas-navy); border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                3. Candidate Matching Scoring Cutoffs (0 to 100)
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tier 1 Cutoff (Top Match %)</label>
                                    <input type="number" step="0.1" class="form-control" name="matching_threshold_tier1" value="<?= e($settings['matching_threshold_tier1'] ?? '85.0') ?>" min="50" max="100" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tier 2 Cutoff (Strong Match %)</label>
                                    <input type="number" step="0.1" class="form-control" name="matching_threshold_tier2" value="<?= e($settings['matching_threshold_tier2'] ?? '70.0') ?>" min="40" max="90" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Minimum Display Cutoff (%)</label>
                                    <input type="number" step="0.1" class="form-control" name="matching_threshold_min" value="<?= e($settings['matching_threshold_min'] ?? '50.0') ?>" min="30" max="70" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Buffer Selection Allowance (%)</label>
                                    <input type="number" class="form-control" name="buffer_selection_percentage" value="<?= e($settings['buffer_selection_percentage'] ?? '20') ?>" min="0" max="50" required>
                                    <div class="form-text small">Extra candidates selectable to absorb no-shows.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4" style="background: var(--prayas-navy); border-color: var(--prayas-navy);">
                                    <i class="bi bi-check-lg me-1"></i> Save Platform Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

