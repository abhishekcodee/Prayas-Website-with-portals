<?php
/**
 * Industry Partner Registration View
 * Organization: PRAYAS – The Automation Academy
 * Senior UI/UX Enterprise Design System
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$error = '';
$fieldErrors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $auth = new AuthController();
    $result = $auth->registerIndustry($_POST);

    if ($result['success']) {
        header("Location: " . app_url("/views/auth/login.php?success=" . urlencode($result['message'])));
        exit;
    } else {
        $error = $result['message'];
        $fieldErrors = $result['errors'] ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Onboarding — PRAYAS Industry Portal</title>
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
                radial-gradient(circle at 10% 10%, rgba(239, 68, 68, 0.16) 0%, transparent 45%),
                radial-gradient(circle at 90% 90%, rgba(37, 99, 235, 0.14) 0%, transparent 50%),
                linear-gradient(180deg, #070B14 0%, #0B1120 100%);
            min-height: 100vh;
            color: #E2E8F0;
            padding: 32px 16px 48px 16px;
        }

        .portal-topbar {
            max-width: 900px;
            margin: 0 auto 24px auto;
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
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.18);
        }

        .reg-card {
            background: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
            color: var(--text-heading);
        }

        .reg-header {
            background: #FAFAFC;
            border-bottom: 1px solid #E2E8F0;
            padding: 32px 36px;
            text-align: center;
            position: relative;
        }

        .reg-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #EF4444, #3B82F6, #10B981);
        }

        .brand-logo-img {
            max-height: 48px;
            width: auto;
            margin-bottom: 12px;
        }

        .reg-body {
            padding: 36px;
        }

        .section-badge-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
            font-weight: 800;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #F1F5F9;
        }

        .section-num-pill {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: rgba(239, 68, 68, 0.12);
            color: var(--brand-primary);
            font-size: 0.78rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .form-label-custom {
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control-custom, .form-select-custom {
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.92rem;
            color: #0F172A;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus, .form-select-custom:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
            outline: none;
        }

        .btn-portal-submit {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border: none;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 1rem;
            padding: 14px 24px;
            border-radius: 12px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
        }

        .btn-portal-submit:hover {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45);
            transform: translateY(-1px);
        }

        .reg-footer {
            background: #F8FAFC;
            padding: 18px 36px;
            border-top: 1px solid #F1F5F9;
            text-align: center;
            font-size: 0.88rem;
        }

        .reg-footer a {
            color: #0F172A;
            font-weight: 700;
            text-decoration: none;
        }

        .reg-footer a:hover {
            color: var(--brand-primary);
        }
    </style>
</head>
<body>

    <div class="portal-topbar">
        <a href="<?= app_url('/views/auth/login.php') ?>" class="back-link">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Sign In</span>
        </a>
        <div class="text-white-50" style="font-size: 0.82rem;">
            Enterprise Partner Onboarding
        </div>
    </div>

    <div class="reg-card">
        <div class="reg-header">
            <a href="<?= app_url('/index.html') ?>" class="text-decoration-none">
                <img src="<?= app_url('/assets/images/logo.png') ?>" alt="PRAYAS The Automation Academy" class="brand-logo-img">
            </a>
            <h3 class="fw-bold mb-1" style="color: var(--text-heading); letter-spacing: -0.3px;">Enterprise Partner Onboarding</h3>
            <p class="text-muted small mb-0">Register your organization to post technical vacancies and source certified ITI manpower</p>
        </div>

        <div class="reg-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-4 shadow-sm d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0"></i>
                    <div><?= e($error) ?></div>
                    <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= app_url('/views/auth/register.php') ?>" novalidate>
                <?= renderCSRFField() ?>

                <!-- 1. Corporate Identity -->
                <div class="section-badge-header">
                    <span class="section-num-pill">1</span>
                    <span>Organization Details</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label-custom">Company / Plant Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['company_name']) ? 'is-invalid' : '' ?>" name="company_name" value="<?= e($_POST['company_name'] ?? '') ?>" required placeholder="e.g. Tata Motors Ltd. / Bharat Forge">
                        <?php if (isset($fieldErrors['company_name'])): ?><div class="invalid-feedback"><?= e($fieldErrors['company_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label-custom">Registration / CIN / GSTIN</label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['registration_number']) ? 'is-invalid' : '' ?>" name="registration_number" value="<?= e($_POST['registration_number'] ?? '') ?>" placeholder="e.g. U29100MH2020PTC123456">
                        <?php if (isset($fieldErrors['registration_number'])): ?><div class="invalid-feedback"><?= e($fieldErrors['registration_number']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Industry Sector <span class="text-danger">*</span></label>
                        <select class="form-select form-select-custom <?= isset($fieldErrors['industry_type']) ? 'is-invalid' : '' ?>" name="industry_type" required>
                            <option value="">-- Select Industry Sector --</option>
                            <?php
                            $sectors = ['Automotive & Auto Components', 'Industrial Automation & Robotics', 'Machinery & Heavy Engineering', 'Electrical & Electronics Manufacturing', 'Precision Tooling & CNC Machining', 'Process & Chemical Plants', 'Solar & Renewable Energy', 'Other Manufacturing / Assembly'];
                            foreach ($sectors as $s) {
                                $selected = (($_POST['industry_type'] ?? '') === $s) ? 'selected' : '';
                                echo "<option value=\"" . e($s) . "\" $selected>" . e($s) . "</option>";
                            }
                            ?>
                        </select>
                        <?php if (isset($fieldErrors['industry_type'])): ?><div class="invalid-feedback"><?= e($fieldErrors['industry_type']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">State <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['state']) ? 'is-invalid' : '' ?>" name="state" value="<?= e($_POST['state'] ?? 'Maharashtra') ?>" required>
                        <?php if (isset($fieldErrors['state'])): ?><div class="invalid-feedback"><?= e($fieldErrors['state']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label-custom">Plant / Office Address <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-custom <?= isset($fieldErrors['address']) ? 'is-invalid' : '' ?>" name="address" rows="2" required placeholder="Complete facility or corporate headquarters address"><?= e($_POST['address'] ?? '') ?></textarea>
                        <?php if (isset($fieldErrors['address'])): ?><div class="invalid-feedback"><?= e($fieldErrors['address']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">District <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['district']) ? 'is-invalid' : '' ?>" name="district" value="<?= e($_POST['district'] ?? '') ?>" required placeholder="e.g. Pune, Nashik, Aurangabad">
                        <?php if (isset($fieldErrors['district'])): ?><div class="invalid-feedback"><?= e($fieldErrors['district']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">City / Industrial Zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['city']) ? 'is-invalid' : '' ?>" name="city" value="<?= e($_POST['city'] ?? '') ?>" required placeholder="e.g. Chakan, Bhosari, Talegaon">
                        <?php if (isset($fieldErrors['city'])): ?><div class="invalid-feedback"><?= e($fieldErrors['city']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">PIN Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['pincode']) ? 'is-invalid' : '' ?>" name="pincode" value="<?= e($_POST['pincode'] ?? '') ?>" required placeholder="6-digit PIN code">
                        <?php if (isset($fieldErrors['pincode'])): ?><div class="invalid-feedback"><?= e($fieldErrors['pincode']) ?></div><?php endif; ?>
                    </div>
                </div>

                <!-- 2. Primary HR Contact -->
                <div class="section-badge-header">
                    <span class="section-num-pill">2</span>
                    <span>Primary HR / Talent Acquisition Lead</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom <?= isset($fieldErrors['hr_contact_name']) ? 'is-invalid' : '' ?>" name="hr_contact_name" value="<?= e($_POST['hr_contact_name'] ?? '') ?>" required placeholder="Contact Person Name">
                        <?php if (isset($fieldErrors['hr_contact_name'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Official Corporate Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-custom <?= isset($fieldErrors['hr_contact_email']) ? 'is-invalid' : '' ?>" name="hr_contact_email" value="<?= e($_POST['hr_contact_email'] ?? '') ?>" required placeholder="hr@company.com">
                        <?php if (isset($fieldErrors['hr_contact_email'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Direct Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control form-control-custom <?= isset($fieldErrors['hr_contact_phone']) ? 'is-invalid' : '' ?>" name="hr_contact_phone" value="<?= e($_POST['hr_contact_phone'] ?? '') ?>" required placeholder="10-digit mobile number">
                        <?php if (isset($fieldErrors['hr_contact_phone'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_phone']) ?></div><?php endif; ?>
                    </div>
                </div>

                <!-- 3. Portal Credentials -->
                <div class="section-badge-header">
                    <span class="section-num-pill">3</span>
                    <span>Portal Access Security</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label-custom">Set Account Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-custom <?= isset($fieldErrors['password']) ? 'is-invalid' : '' ?>" name="password" required placeholder="Minimum 8 characters (letters & numbers)">
                        <?php if (isset($fieldErrors['password'])): ?><div class="invalid-feedback"><?= e($fieldErrors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-custom <?= isset($fieldErrors['confirm_password']) ? 'is-invalid' : '' ?>" name="confirm_password" required placeholder="Re-enter password">
                        <?php if (isset($fieldErrors['confirm_password'])): ?><div class="invalid-feedback"><?= e($fieldErrors['confirm_password']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-portal-submit">
                        <span>Submit Enterprise Registration</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="reg-footer">
            <span class="text-muted">Already an approved industrial partner?</span>
            <a href="<?= app_url('/views/auth/login.php') ?>" class="ms-1">Sign In Here &rarr;</a>
        </div>
    </div>

    <div class="text-center mt-4 text-white-50" style="font-size: 0.78rem;">
        &copy; <?= date('Y') ?> PRAYAS – The Automation Academy. All Rights Reserved.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>