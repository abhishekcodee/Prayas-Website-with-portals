<?php
/**
 * Industry Company Profile Management
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Company Profile — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: /views/auth/login.php");
    exit;
}

$industryCtrl = new IndustryController();
$company = $industryCtrl->getCompanyProfile($companyId);

if (!$company) {
    echo "<div class='container p-5'><h4>Error: Company profile record not found.</h4></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    // Check if user has permission to edit profile (industry_admin)
    if (!hasRole('industry_admin') && !hasPermission('company.update')) {
        $_SESSION['flash_error'] = 'You do not have administrative permission to update company details.';
        header("Location: /views/industry/company_profile.php");
        exit;
    }

    $payload = [
        'company_name'        => $_POST['company_name'] ?? '',
        'registration_number' => $_POST['registration_number'] ?? '',
        'industry_type'       => $_POST['industry_type'] ?? '',
        'address'             => $_POST['address'] ?? '',
        'state'               => $_POST['state'] ?? '',
        'district'            => $_POST['district'] ?? '',
        'city'                => $_POST['city'] ?? '',
        'pincode'             => $_POST['pincode'] ?? '',
        'website'             => $_POST['website'] ?? '',
        'hr_contact_name'     => $_POST['hr_contact_name'] ?? '',
        'hr_contact_phone'    => $_POST['hr_contact_phone'] ?? '',
        'hr_contact_email'    => $_POST['hr_contact_email'] ?? '',
        'description'         => $_POST['description'] ?? ''
    ];

    $fileLogo = $_FILES['company_logo'] ?? null;
    $res = $industryCtrl->updateCompanyProfile($companyId, $payload, $fileLogo);

    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
    } else {
        $_SESSION['flash_error'] = $res['message'];
    }

    header("Location: /views/industry/company_profile.php");
    exit;
}

$verStatus = $company['verification_status'] ?? 'Pending';
$badgeClass = match ($verStatus) {
    'Verified', 'Active' => 'bg-success',
    'Under Review'       => 'bg-info text-dark',
    'Rejected'           => 'bg-danger',
    'Suspended'          => 'bg-dark',
    default              => 'bg-warning text-dark'
};

// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i>Company Profile</h4>
                <p class="text-muted small mb-0">Manage corporate identity, statutory details, HR contacts, and plant preferences.</p>
            </div>
            <div>
                <a href="/views/industry/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Verification Dossier & Logo -->
            <div class="col-lg-4">
                <!-- Verification Status Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Verification Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center p-3 mb-3 bg-light rounded">
                            <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2"><?= htmlspecialchars($verStatus) ?></span>
                            <div class="small text-muted mt-2">
                                <?php if (in_array($verStatus, ['Verified', 'Active'])): ?>
                                    <i class="bi bi-check-circle-fill text-success me-1"></i>Verified by PRAYAS
                                <?php elseif ($verStatus === 'Under Review'): ?>
                                    <i class="bi bi-hourglass-split text-info me-1"></i>Audit under review
                                <?php elseif ($verStatus === 'Rejected'): ?>
                                    <i class="bi bi-x-circle-fill text-danger me-1"></i>Verification declined
                                <?php else: ?>
                                    <i class="bi bi-clock-history text-warning me-1"></i>Pending vetting
                                <?php endif; ?>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Registered On:</span>
                                <span class="fw-semibold"><?= htmlspecialchars($company['created_at'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Verified Date:</span>
                                <span class="fw-semibold"><?= htmlspecialchars($company['verified_at'] ?? 'Pending') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Audited By:</span>
                                <span class="fw-semibold"><?= htmlspecialchars($company['verified_by_name'] ?? 'PRAYAS Committee') ?></span>
                            </li>
                            <?php if (!empty($company['sensitive_modified_at'])): ?>
                            <li class="list-group-item d-flex justify-content-between px-0 bg-warning-subtle p-2 rounded">
                                <span class="text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>Re-verification Flag:</span>
                                <span class="fw-bold"><?= htmlspecialchars($company['sensitive_modified_at']) ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <?php if ($verStatus === 'Rejected' && !empty($company['rejection_reason'])): ?>
                            <div class="mt-3 p-3 bg-danger-subtle text-danger rounded small">
                                <strong>Rejection Reason:</strong>
                                <div><?= nl2br(htmlspecialchars($company['rejection_reason'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>Verification status can only be modified by authorized Super Admin evaluators.
                        </div>
                    </div>
                </div>

                <!-- Company Logo Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-image me-2 text-primary"></i>Corporate Logo</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3 p-3 border rounded bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                            <?php if (!empty($company['company_logo'])): ?>
                                <img src="<?= htmlspecialchars($company['company_logo']) ?>" alt="Company Logo" class="img-fluid" style="max-height: 120px;">
                            <?php else: ?>
                                <div class="text-muted small">
                                    <i class="bi bi-building fs-1 d-block mb-1"></i>
                                    No logo uploaded
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted d-block mb-2">Upload PNG, JPG, or WebP (Max 2MB)</small>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Edit Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Company Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="/views/industry/company_profile.php" enctype="multipart/form-data">
                            <?= renderCSRFField() ?>

                            <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">1. Statutory & Business Identification</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Company Legal Name <span class="text-danger">*</span></label>
                                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company['company_name'] ?? '') ?>" required>
                                    <div class="form-text small">Legal name as registered with MCA/ROC.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Registration Number (CIN / Udyam / GSTIN)</label>
                                    <input type="text" name="registration_number" class="form-control" value="<?= htmlspecialchars($company['registration_number'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Industry Sector <span class="text-danger">*</span></label>
                                    <select name="industry_type" class="form-select" required>
                                        <?php
                                        $sectors = [
                                            'Automotive & Auto Components', 'Industrial Automation & Robotics', 
                                            'Heavy Engineering & Machinery', 'Electrical & Electronics Manufacturing',
                                            'CNC Machining & Tool Rooms', 'Metal Fabrication & Welding',
                                            'Plastics & Polymer Processing', 'Pharmaceutical & Process Engineering',
                                            'Renewable Energy & Solar/Wind', 'Aerospace & Defence Components',
                                            'Other Manufacturing Sector'
                                        ];
                                        foreach ($sectors as $sec): ?>
                                            <option value="<?= $sec ?>" <?= ($company['industry_type'] ?? '') === $sec ? 'selected' : '' ?>><?= $sec ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Company Official Website</label>
                                    <input type="text" name="website" class="form-control" placeholder="https://example.com" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">2. Registered Head Office Address</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Office Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($company['city'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                    <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($company['district'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($company['state'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">PIN Code <span class="text-danger">*</span></label>
                                    <input type="text" name="pincode" class="form-control" pattern="[1-9][0-9]{5}" value="<?= htmlspecialchars($company['pincode'] ?? '') ?>" required>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">3. Primary HR Contact</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">HR Contact Person <span class="text-danger">*</span></label>
                                    <input type="text" name="hr_contact_name" class="form-control" value="<?= htmlspecialchars($company['hr_contact_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Official HR Email <span class="text-danger">*</span></label>
                                    <input type="email" name="hr_contact_email" class="form-control" value="<?= htmlspecialchars($company['hr_contact_email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">HR Contact Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="hr_contact_phone" class="form-control" value="<?= htmlspecialchars($company['hr_contact_phone'] ?? '') ?>" required>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">4. Branding & Company Overview</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Update Company Logo</label>
                                    <input type="file" name="company_logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Company Profile & Operations Summary</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Provide a brief summary of manufacturing products, plant capacities, and ITI apprentice working environment..."><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <span class="text-muted small">
                                    <i class="bi bi-shield-lock me-1"></i>Changes are recorded in platform audit ledger.
                                </span>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Save Company Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

