<?php
/**
 * PRAYAS Enterprise Industry Manpower Portal
 * Designed with Senior Enterprise UI/UX Design System
 * 
 * Features:
 * - Fluid dual-column layout with enterprise brand showcase & trust metrics
 * - Ambient radial lighting and subtle tech-grid background
 * - Modern typography (Plus Jakarta Sans & Inter)
 * - Quick-fill demo credentials for evaluation/testing
 * - Interactive password reveal with accessibility states
 * - Preserves 100% backend AuthController, CSRF & Session security
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

// Process credentials POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your work email and password.';
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
    
    <!-- Enterprise Typography: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        :root {
            --brand-primary: #EF4444;
            --brand-primary-hover: #DC2626;
            --brand-primary-light: rgba(239, 68, 68, 0.08);
            --brand-navy-950: #070B14;
            --brand-navy-900: #0B1120;
            --brand-navy-800: #131D33;
            --brand-navy-700: #1E293B;
            --surface-card: #FFFFFF;
            --surface-muted: #F8FAFC;
            --border-light: #E2E8F0;
            --text-heading: #0F172A;
            --text-body: #475569;
            --text-muted: #94A3B8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--brand-navy-950);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(239, 68, 68, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(37, 99, 235, 0.14) 0%, transparent 50%),
                linear-gradient(180deg, #070B14 0%, #0B1120 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #E2E8F0;
            position: relative;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* Subtle Geometric Background Grid */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            pointer-events: none;
            z-index: 0;
        }

        /* Top Brand Navigation */
        .portal-topbar {
            position: relative;
            z-index: 10;
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            color: #94A3B8;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.25s ease;
        }

        .back-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateX(-2px);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #10B981;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 6px 14px;
            border-radius: 9999px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10B981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Main Container Layout */
        .portal-wrapper {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px 20px 48px 20px;
        }

        .enterprise-auth-card {
            width: 100%;
            max-width: 1080px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Left Showcase Column */
        .showcase-column {
            padding: 48px 44px;
            background: linear-gradient(145deg, rgba(19, 29, 51, 0.85) 0%, rgba(11, 17, 32, 0.95) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .showcase-column::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand-primary), #3B82F6, #10B981);
        }

        .brand-badge-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 32px;
        }

        .brand-logo-container {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #FFFFFF;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        .brand-logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .brand-text-title {
            font-size: 1.18rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.3px;
            margin: 0;
            line-height: 1.2;
        }

        .brand-text-sub {
            font-size: 0.8rem;
            color: var(--brand-primary);
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 0;
        }

        .hero-heading {
            font-size: 2rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
        }

        .hero-heading span {
            background: linear-gradient(135deg, #FF6B6B 0%, #FFA07A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 0.95rem;
            color: #94A3B8;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* Enterprise Trust Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 36px;
            padding: 20px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
        }

        .metric-item {
            text-align: center;
        }

        .metric-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }

        .metric-label {
            font-size: 0.72rem;
            color: #94A3B8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Pillars List */
        .pillars-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .pillar-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.88rem;
            color: #CBD5E1;
        }

        .pillar-icon {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: rgba(239, 68, 68, 0.15);
            color: #EF4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Right Form Column */
        .form-column {
            background: #FFFFFF;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--text-heading);
        }

        .form-header-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }

        .form-header-sub {
            font-size: 0.9rem;
            color: var(--text-body);
            margin-bottom: 28px;
        }


        /* Custom Input Groups */
        .form-label-custom {
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            margin-bottom: 18px;
        }

        .input-group-elevated:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }

        .input-icon-box {
            padding: 12px 14px;
            color: #94A3B8;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
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
            font-weight: 500;
            color: #0F172A;
            background: transparent;
            font-family: inherit;
        }

        .input-elevated-field::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }

        .btn-toggle-eye {
            background: transparent;
            border: none;
            padding: 12px 14px;
            color: #94A3B8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .btn-toggle-eye:hover {
            color: #334155;
        }

        /* Checkbox & Forgot Link */
        .form-aux-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.84rem;
        }

        .custom-check-label {
            color: #475569;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .custom-check-input {
            accent-color: var(--brand-primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: var(--brand-primary-hover);
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-portal-submit {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border: none;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.98rem;
            padding: 13px 20px;
            border-radius: 12px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .btn-portal-submit:hover {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45);
            transform: translateY(-1px);
        }

        .btn-portal-submit:active {
            transform: translateY(0);
        }

        /* Register Banner */
        .register-callout {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #F1F5F9;
            text-align: center;
            font-size: 0.88rem;
            color: #64748B;
        }

        .register-callout a {
            color: #0F172A;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
            transition: color 0.2s ease;
        }

        .register-callout a:hover {
            color: var(--brand-primary);
        }

        /* Security Assurance Bar */
        .security-assurance-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
            font-size: 0.76rem;
            color: #94A3B8;
            font-weight: 500;
        }

        .security-assurance-bar span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Portal Footer */
        .portal-footer {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 12px 20px 24px 20px;
            font-size: 0.78rem;
            color: #64748B;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .enterprise-auth-card {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .showcase-column {
                display: none;
            }

            .form-column {
                padding: 40px 32px;
            }

            .portal-topbar {
                padding: 16px 20px;
            }
        }

        @media (max-width: 575.98px) {
            .form-column {
                padding: 32px 20px;
            }

            .portal-wrapper {
                padding: 10px 14px 32px 14px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="portal-topbar">
        <a href="<?= app_url('/index.html') ?>" class="back-link">
            <i class="bi bi-arrow-left"></i>
            <span>Return to PRAYAS Website</span>
        </a>

        <div class="status-badge" title="Authenticated Industrial Gateway">
            <span class="status-dot"></span>
            <span>Enterprise Gateway • TLS 1.3</span>
        </div>
    </header>

    <!-- Main Auth Center Container -->
    <main class="portal-wrapper">
        <div class="enterprise-auth-card">
            
            <!-- Left: Showcase & Authority Pillar -->
            <div class="showcase-column">
                <div>
                    <div class="brand-badge-row">
                        <div class="brand-logo-container">
                            <img src="<?= app_url('/assets/images/logo.png') ?>" alt="PRAYAS Logo">
                        </div>
                        <div>
                            <h2 class="brand-text-title">PRAYAS</h2>
                            <p class="brand-text-sub">The Automation Academy</p>
                        </div>
                    </div>

                    <h1 class="hero-heading">
                        Connecting Industry with <span>Precision Talent.</span>
                    </h1>

                    <p class="hero-description">
                        India’s premier industrial placement gateway. Access rigorously evaluated, certified automation technicians and ITI engineering talent across manufacturing hubs.
                    </p>

                    <!-- Trust Metrics -->
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value">500+</div>
                            <div class="metric-label">Recruiters</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">12K+</div>
                            <div class="metric-label">Technicians</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">98.4%</div>
                            <div class="metric-label">SLA Rate</div>
                        </div>
                    </div>

                    <!-- Key Value Pillars -->
                    <div class="pillars-list">
                        <div class="pillar-row">
                            <div class="pillar-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                            <div>Direct automated scheduling for industrial campus hiring drives.</div>
                        </div>
                        <div class="pillar-row">
                            <div class="pillar-icon"><i class="bi bi-shield-check"></i></div>
                            <div>100% pre-vetted skill metrics, CNC/PLC certification & identity audits.</div>
                        </div>
                        <div class="pillar-row">
                            <div class="pillar-icon"><i class="bi bi-graph-up-arrow"></i></div>
                            <div>Comprehensive analytics for job postings, offers, and retention metrics.</div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-top border-white-10 d-flex justify-content-between align-items-center">
                    <span class="text-white-50" style="font-size: 0.78rem;">National Skill Development Aligned</span>
                    <span class="badge bg-secondary-subtle text-light-emphasis px-2 py-1" style="font-size: 0.72rem;">v2.4 Enterprise</span>
                </div>
            </div>

            <!-- Right: Authentication Form -->
            <div class="form-column">
                <div>
                    <h3 class="form-header-title">Industry Sign In</h3>
                    <p class="form-header-sub">Enter your authorized enterprise credentials to continue.</p>

                    <!-- Alerts -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 text-danger"></i>
                            <div><?= e($error) ?></div>
                            <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 text-success"></i>
                            <div><?= e($success) ?></div>
                            <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($warning)): ?>
                        <div class="alert alert-warning alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-info-circle-fill flex-shrink-0 text-warning"></i>
                            <div><?= e($warning) ?></div>
                            <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>


                    <!-- Auth Form -->
                    <form method="POST" action="<?= app_url('/views/auth/login.php') ?>" id="loginForm" novalidate>
                        <?= renderCSRFField() ?>

                        <!-- Email Field -->
                        <div class="mb-2">
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
                                    autocomplete="email"
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-2">
                            <div class="form-label-custom">
                                <span>Password</span>
                                <a href="<?= app_url('/views/auth/forgot_password.php') ?>" class="forgot-link">Forgot password?</a>
                            </div>
                            <div class="input-group-elevated">
                                <span class="input-icon-box"><i class="bi bi-shield-lock"></i></span>
                                <input 
                                    type="password" 
                                    class="input-elevated-field" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    placeholder="Enter your password" 
                                    autocomplete="current-password"
                                >
                                <button type="button" class="btn-toggle-eye" id="togglePasswordBtn" title="Toggle password visibility">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Aux row: Remember me -->
                        <div class="form-aux-row">
                            <label class="custom-check-label">
                                <input type="checkbox" name="remember" class="custom-check-input" checked>
                                <span>Remember this device for 30 days</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-portal-submit" id="submitBtn">
                            <span>Sign In to Portal</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Register Link -->
                    <div class="register-callout">
                        <span>New Industrial Partner?</span>
                        <a href="<?= app_url('/views/auth/register.php') ?>">Register Company Account &rarr;</a>
                    </div>

                    <!-- Security Assurance -->
                    <div class="security-assurance-bar">
                        <span><i class="bi bi-lock-fill"></i> 256-Bit SSL</span>
                        <span>•</span>
                        <span><i class="bi bi-shield-check"></i> SOC 2 Compliant</span>
                        <span>•</span>
                        <span><i class="bi bi-check2-circle"></i> ISO Certified</span>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="portal-footer">
        &copy; <?= date('Y') ?> PRAYAS – The Automation Academy. All Rights Reserved. Empowering Skill India Mission.
    </footer>

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
                toggleIcon.className = isPassword ? 'bi bi-eye-slash text-danger' : 'bi bi-eye';
            });
        }

    </script>
</body>
</html>