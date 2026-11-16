<?php
/**
 * Structured 5-Step Industry Registration & Edit Form — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Industry Administration & Governance
 */

declare(strict_types=1);

$pageTitle = 'Industry Registration Form — PRAYAS Super Admin';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole('super_admin');

$industryCtrl = new IndustryController();

$industryId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$industry = null;
if ($industryId) {
    $industry = $industryCtrl->getIndustryById($industryId);
    if (!$industry) {
        $_SESSION['flash_error'] = 'Industry profile not found.';
        header("Location: " . app_url('/views/super_admin/industries.php'));
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $payload = [
        // Step 1: Basic Information
        'industry_name'          => $_POST['industry_name'] ?? '',
        'industry_code'          => $_POST['industry_code'] ?? '',
        'industry_type'          => $_POST['industry_type'] ?? '',
        'description'            => $_POST['description'] ?? '',
        'website'                => $_POST['website'] ?? '',
        'establishment_year'     => $_POST['establishment_year'] ?? '',

        // Step 2: Contact Information
        'primary_contact_person' => $_POST['primary_contact_person'] ?? '',
        'designation'            => $_POST['designation'] ?? '',
        'official_email'         => $_POST['official_email'] ?? '',
        'contact_number'         => $_POST['contact_number'] ?? '',
        'alternate_contact'      => $_POST['alternate_contact'] ?? '',

        // Step 3: Registered Address
        'address_line1'          => $_POST['address_line1'] ?? '',
        'address_line2'          => $_POST['address_line2'] ?? '',
        'state'                  => $_POST['state'] ?? '',
        'district'               => $_POST['district'] ?? '',
        'city'                   => $_POST['city'] ?? '',
        'taluka'                 => $_POST['taluka'] ?? '',
        'village'                => $_POST['village'] ?? '',
        'pincode'                => $_POST['pincode'] ?? '',
        'country'                => $_POST['country'] ?? 'India',

        // Step 4: Status
        'status'                 => $_POST['status'] ?? 'Active'
    ];

    $res = $industryCtrl->saveIndustry(null, $payload, $industryId);

    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        header("Location: " . app_url('/views/super_admin/industries.php'));
        exit;
    } else {
        $_SESSION['flash_error'] = $res['message'];
        $industry = array_merge($industry ?? [], $payload);
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= app_url('/views/super_admin/industries.php') ?>">Industry Directory</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $industryId ? 'Edit Industry' : 'Register Industry' ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="dashboard-title mb-1">
                    <i class="bi bi-building-add me-2 text-primary"></i><?= $industryId ? 'Edit Industry Profile' : 'Structured Industry Registration' ?>
                </h1>
                <p class="supporting-text mb-0">Multi-step registration workflow with duplicate code verification, address validation, and administrative controls.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/super_admin/industries.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Industry Directory
                </a>
            </div>
        </div>

        <div class="card-enterprise w-100 mb-5">
            <div class="card-header bg-transparent py-3">
                <h2 class="section-title mb-0">
                    <i class="bi bi-card-checklist me-2 text-primary"></i>
                    <?= $industryId ? 'Update: ' . e($industry['company_name'] ?? '') : 'New Industry Onboarding (5 Steps)' ?>
                </h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= app_url('/views/super_admin/industry_form.php' . ($industryId ? '?id=' . $industryId : '')) ?>" id="industryForm">
                    <?= renderCSRFField() ?>

                    <!-- STEP 1: Basic Information -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-info-circle me-1"></i>Step 1 — Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Industry / Entity Name <span class="text-danger">*</span></label>
                                <input type="text" name="industry_name" class="form-control" placeholder="e.g. Tata Motors Passenger Vehicles" value="<?= e($industry['company_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Industry Code / CIN <span class="text-danger">*</span></label>
                                <input type="text" name="industry_code" class="form-control text-uppercase font-monospace" placeholder="e.g. IND-TMPV-01" value="<?= e($industry['registration_number'] ?? $industry['industry_code'] ?? '') ?>" required>
                                <div class="micro-text text-muted">Must be globally unique.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Year Established</label>
                                <input type="number" name="establishment_year" class="form-control" placeholder="e.g. 1998" min="1800" max="<?= date('Y') ?>" value="<?= e($industry['establishment_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Industry Type / Sector <span class="text-danger">*</span></label>
                                <select name="industry_type" class="form-select" required>
                                    <option value="">-- Select Sector --</option>
                                    <?php 
                                    $sectors = [
                                        'Automotive Manufacturing',
                                        'Heavy Engineering',
                                        'CNC & Precision Tooling',
                                        'Electronics & Assembly',
                                        'Robotics & Industrial Automation',
                                        'Electrical Equipment',
                                        'Aerospace & Defense Components',
                                        'Steel & Metal Fabrication',
                                        'Plastics & Polymer Processing',
                                        'Renewable Energy / Solar'
                                    ];
                                    foreach ($sectors as $sec): ?>
                                        <option value="<?= $sec ?>" <?= ($industry['industry_type'] ?? '') === $sec ? 'selected' : '' ?>><?= $sec ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Official Corporate Website</label>
                                <input type="url" name="website" class="form-control" placeholder="https://www.company.com" value="<?= e($industry['website'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Industry Description & Operations</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of industrial scope, core product lines, and manufacturing facilities..."><?= e($industry['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Contact Information -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-person-lines-fill me-1"></i>Step 2 — Contact Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Primary Contact Person <span class="text-danger">*</span></label>
                                <input type="text" name="primary_contact_person" class="form-control" placeholder="e.g. Vikram Malhotra" value="<?= e($industry['hr_contact_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="e.g. Vice President - Human Resources" value="<?= e($industry['designation'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Official Email <span class="text-danger">*</span></label>
                                <input type="email" name="official_email" class="form-control" placeholder="hr@company.com" value="<?= e($industry['hr_contact_email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Primary Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" placeholder="9822001122" value="<?= e($industry['hr_contact_phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Alternate Contact Number</label>
                                <input type="text" name="alternate_contact" class="form-control" placeholder="020-27891234" value="<?= e($industry['alternate_contact'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Registered Address -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-geo-alt me-1"></i>Step 3 — Registered Corporate Address
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                                <input type="text" name="address_line1" class="form-control" placeholder="e.g. Corporate Tower A, Sector 18" value="<?= e($industry['address'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Address Line 2</label>
                                <input type="text" name="address_line2" class="form-control" placeholder="e.g. Industrial Cyber Hub" value="<?= e($industry['address_line2'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" placeholder="e.g. Pune" value="<?= e($industry['city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Taluka / Village</label>
                                <input type="text" name="taluka" class="form-control" placeholder="e.g. Haveli" value="<?= e($industry['taluka'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                <input type="text" name="district" class="form-control" placeholder="e.g. Pune" value="<?= e($industry['district'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control" placeholder="e.g. Maharashtra" value="<?= e($industry['state'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">PIN Code <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" class="form-control" pattern="[1-9][0-9]{5}" placeholder="e.g. 411001" value="<?= e($industry['pincode'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Country</label>
                                <input type="text" name="country" class="form-control" value="India">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Industry Administration & Status -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-shield-check me-1"></i>Step 4 — Industry Administration & Account Status
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Initial Account Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="Active" <?= ($industry['verification_status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active (Approved for Sourcing)</option>
                                    <option value="Pending" <?= ($industry['verification_status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending Verification</option>
                                    <option value="Suspended" <?= ($industry['verification_status'] ?? '') === 'Suspended' ? 'selected' : '' ?>>Suspended (On Hold)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Administrator Provisioning Policy</label>
                                <div class="p-2 bg-light border rounded small text-muted">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>After creating this industry, you can designate an authorized Industry Admin or invite team members via the Users console.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Review & Submission -->
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="<?= app_url('/views/super_admin/industries.php') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i><?= $industryId ? 'Save Industry Profile' : 'Step 5: Review & Register Industry' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

