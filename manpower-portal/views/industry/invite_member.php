<?php
/**
 * Invite Industry Team Member
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Invite Team Member — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole('industry_admin');

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: /views/auth/login.php");
    exit;
}

$industryCtrl = new IndustryController();
$invitationResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $payload = [
        'name'  => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'role'  => $_POST['role'] ?? 'industry_user'
    ];

    $res = $industryCtrl->inviteTeamMember($companyId, getUserId(), $payload);
    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        $invitationResult = $res;
    } else {
        $_SESSION['flash_error'] = $res['message'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-person-plus me-2 text-primary"></i>Invite Team Member</h4>
                <p class="text-muted small mb-0">Issue a secure single-use invitation to plant HR coordinators or recruitment evaluators.</p>
            </div>
            <div>
                <a href="/views/industry/team_members.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Team Members
                </a>
            </div>
        </div>

        <?php if ($invitationResult && !empty($invitationResult['raw_token'])): ?>
            <div class="card border-success border-2 shadow-sm mb-4 w-100">
                <div class="card-body p-4 text-center">
                    <div class="text-success fs-1 mb-2"><i class="bi bi-check-circle-fill"></i></div>
                    <h5 class="fw-bold text-success">Invitation Generated Successfully!</h5>
                    <p class="text-muted small">Share the registration link below with the colleague to complete account activation:</p>
                    
                    <div class="input-group mb-3">
                        <input type="text" id="inviteLink" class="form-control text-center font-monospace bg-light" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/views/auth/register.php?token=" . htmlspecialchars($invitationResult['raw_token']) ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" id="copyInviteBtn" onclick="navigator.clipboard.writeText(document.getElementById('inviteLink').value); this.innerHTML='<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard me-1\'></i>Copy Link', 2500);">
                            <i class="bi bi-clipboard me-1"></i>Copy Link
                        </button>
                    </div>

                    <small class="text-muted d-block">This invitation token is single-use and will automatically expire in 7 days.</small>
                    <div class="mt-3">
                        <a href="/views/industry/team_members.php" class="btn btn-primary btn-sm">Done & Return to Team</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm w-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Invitee Information</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/views/industry/invite_member.php">
                    <?= renderCSRFField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Anand Joshi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Corporate Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="anand.joshi@company.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Mobile / Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 9811223344">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Company Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="industry_user" selected>Industry Team Member (View Candidates & Requirements)</option>
                                <option value="industry_admin">Industry Partner Admin (Full Company & Plant Management)</option>
                            </select>
                            <div class="form-text small">Industry administrators cannot assign system-level roles.</div>
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-4 mb-0">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        The invited member will be strictly affiliated with your registered company. They will not have access to data belonging to any other enterprise.
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                        <a href="/views/industry/team_members.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i>Generate Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

