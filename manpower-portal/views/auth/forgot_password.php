<?php
/**
 * Forgot Password Request View
 * Organization: PRAYAS – The Automation Academy
 * Senior UI/UX Enterprise Design System
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$error = '';
$success = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $email = trim((string)($_POST['email'] ?? ''));
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
    <title>Reset Password — PRAYAS Industry Portal</title>
    <link rel="icon" type="image/png" href="<?= app_url('/assets/images/favicon.png') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        :root {
            --brand-primary: #EF4444;
            --brand-primary-hover: #DC2626;
            --brand-navy-950: #070B14;
            --brand-navy-900: #0B1120;
            --text-heading: #0F172A;
            --text-body: #475569;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--brand-navy-950);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(239, 68, 68, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(37, 99, 235, 0.12) 0%, transparent 50%),
                linear-gradient(180deg, #070B14 0%, #0B1120 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #E2E8F0;
        }

        .auth-card {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            color: var(--text-heading);
        }

        .auth-header {
            padding: 36px 32px 20px 32px;
            text-align: center;
            border-bottom: 1px solid #F1F5F9;
            background: #FAFAFC;
        }

        .brand-logo-img {
            max-height: 48px;
            width: auto;
            margin-bottom: 14px;
        }

        .auth-body {
            padding: 28px 32px 32px 32px;
        }

        .form-label-custom {
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-group-elevated {
            position: relative;
            display: flex;
            align-items: center;
            border: 1.5px solid #E2E8F0;
            border-radius: 12px;
            background: #FFFFFF;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            margin-bottom: 20px;
        }

        .input-group-elevated:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }

        .input-icon-box {
            padding: 12px 14px;
            color: #94A3B8;
            font-size: 1.05rem;
        }

        .input-group-elevated:focus-within .input-icon-box {
            color: var(--brand-primary);
        }

        .input-elevated-field {
            border: none;
            outline: none;
            width: 100%;
            padding: 12px 14px 12px 0;
            font-size: 0.94rem;
            color: #0F172A;
            background: transparent;
            font-family: inherit;
        }

        .btn-portal-submit {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border: none;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 18px;
            border-radius: 12px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
        }

        .btn-portal-submit:hover {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45);
            transform: translateY(-1px);
        }

        .auth-footer {
            background: #F8FAFC;
            padding: 16px 32px;
            border-top: 1px solid #F1F5F9;
            text-align: center;
            font-size: 0.86rem;
        }

        .auth-footer a {
            color: #0F172A;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .auth-footer a:hover {
            color: var(--brand-primary);
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/portal_header.php'; ?>

<div class="auth-card">
    <div class="auth-header">
        <a href="<?= app_url('/index.html') ?>" class="text-decoration-none">
            <img src="<?= app_url('/assets/images/logo.png') ?>" alt="PRAYAS Logo" class="brand-logo-img">
        </a>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading); letter-spacing: -0.3px;">Reset Password</h4>
        <p class="text-muted small mb-0">Recover your industrial portal access</p>
    </div>

    <div class="auth-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0"></i>
                <div><?= e($error) ?></div>
                <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success small mb-3 p-3 shadow-sm rounded-3" role="alert">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <strong>Instructions Dispatched</strong>
                </div>
                <div><?= e($success) ?></div>
            </div>
            
            <?php if (!empty($devLink)): ?>
                <div class="alert alert-info small p-3 rounded-3" style="word-break: break-all;">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Development Token:</div>
                    <a href="<?= e($devLink) ?>" class="fw-bold text-decoration-underline text-primary">Proceed to Reset Password &rarr;</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted small mb-4">
                Enter your registered corporate email address. We will verify your account and provide a secure, time-limited password recovery token.
            </p>

            <form method="POST" action="<?= app_url('/views/auth/forgot_password.php') ?>" novalidate>
                <?= renderCSRFField() ?>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Corporate Email Address</label>
                    <div class="input-group-elevated">
                        <span class="input-icon-box"><i class="bi bi-envelope"></i></span>
                        <input 
                            type="email" 
                            class="input-elevated-field" 
                            id="email" 
                            name="email" 
                            value="<?= e($_POST['email'] ?? '') ?>" 
                            required 
                            autofocus 
                            placeholder="name@company.com"
                        >
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-portal-submit">
                        <span>Send Recovery Link</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="auth-footer">
        <a href="<?= app_url('/views/auth/login.php') ?>">
            <i class="bi bi-arrow-left me-1"></i> Return to Portal Sign In
        </a>
    </div>
</div>

<div class="text-center mt-4 text-white-50" style="font-size: 0.78rem;">
    &copy; <?= date('Y') ?> PRAYAS – The Automation Academy. All Rights Reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>