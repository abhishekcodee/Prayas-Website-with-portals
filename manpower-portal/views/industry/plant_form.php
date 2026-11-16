<?php
/**
 * Manufacturing Plant Add / Edit Form — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Manufacturing Plant Management
 */

declare(strict_types=1);

$pageTitle = 'Manage Plant — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$industryCtrl = new IndustryController();

$plantId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$plant = null;
if ($plantId) {
    $plant = $industryCtrl->getPlantById($companyId, $plantId);
    if (!$plant) {
        $_SESSION['flash_error'] = 'Manufacturing plant not found or access denied.';
        header("Location: " . app_url('/views/industry/plants.php'));
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $payload = [
        // Section A: Plant Information
        'plant_name'               => $_POST['plant_name'] ?? '',
        'plant_code'               => $_POST['plant_code'] ?? '',
        'plant_type'               => $_POST['plant_type'] ?? 'Manufacturing Unit',
        'manufacturing_sector'     => $_POST['manufacturing_sector'] ?? '',
        'description'              => $_POST['description'] ?? '',
        'establishment_year'       => $_POST['establishment_year'] ?? '',

        // Section B: Location
        'plant_address'            => $_POST['plant_address'] ?? '',
        'state'                    => $_POST['state'] ?? '',
        'district'                 => $_POST['district'] ?? '',
        'city'                     => $_POST['city'] ?? '',
        'taluka'                   => $_POST['taluka'] ?? '',
        'pincode'                  => $_POST['pincode'] ?? '',
        'country'                  => $_POST['country'] ?? 'India',
        'google_maps_url'          => $_POST['google_maps_url'] ?? '',

        // Section C: Contact Details
        'contact_person'           => $_POST['contact_person'] ?? '',
        'contact_designation'      => $_POST['contact_designation'] ?? '',
        'contact_email'            => $_POST['contact_email'] ?? '',
        'contact_number'           => $_POST['contact_number'] ?? '',
        'alternate_contact_number' => $_POST['alternate_contact_number'] ?? '',

        // Section D: Operations
        'manufacturing_activity'   => $_POST['manufacturing_activity'] ?? '',
        'production_category'      => $_POST['production_category'] ?? '',
        'departments'              => $_POST['departments'] ?? '',
        'shifts_count'             => (int)($_POST['shifts_count'] ?? 3),
        'working_days_per_week'    => (int)($_POST['working_days_per_week'] ?? 6),
        'workforce_size'           => $_POST['workforce_size'] ?? '',
        'status'                   => $_POST['status'] ?? 'Active'
    ];

    $res = $industryCtrl->savePlant($companyId, $payload, $plantId);

    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        header("Location: " . app_url('/views/industry/plants.php'));
        exit;
    } else {
        $_SESSION['flash_error'] = $res['message'];
        $plant = array_merge($plant ?? [], $payload);
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
                <li class="breadcrumb-item"><a href="<?= app_url('/views/industry/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= app_url('/views/industry/plants.php') ?>">Manufacturing Plants</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $plantId ? 'Edit Plant' : 'Register Plant' ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="dashboard-title mb-1">
                    <i class="bi bi-geo-alt me-2 text-primary"></i><?= $plantId ? 'Edit Manufacturing Plant' : 'Register Manufacturing Plant' ?>
                </h1>
                <p class="supporting-text mb-0">Record physical facility coordinates, factory operations, and recruitment coordinators.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Plants List
                </a>
            </div>
        </div>

        <div class="card-enterprise w-100 mb-5">
            <div class="card-header bg-transparent py-3">
                <h2 class="section-title mb-0">
                    <i class="bi bi-building-add me-2 text-primary"></i>
                    <?= $plantId ? 'Update Plant: ' . e($plant['plant_name'] ?? '') : 'New Manufacturing Plant Registration' ?>
                </h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= app_url('/views/industry/plant_form.php' . ($plantId ? '?id=' . $plantId : '')) ?>" id="plantRegistrationForm">
                    <?= renderCSRFField() ?>

                    <!-- SECTION A: Plant Information -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-info-circle me-1"></i>Section A — Plant Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Plant / Facility Name <span class="text-danger">*</span></label>
                                <input type="text" name="plant_name" class="form-control" placeholder="e.g. Pune Robotics Unit / Assembly Plant 2" value="<?= e($plant['plant_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Plant Code <span class="text-danger">*</span></label>
                                <input type="text" name="plant_code" class="form-control text-uppercase font-monospace" placeholder="e.g. PLT-PUN-01" value="<?= e($plant['plant_code'] ?? '') ?>" required>
                                <div class="micro-text text-muted">Must be unique for your company.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Establishment Year</label>
                                <input type="number" name="establishment_year" class="form-control" placeholder="e.g. 2018" min="1900" max="<?= date('Y') ?>" value="<?= e($plant['establishment_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Plant Type <span class="text-danger">*</span></label>
                                <select name="plant_type" class="form-select" required>
                                    <?php 
                                    $types = [
                                        'Manufacturing Unit', 
                                        'Heavy Fabrication Shop', 
                                        'CNC Machine Shop / Tool Room', 
                                        'Automated Assembly Line', 
                                        'Foundry & Casting Facility',
                                        'Warehouse & Supply Chain Logistics', 
                                        'R&D / Testing Centre'
                                    ];
                                    foreach ($types as $t): ?>
                                        <option value="<?= $t ?>" <?= ($plant['plant_type'] ?? 'Manufacturing Unit') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Industry / Manufacturing Sector</label>
                                <input type="text" name="manufacturing_sector" class="form-control" placeholder="e.g. Automotive, Electronics, Defense, Precision Engineering" value="<?= e($plant['manufacturing_sector'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Plant Description & Operational Overview</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of facility operations, machinery installed, and core products..."><?= e($plant['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION B: Location -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-geo-alt me-1"></i>Section B — Geographical Location Coordinates
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Physical Plant Address / Plot Details <span class="text-danger">*</span></label>
                                <textarea name="plant_address" class="form-control" rows="2" placeholder="e.g. Plot No. E-45, MIDC Industrial Area, Phase II" required><?= e($plant['plant_address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">City / Town <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" placeholder="e.g. Pune" value="<?= e($plant['city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Taluka / Sub-District</label>
                                <input type="text" name="taluka" class="form-control" placeholder="e.g. Haveli" value="<?= e($plant['taluka'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                <input type="text" name="district" class="form-control" placeholder="e.g. Pune" value="<?= e($plant['district'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control" placeholder="e.g. Maharashtra" value="<?= e($plant['state'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">PIN Code <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" class="form-control" pattern="[1-9][0-9]{5}" placeholder="e.g. 411019" value="<?= e($plant['pincode'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Country</label>
                                <input type="text" name="country" class="form-control" value="<?= e($plant['country'] ?? 'India') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Google Maps URL (Optional)</label>
                                <input type="url" name="google_maps_url" class="form-control" placeholder="https://maps.google.com/..." value="<?= e($plant['google_maps_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION C: Contact Details -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-person-badge me-1"></i>Section C — Plant Contact & Coordinator Details
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Plant Contact Person <span class="text-danger">*</span></label>
                                <input type="text" name="contact_person" class="form-control" placeholder="e.g. Ramesh Kulkarni" value="<?= e($plant['contact_person'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Designation / Role Title</label>
                                <input type="text" name="contact_designation" class="form-control" placeholder="e.g. Senior Plant HR Manager" value="<?= e($plant['contact_designation'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Official Email Address</label>
                                <input type="email" name="contact_email" class="form-control" placeholder="e.g. plant.pune@company.com" value="<?= e($plant['contact_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Primary Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" placeholder="e.g. 9822334455" value="<?= e($plant['contact_number'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Alternate Contact Number</label>
                                <input type="text" name="alternate_contact_number" class="form-control" placeholder="e.g. 020-27123456" value="<?= e($plant['alternate_contact_number'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION D: Operations -->
                    <div class="mb-4">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3 border-bottom pb-2">
                            <i class="bi bi-gear-wide-connected me-1"></i>Section D — Manufacturing Operations
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Manufacturing Activity</label>
                                <input type="text" name="manufacturing_activity" class="form-control" placeholder="e.g. Sheet Metal Stamping, Robotic Welding, Wire Harnessing" value="<?= e($plant['manufacturing_activity'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Production Category</label>
                                <input type="text" name="production_category" class="form-control" placeholder="e.g. Serial Line Production / High Precision Batch" value="<?= e($plant['production_category'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Active Departments (Comma-separated)</label>
                                <input type="text" name="departments" class="form-control" placeholder="e.g. Press Shop, Assembly, Quality Inspection, Tool Room, Maintenance" value="<?= e(is_array($plant['departments'] ?? null) ? implode(', ', $plant['departments']) : ($plant['departments'] ?? '')) ?>">
                                <div class="micro-text text-muted">Departments configured here assist recruiters in filtering trade allocations.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Production Shifts / Day</label>
                                <select name="shifts_count" class="form-select">
                                    <option value="1" <?= ((int)($plant['shifts_count'] ?? 3) === 1) ? 'selected' : '' ?>>1 Shift (General)</option>
                                    <option value="2" <?= ((int)($plant['shifts_count'] ?? 3) === 2) ? 'selected' : '' ?>>2 Shifts</option>
                                    <option value="3" <?= ((int)($plant['shifts_count'] ?? 3) === 3) ? 'selected' : '' ?>>3 Shifts (24/7)</option>
                                    <option value="4" <?= ((int)($plant['shifts_count'] ?? 3) === 4) ? 'selected' : '' ?>>4 Shifts / Rotational</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Working Days / Week</label>
                                <select name="working_days_per_week" class="form-select">
                                    <option value="5" <?= ((int)($plant['working_days_per_week'] ?? 6) === 5) ? 'selected' : '' ?>>5 Days</option>
                                    <option value="6" <?= ((int)($plant['working_days_per_week'] ?? 6) === 6) ? 'selected' : '' ?>>6 Days</option>
                                    <option value="7" <?= ((int)($plant['working_days_per_week'] ?? 6) === 7) ? 'selected' : '' ?>>7 Days (Continuous)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Approx. Workforce Size</label>
                                <select name="workforce_size" class="form-select">
                                    <option value="<100" <?= ($plant['workforce_size'] ?? '') === '<100' ? 'selected' : '' ?>>&lt; 100 Personnel</option>
                                    <option value="100-250" <?= ($plant['workforce_size'] ?? '100-250') === '100-250' ? 'selected' : '' ?>>100 - 250 Personnel</option>
                                    <option value="250-500" <?= ($plant['workforce_size'] ?? '') === '250-500' ? 'selected' : '' ?>>250 - 500 Personnel</option>
                                    <option value="500-1000" <?= ($plant['workforce_size'] ?? '') === '500-1000' ? 'selected' : '' ?>>500 - 1000 Personnel</option>
                                    <option value="1000+" <?= ($plant['workforce_size'] ?? '') === '1000+' ? 'selected' : '' ?>>1000+ Personnel</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Operational Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="Active" <?= ($plant['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active Operational</option>
                                    <option value="Inactive" <?= ($plant['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive / Standby</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION E: Review & Action Buttons -->
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i><?= $plantId ? 'Update Plant Facility' : 'Save & Register Plant' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
