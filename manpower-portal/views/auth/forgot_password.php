<?php
/**
 * Forgot Password Request View
 * Organization: PRAYAS – The Automation Academy
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$error = '';
$success = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $email = (string)($_POST['email'] ?? '');
    $auth = new AuthController();
    $result = $auth->requestPasswordReset($email);

    if ($result['success']) {
        $success = $result['message'];
        $devLink = $result['dev_link'] ?? '';
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
    <title>Forgot Password — PRAYAS</title>
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
<body class="d-flex align-items-center justify-content-center py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            
            <div class="card auth-card">
                <div class="auth-header">
                    <img src="/assets/images/logo.png" alt="PRAYAS The Automation Academy" class="brand-logo mb-2">
                    <h5 class="fw-bold mb-1" style="color: var(--prayas-navy);">Reset Password</h5>
                    <p class="text-muted small mb-0">Enter your registered email address</p>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success small mb-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
                        </div>
                        <?php if (!empty($devLink)): ?>
                            <div class="alert alert-info small" style="word-break: break-all;">
                                <strong>[Development Notice: SMTP Offline]</strong><br>
                                Use this generated token link to reset your password:<br>
                                <a href="<?= e($devLink) ?>" class="fw-bold text-decoration-underline">Reset Password Link</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-3">We will verify your account and provide a secure, time-limited password recovery token.</p>
                        
                        <form method="POST" action="/views/auth/forgot_password.php" novalidate>
                            <?= renderCSRFField() ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="name@company.com">
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-prayas">Send Reset Instructions</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-light text-center py-3 border-top">
                    <a href="/views/auth/login.php" class="small text-decoration-none" style="color: var(--prayas-navy);"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
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

