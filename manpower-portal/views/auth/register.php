<?php
/**
 * Industry Partner Registration View
 * Organization: PRAYAS – The Automation Academy
 */

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
        header("Location: /views/auth/login.php?success=" . urlencode($result['message']));
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
    <title>Industry Partner Registration — PRAYAS</title>
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
        .reg-card {
            background: #ffffff;
            border: none;
            border-radius: 14px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .reg-header {
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
        .section-title {
            color: var(--prayas-navy);
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 16px;
        }
        .brand-logo {
            max-height: 48px;
            width: auto;
        }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <div class="card reg-card">
                <div class="reg-header">
                    <img src="/assets/images/logo.png" alt="PRAYAS The Automation Academy" class="brand-logo mb-2">
                    <h4 class="fw-bold mb-1" style="color: var(--prayas-navy);">Industry Partner Onboarding</h4>
                    <p class="text-muted small mb-0">Register your organization to submit manpower requirements and source skilled ITI technicians</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/views/auth/register.php" novalidate>
                        <?= renderCSRFField() ?>

                        <!-- 1. Corporate Identity -->
                        <div class="section-title">1. Organization Details</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Company / Plant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['company_name']) ? 'is-invalid' : '' ?>" name="company_name" value="<?= e($_POST['company_name'] ?? '') ?>" required placeholder="">
                                <?php if (isset($fieldErrors['company_name'])): ?><div class="invalid-feedback"><?= e($fieldErrors['company_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Registration / CIN / GSTIN</label>
                                <input type="text" class="form-control <?= isset($fieldErrors['registration_number']) ? 'is-invalid' : '' ?>" name="registration_number" value="<?= e($_POST['registration_number'] ?? '') ?>" placeholder="e.g. U29100MH2020PTC123456">
                                <?php if (isset($fieldErrors['registration_number'])): ?><div class="invalid-feedback"><?= e($fieldErrors['registration_number']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Industry Sector <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($fieldErrors['industry_type']) ? 'is-invalid' : '' ?>" name="industry_type" required>
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
                                <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['state']) ? 'is-invalid' : '' ?>" name="state" value="<?= e($_POST['state'] ?? 'Maharashtra') ?>" required>
                                <?php if (isset($fieldErrors['state'])): ?><div class="invalid-feedback"><?= e($fieldErrors['state']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Plant / Office Address <span class="text-danger">*</span></label>
                                <textarea class="form-control <?= isset($fieldErrors['address']) ? 'is-invalid' : '' ?>" name="address" rows="2" required placeholder="Address"><?= e($_POST['address'] ?? '') ?></textarea>
                                <?php if (isset($fieldErrors['address'])): ?><div class="invalid-feedback"><?= e($fieldErrors['address']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['district']) ? 'is-invalid' : '' ?>" name="district" value="<?= e($_POST['district'] ?? '') ?>" required placeholder="e.g. Pune, Nashik">
                                <?php if (isset($fieldErrors['district'])): ?><div class="invalid-feedback"><?= e($fieldErrors['district']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['city']) ? 'is-invalid' : '' ?>" name="city" value="<?= e($_POST['city'] ?? '') ?>" required placeholder="e.g. Chakan, Bhosari">
                                <?php if (isset($fieldErrors['city'])): ?><div class="invalid-feedback"><?= e($fieldErrors['city']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Pincode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['pincode']) ? 'is-invalid' : '' ?>" name="pincode" value="<?= e($_POST['pincode'] ?? '') ?>" required placeholder="6-digit code">
                                <?php if (isset($fieldErrors['pincode'])): ?><div class="invalid-feedback"><?= e($fieldErrors['pincode']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <!-- 2. Primary HR Contact -->
                        <div class="section-title">2. Primary HR Contact Person</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">HR Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($fieldErrors['hr_contact_name']) ? 'is-invalid' : '' ?>" name="hr_contact_name" value="<?= e($_POST['hr_contact_name'] ?? '') ?>" required placeholder="Full Name">
                                <?php if (isset($fieldErrors['hr_contact_name'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Official Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?= isset($fieldErrors['hr_contact_email']) ? 'is-invalid' : '' ?>" name="hr_contact_email" value="<?= e($_POST['hr_contact_email'] ?? '') ?>" required placeholder="hr@company.com">
                                <?php if (isset($fieldErrors['hr_contact_email'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_email']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?= isset($fieldErrors['hr_contact_phone']) ? 'is-invalid' : '' ?>" name="hr_contact_phone" value="<?= e($_POST['hr_contact_phone'] ?? '') ?>" required placeholder="10-digit mobile">
                                <?php if (isset($fieldErrors['hr_contact_phone'])): ?><div class="invalid-feedback"><?= e($fieldErrors['hr_contact_phone']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <!-- 3. Portal Credentials -->
                        <div class="section-title">3. Account Password</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?= isset($fieldErrors['password']) ? 'is-invalid' : '' ?>" name="password" required placeholder="Min. 8 characters (letters + numbers)">
                                <?php if (isset($fieldErrors['password'])): ?><div class="invalid-feedback"><?= e($fieldErrors['password']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?= isset($fieldErrors['confirm_password']) ? 'is-invalid' : '' ?>" name="confirm_password" required placeholder="Re-enter password">
                                <?php if (isset($fieldErrors['confirm_password'])): ?><div class="invalid-feedback"><?= e($fieldErrors['confirm_password']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-prayas py-2 fs-6">Submit Company Registration</button>
                        </div>
                    </form>
                </div>

                <div class="card-footer bg-light text-center py-3 border-top">
                    <span class="text-muted small">Already registered? </span>
                    <a href="/views/auth/login.php" class="fw-semibold small text-decoration-none" style="color: var(--prayas-navy);">Sign In Here</a>
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

