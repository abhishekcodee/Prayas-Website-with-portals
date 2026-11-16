<?php
/**
 * Professional Enterprise Login Portal
 * Organization: PRAYAS – The Automation Academy
 * Design System: Clean Corporate Enterprise Standard
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$error   = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$warning = $_GET['warning'] ?? '';

// If already authenticated, redirect to appropriate role dashboard
if (isAuthenticated()) {
    $role = getRoleName();
    $target = match ($role) {
        'super_admin'       => app_url('/views/super_admin/dashboard.php'),
        'industry_admin'    => app_url('/views/industry/dashboard.php'),
        'recruitment_admin' => app_url('/views/recruitment_admin/dashboard.php'),
        'recruiter'         => app_url('/views/recruiter/dashboard.php'),
        default             => app_url('/views/auth/login.php')
    };
    if ($target !== app_url('/views/auth/login.php')) {
        header("Location: {$target}");
        exit;
    }
}

// Process standard credentials POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $auth = new AuthController();
        $result = $auth->login($email, $password);

        if ($result['success']) {
            header("Location: " . app_url($result['redirect']));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — PRAYAS Industry Manpower Portal</title>
    <link rel="icon" type="image/png" href="<?= app_url('/assets/images/favicon.png') ?>">
    <!-- Google Fonts: Urbanist & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('/assets/css/dashboard.css') ?>?v=2.2.0">
    <style>
        :root {
            --primary-coral: #EF6B6B;
            --primary-hover: #E25555;
            --deep-navy: #0F172A;
            --border-color: #E2E8F0;
        }
        body {
            font-family: 'Urbanist', 'Inter', sans-serif;
            background: linear-gradient(135deg, #090E1A 0%, #0F172A 50%, #1E293B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #1E293B;
        }
        .login-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.12);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            padding: 36px 32px 20px 32px;
            text-align: center;
            border-bottom: 1px solid #F1F5F9;
        }
        .brand-logo-img {
            max-height: 48px;
            width: auto;
            margin-bottom: 12px;
        }
        .login-body {
            padding: 28px 32px 32px 32px;
        }
        .form-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .input-group-custom .input-group-text {
            background-color: #F8FAFC;
            border-color: #E2E8F0;
            color: #64748B;
            border-radius: 10px 0 0 10px;
            padding: 10px 14px;
        }
        .input-group-custom .form-control {
            border-color: #E2E8F0;
            border-radius: 0 10px 10px 0;
            padding: 10px 14px;
            font-size: 0.92rem;
            color: #0F172A;
        }
        .input-group-custom .form-control:focus {
            border-color: var(--primary-coral);
            box-shadow: 0 0 0 3px rgba(239, 107, 107, 0.15);
        }
        .btn-signin {
            background-color: var(--primary-coral);
            border-color: var(--primary-coral);
            color: #FFFFFF;
            font-weight: 700;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            width: 100%;
        }
        .btn-signin:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(239, 107, 107, 0.35);
        }
        .login-footer {
            background: #F8FAFC;
            padding: 16px 32px;
            border-top: 1px solid #F1F5F9;
            text-align: center;
            font-size: 0.84rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Header -->
    <div class="login-header">
        <a href="<?= app_url('/index.php') ?>" class="text-decoration-none">
            <img src="<?= app_url('/assets/images/logo.png') ?>" alt="PRAYAS – The Automation Academy" class="brand-logo-img">
        </a>
        <h4 class="fw-bold mb-1" style="color: var(--deep-navy); letter-spacing: -0.3px;">Industry Manpower Portal</h4>
        <p class="text-muted small mb-0">Sign in to your authorized portal</p>
    </div>

    <!-- Body -->
    <div class="login-body">
        
        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($warning)): ?>
            <div class="alert alert-warning alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i><?= e($warning) ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Credentials Form -->
        <form method="POST" action="<?= app_url('/views/auth/login.php') ?>" id="loginForm" novalidate>
            <?= renderCSRFField() ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group input-group-custom">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="name@company.com">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="<?= app_url('/views/auth/forgot_password.php') ?>" class="micro-text text-decoration-none fw-semibold" style="color: var(--primary-coral);">Forgot?</a>
                </div>
                <div class="input-group input-group-custom">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-end-0" id="password" name="password" required placeholder="••••••••">
                    <button class="btn btn-outline-secondary border-start-0 border-light-subtle bg-white text-muted px-3" type="button" id="togglePasswordBtn" title="Toggle password visibility">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-signin">
                    Sign In
                </button>
            </div>
        </form>

    </div>

    <!-- Footer -->
    <div class="login-footer">
        <span class="text-muted">New Industrial Partner?</span>
        <a href="<?= app_url('/views/auth/register.php') ?>" class="fw-semibold text-decoration-none ms-1" style="color: var(--deep-navy);">
            Register Company
        </a>
    </div>
</div>

<div class="position-fixed bottom-0 start-50 translate-middle-x pb-3 text-center text-white-50 micro-text">
    &copy; <?= date('Y') ?> PRAYAS – The Automation Academy. All Rights Reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password Visibility Toggle
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.className = isPassword ? 'bi bi-eye-slash text-danger' : 'bi bi-eye text-muted';
        });
    }
</script>
</body>
</html>
