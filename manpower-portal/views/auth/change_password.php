<?php
/**
 * Change Password View
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

// Must be authenticated to change password
if (!isAuthenticated()) {
    header("Location: /views/auth/login.php?error=" . urlencode("Please log in to change your password."));
    exit;
}

$error = '';
$success = '';
$warning = $_GET['warning'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $currentPass = (string)($_POST['current_password'] ?? '');
    $newPass     = (string)($_POST['new_password'] ?? '');
    $confirmPass = (string)($_POST['confirm_password'] ?? '');

    $auth = new AuthController();
    $result = $auth->changePassword(getUserId(), $currentPass, $newPass, $confirmPass);

    if ($result['success']) {
        $role = getRoleName();
        $target = match ($role) {
            'super_admin'       => '/views/super_admin/dashboard.php',
            'industry_admin'    => '/views/industry/dashboard.php',
            'recruitment_admin' => '/views/recruitment_admin/dashboard.php',
            'recruiter'         => '/views/recruiter/dashboard.php',
            default             => '/views/auth/login.php'
        };
        header("Location: {$target}?success=" . urlencode("Password updated successfully."));
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — PRAYAS</title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --prayas-navy: #0F2042;
            --prayas-blue: #003366;
            --prayas-gold: #E89218;
            --prayas-slate: #1E293B;
        }
        body {
            background: linear-gradient(135deg, #0a1730 0%, #0F2042 100%);
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .auth-card {
            background: #ffffff;
            border: none;
            border-radius: 14px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .auth-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 24px;
            text-align: center;
        }
        .btn-prayas {
            background-color: var(--prayas-navy);
            border-color: var(--prayas-navy);
            color: #ffffff;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-prayas:hover {
            background-color: var(--prayas-blue);
            color: #ffffff;
        }
        .brand-logo {
            max-height: 48px;
            width: auto;
        }
    </style>
</head>
<body class="py-4">
<?php require_once __DIR__ . '/../../includes/portal_header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            
            <div class="card auth-card">
                <div class="auth-header">
                    <img src="/assets/images/logo.png" alt="PRAYAS The Automation Academy" class="brand-logo mb-2">
                    <h5 class="fw-bold mb-1" style="color: var(--prayas-navy);">Update Password</h5>
                    <p class="text-muted small mb-0">Logged in as <?= e($_SESSION['email'] ?? 'User') ?></p>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($warning)): ?>
                        <div class="alert alert-warning small mb-3" role="alert">
                            <i class="bi bi-shield-lock-fill me-2"></i><?= e($warning) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/views/auth/change_password.php" novalidate>
                        <?= renderCSRFField() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required autofocus placeholder="Enter current password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">New Password</label>
                            <input type="password" class="form-control" name="new_password" required placeholder="Min. 8 characters (letters + numbers)">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required placeholder="Re-enter new password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-prayas">Update Password</button>
                        </div>
                    </form>
                </div>

                <div class="card-footer bg-light text-center py-3 border-top">
                    <a href="/views/auth/logout.php" class="text-danger small text-decoration-none"><i class="bi bi-box-arrow-right me-1"></i>Sign Out</a>
                </div>
            </div>

            <div class="text-center mt-4 text-white-50 small">
                &copy; <?= date('Y') ?> PRAYAS – The Automation Academy. All Rights Reserved.
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

