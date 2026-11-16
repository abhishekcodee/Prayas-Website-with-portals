<?php
/**
 * Manufacturing Plant Details Dossier — Design System 2.0
 * Organization: PRAYAS – The Automation Academy
 * Part 06: Manufacturing Plant Management
 */

declare(strict_types=1);

$pageTitle = 'Plant Details Dossier — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$plantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$industryCtrl = new IndustryController();
$plant = $industryCtrl->getPlantDetails($companyId, $plantId);

if (!$plant) {
    $_SESSION['flash_error'] = 'Manufacturing plant not found or access denied.';
    header("Location: " . app_url('/views/industry/plants.php'));
    exit;
}
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
                <li class="breadcrumb-item active" aria-current="page"><?= e($plant['plant_name']) ?></li>
            </ol>
        </nav>

        <!-- Header Banner -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-light text-primary me-3 flex-shrink-0" style="width: 56px; height: 56px; text-align: center;">
                        <i class="bi bi-geo-alt-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h1 class="dashboard-title mb-0"><?= e($plant['plant_name']) ?></h1>
                            <span class="badge bg-secondary font-monospace"><?= e($plant['plant_code']) ?></span>
                            <?php if ($plant['status'] === 'Active'): ?>
                                <span class="badge-status badge-verified">Active Operational</span>
                            <?php else: ?>
                                <span class="badge-status badge-rejected">Inactive Unit</span>
                            <?php endif; ?>
                        </div>
                        <div class="supporting-text text-muted mt-1">
                            <span class="me-3"><i class="bi bi-building me-1"></i><?= e($plant['company_name']) ?></span>
                            <span class="me-3"><i class="bi bi-tag me-1"></i><?= e($plant['plant_type'] ?? 'Manufacturing Unit') ?></span>
                            <span><i class="bi bi-geo me-1"></i><?= e($plant['city']) ?>, <?= e($plant['state']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= app_url('/views/industry/plant_form.php?id=' . $plant['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Edit Plant Details
                    </a>
                    <a href="<?= app_url('/views/industry/plants.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Plants List
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Key Plant Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="summary-card">
                    <div class="metric-label">Requisitions Linked</div>
                    <div class="metric-value text-primary"><?= (int)$plant['total_requirements'] ?></div>
                    <div class="supporting-text mt-1 text-muted">Posted for this site</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="summary-card">
                    <div class="metric-label">Target Vacancies</div>
                    <div class="metric-value text-dark"><?= (int)$plant['total_vacancies'] ?></div>
                    <div class="supporting-text mt-1 text-muted">Total headcount demand</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="summary-card">
                    <div class="metric-label">Joined Candidates</div>
                    <div class="metric-value text-success"><?= (int)$plant['total_joined'] ?></div>
                    <div class="supporting-text mt-1 text-success fw-semibold">On-ground placements</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="summary-card">
                    <div class="metric-label">Shifts & Schedule</div>
                    <div class="metric-value text-info"><?= (int)($plant['shifts_count'] ?? 3) ?> Shifts</div>
                    <div class="supporting-text mt-1 text-muted"><?= (int)($plant['working_days_per_week'] ?? 6) ?> Days / Week</div>
                </div>
            </div>
        </div>

        <!-- Details Dossier Tabs -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Column 1: Plant & Geographical Coordinates -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="bi bi-geo-alt me-2 text-primary"></i>1. Facility & Geographic Coordinates
                        </h6>
                        <table class="table table-borderless table-sm small mb-4">
                            <tr>
                                <th class="text-muted w-40">Plant Legal Name:</th>
                                <td class="fw-bold"><?= e($plant['plant_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Unique Plant Code:</th>
                                <td class="font-monospace fw-bold text-primary"><?= e($plant['plant_code']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Plant Facility Type:</th>
                                <td><?= e($plant['plant_type'] ?? 'Manufacturing Unit') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Manufacturing Sector:</th>
                                <td><?= e($plant['manufacturing_sector'] ?? 'General Manufacturing') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Year Established:</th>
                                <td><?= e($plant['establishment_year'] ?? 'Not Specified') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Registered Address:</th>
                                <td><?= e($plant['plant_address']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">City & Taluka:</th>
                                <td><?= e($plant['city']) ?> <?= !empty($plant['taluka']) ? '(' . e($plant['taluka']) . ')' : '' ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">District & State:</th>
                                <td><?= e($plant['district']) ?>, <?= e($plant['state']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">PIN Code & Country:</th>
                                <td><?= e($plant['pincode']) ?>, <?= e($plant['country'] ?? 'India') ?></td>
                            </tr>
                            <?php if (!empty($plant['google_maps_url'])): ?>
                            <tr>
                                <th class="text-muted">Maps Location URL:</th>
                                <td><a href="<?= e($plant['google_maps_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary small"><i class="bi bi-box-arrow-up-right me-1"></i>View on Google Maps</a></td>
                            </tr>
                            <?php endif; ?>
                        </table>

                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="bi bi-card-text me-2 text-primary"></i>2. Plant Description
                        </h6>
                        <p class="small text-secondary">
                            <?= !empty($plant['description']) ? nl2br(e($plant['description'])) : 'No formal operational overview or plant description submitted.' ?>
                        </p>
                    </div>

                    <!-- Column 2: Operations & Recruitment Coordinators -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="bi bi-gear-wide-connected me-2 text-primary"></i>3. Manufacturing Operations
                        </h6>
                        <table class="table table-borderless table-sm small mb-4">
                            <tr>
                                <th class="text-muted w-40">Manufacturing Activity:</th>
                                <td><?= e($plant['manufacturing_activity'] ?? 'General Production') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Production Category:</th>
                                <td><?= e($plant['production_category'] ?? 'Discrete / Line Production') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Active Departments:</th>
                                <td>
                                    <?php if (!empty($plant['departments'])): ?>
                                        <?php 
                                            $depts = is_array($plant['departments']) ? $plant['departments'] : explode(',', (string)$plant['departments']);
                                            foreach ($depts as $d):
                                        ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1"><?= e(trim((string)$d)) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">General Manufacturing Units</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Production Shifts:</th>
                                <td><?= (int)($plant['shifts_count'] ?? 3) ?> Shifts / Day</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Working Schedule:</th>
                                <td><?= (int)($plant['working_days_per_week'] ?? 6) ?> Days per Week</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Approximate Workforce:</th>
                                <td><span class="badge bg-info text-dark"><?= e($plant['workforce_size'] ?? 'Standard Plant') ?></span></td>
                            </tr>
                        </table>

                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="bi bi-person-badge me-2 text-primary"></i>4. Plant Recruitment Coordinator
                        </h6>
                        <table class="table table-borderless table-sm small mb-0">
                            <tr>
                                <th class="text-muted w-40">Primary Contact Person:</th>
                                <td class="fw-bold text-dark"><?= e($plant['contact_person']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Designation:</th>
                                <td><?= e($plant['contact_designation'] ?? 'Plant HR Coordinator') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Contact Phone:</th>
                                <td><i class="bi bi-telephone me-1 text-primary"></i><?= e($plant['contact_number']) ?></td>
                            </tr>
                            <?php if (!empty($plant['alternate_contact_number'])): ?>
                            <tr>
                                <th class="text-muted">Alternate Phone:</th>
                                <td><?= e($plant['alternate_contact_number']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($plant['contact_email'])): ?>
                            <tr>
                                <th class="text-muted">Official Email:</th>
                                <td><i class="bi bi-envelope me-1 text-primary"></i><?= e($plant['contact_email']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th class="text-muted">Assigned Platform Admin:</th>
                                <td>
                                    <?php if (!empty($plant['assigned_admin_name'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-shield-check text-success me-1"></i><?= e($plant['assigned_admin_name']) ?>
                                            (<?= e($plant['assigned_admin_email'] ?? '') ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Manpower Requisitions -->
        <div class="card-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>Active Manpower Requisitions for this Facility (<?= count($plant['requirements']) ?>)</h3>
                <a href="<?= app_url('/views/industry/requirement_form.php?plant_id=' . $plant['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Create Requisition for Plant
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-prayas table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Requisition Code</th>
                            <th>Job Role Title</th>
                            <th>Quantity Required</th>
                            <th>Onboarded</th>
                            <th>Submission Date</th>
                            <th>Target Joining</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plant['requirements'])): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No manpower requisitions currently designated for this manufacturing unit.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plant['requirements'] as $req): ?>
                                <tr>
                                    <td class="font-monospace text-primary fw-bold"><?= e($req['requirement_id']) ?></td>
                                    <td class="fw-bold text-dark"><?= e($req['title']) ?></td>
                                    <td><?= (int)$req['quantity_required'] ?></td>
                                    <td class="text-success fw-bold"><?= (int)$req['joined_count'] ?></td>
                                    <td class="small text-muted"><?= e($req['submission_date']) ?></td>
                                    <td class="small text-muted"><?= e($req['target_joining_date']) ?></td>
                                    <td><span class="badge-status badge-approved"><?= e($req['status']) ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= app_url('/views/industry/requirement_details.php?id=' . $req['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

