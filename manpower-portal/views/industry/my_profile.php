<?php
/**
 * Industry User Personal Profile
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'My Profile — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';

requireRole(['industry_admin', 'industry_user']);

$pdo = Database::getConnection();
$userId = getUserId();
$companyId = getCompanyId();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $name  = trim((string)($_POST['full_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if (strlen($name) < 2) {
        $_SESSION['flash_error'] = 'Full name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE `users` SET `full_name` = :name, `phone` = :phone, `updated_at` = NOW() WHERE `id` = :uid");
        $stmt->execute([':name' => $name, ':phone' => $phone, ':uid' => $userId]);
        $_SESSION['full_name'] = $name;
        $_SESSION['flash_success'] = 'Profile details updated successfully.';
    }
    header("Location: /views/industry/my_profile.php");
    exit;
}

$userStmt = $pdo->prepare("SELECT u.*, r.label AS role_label, c.company_name, c.verification_status 
    FROM `users` u 
    JOIN `roles` r ON u.role_id = r.id 
    LEFT JOIN `companies` c ON u.company_id = c.id 
    WHERE u.id = :uid LIMIT 1");
$userStmt->execute([':uid' => $userId]);
$user = $userStmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>My User Profile</h4>
                <p class="text-muted small mb-0">Manage your personal credentials, contact info, and security preferences.</p>
            </div>
            <div>
                <a href="/views/industry/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row g-4 w-100 mx-0">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name'] ?? '') ?></h5>
                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($user['role_label'] ?? '') ?></span>
                    <div class="small text-muted mb-3"><?= htmlspecialchars($user['company_name'] ?? '') ?></div>
                    <div class="border-top pt-3 small text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Account Status:</span>
                            <span class="badge bg-success"><?= htmlspecialchars($user['status'] ?? 'Active') ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Member Since:</span>
                            <span><?= htmlspecialchars(substr($user['created_at'] ?? '', 0, 10)) ?></span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3 p-3">
                    <h6 class="fw-bold small mb-2"><i class="bi bi-shield-lock me-1 text-primary"></i>Password & Session Security</h6>
                    <p class="small text-muted mb-3">Manage your authentication credentials or end your active portal session.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= app_url('/views/auth/change_password.php') ?>" class="btn btn-outline-primary btn-sm text-start">
                            <i class="bi bi-key me-2"></i>Change Password
                        </a>
                        <a href="<?= app_url('/views/auth/logout.php') ?>" class="btn btn-outline-danger btn-sm text-start">
                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out of Account
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0">Edit Profile Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="/views/industry/my_profile.php">
                            <?= renderCSRFField() ?>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email Address (Login Identity)</label>
                                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                                <div class="form-text small">Email identity cannot be changed self-service. Contact PRAYAS support.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Contact Mobile Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Associated Company</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['company_name'] ?? '') ?> (<?= htmlspecialchars($user['verification_status'] ?? '') ?>)" readonly>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

