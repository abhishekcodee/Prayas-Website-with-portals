<?php
/**
 * Multi-Step Manpower Requirement Creation & Edit Wizard
 * Organization: PRAYAS – The Automation Academy
 */

declare(strict_types=1);

$pageTitle = 'Requirement Creation Wizard — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/RequirementController.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$pdo = Database::getConnection();
$reqCtrl = new RequirementController($pdo);
$industryCtrl = new IndustryController($pdo);

$plants = $industryCtrl->getPlants($companyId, ['status' => 'Active']);
$company = $industryCtrl->getCompanyProfile($companyId);

$reqId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$existingReq = null;
$initialStep = 1;

if ($reqId) {
    $existingReq = $reqCtrl->getRequirementById($companyId, $reqId);
    if (!$existingReq) {
        $_SESSION['flash_error'] = 'Requirement not found or access denied.';
        header("Location: " . app_url('/views/industry/requirements.php'));
        exit;
    }

    if (!in_array($existingReq['status'], ['DRAFT', 'NEEDS_CORRECTION'], true)) {
        $_SESSION['flash_error'] = "Requirement '{$existingReq['requirement_id']}' is already in {$existingReq['status']} status and cannot be edited in the wizard.";
        header("Location: " . app_url('/views/industry/requirement_details.php?id=' . $reqId));
        exit;
    }

    $initialStep = max(1, min(7, (int)($existingReq['wizard_step'] ?? 1)));
}

// Handle Form Submission (Draft vs Final Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();

    $action = $_POST['submit_action'] ?? 'draft';

    // Parse Trades array from POST
    $tradesList = [];
    if (isset($_POST['trades']) && is_array($_POST['trades'])) {
        foreach ($_POST['trades'] as $t) {
            if (!empty($t['trade_name'])) {
                $tradesList[] = [
                    'trade_name'        => trim((string)$t['trade_name']),
                    'vacancies'         => max(1, (int)($t['vacancies'] ?? 1)),
                    'qualification'     => trim((string)($t['qualification'] ?? 'ITI')),
                    'board_type'        => trim((string)($t['board_type'] ?? 'Either')),
                    'passing_year_from' => trim((string)($t['passing_year_from'] ?? '2022')),
                    'passing_year_to'   => trim((string)($t['passing_year_to'] ?? '2026')),
                    'required_skills'   => trim((string)($t['required_skills'] ?? '')),
                    'min_experience'    => trim((string)($t['min_experience'] ?? 'Fresher')),
                    'max_experience'    => trim((string)($t['max_experience'] ?? '')),
                    'max_age'           => !empty($t['max_age']) ? (int)$t['max_age'] : null,
                    'gender'            => (string)($t['gender'] ?? 'Any'),
                    'fresher_allowed'   => isset($t['fresher_allowed']) ? 1 : 0
                ];
            }
        }
    }

    $payload = [
        'title'                          => trim((string)($_POST['title'] ?? '')),
        'plant_id'                       => !empty($_POST['plant_id']) ? (int)$_POST['plant_id'] : null,
        'department'                     => trim((string)($_POST['department'] ?? '')),
        'industry_sector'                => trim((string)($_POST['industry_sector'] ?? ($company['industry_type'] ?? ''))),
        'requirement_purpose'            => trim((string)($_POST['requirement_purpose'] ?? '')),
        'company_website'                => trim((string)($_POST['company_website'] ?? ($company['website'] ?? ''))),
        'job_description'                => trim((string)($_POST['job_description'] ?? '')),
        'employment_type'                => (string)($_POST['employment_type'] ?? 'Full Time'),
        'priority'                       => (string)($_POST['priority'] ?? 'Medium'),
        'quantity_required'              => (int)($_POST['quantity_required'] ?? 0),
        'trades'                         => $tradesList,
        'qualification'                  => trim((string)($_POST['qualification'] ?? ($tradesList[0]['qualification'] ?? 'ITI'))),
        'board_type'                     => (string)($_POST['board_type'] ?? 'Either'),
        'passing_year_from'              => (string)($_POST['passing_year_from'] ?? ($tradesList[0]['passing_year_from'] ?? '2022')),
        'passing_year_to'                => (string)($_POST['passing_year_to'] ?? ($tradesList[0]['passing_year_to'] ?? '2026')),
        'experience_required'            => (string)($_POST['experience_required'] ?? ($tradesList[0]['min_experience'] ?? 'Fresher')),
        'min_age'                        => (int)($_POST['min_age'] ?? 18),
        'max_age'                        => !empty($_POST['max_age']) ? (int)$_POST['max_age'] : (!empty($tradesList[0]['max_age']) ? (int)$tradesList[0]['max_age'] : null),
        'gender_preference'              => (string)($_POST['gender_preference'] ?? ($tradesList[0]['gender'] ?? 'Any')),
        'skills'                         => trim((string)($_POST['skills'] ?? implode(', ', array_filter(array_column($tradesList, 'required_skills'))))),
        'job_location_state'             => trim((string)($_POST['job_location_state'] ?? ($company['state'] ?? 'Maharashtra'))),
        'job_location_district'          => trim((string)($_POST['job_location_district'] ?? ($company['district'] ?? 'Pune'))),
        'job_location_city'              => trim((string)($_POST['job_location_city'] ?? ($company['city'] ?? 'Pune'))),
        'job_location_pincode'           => trim((string)($_POST['job_location_pincode'] ?? ($company['pincode'] ?? '411001'))),
        'working_hours'                  => (string)($_POST['working_hours'] ?? '8 Hours'),
        'shift_details'                  => (string)($_POST['shift_details'] ?? 'General Shift'),
        'weekly_working_days'            => (int)($_POST['weekly_working_days'] ?? 6),
        'rotational_shift'               => (string)($_POST['rotational_shift'] ?? 'Yes'),
        'accommodation_provided'         => is_array($_POST['accommodation_provided'] ?? null)
            ? implode(', ', array_filter($_POST['accommodation_provided']))
            : (string)($_POST['accommodation_provided'] ?? 'No Accommodation'),
        'food_facility'                  => (string)($_POST['food_facility'] ?? 'Subsidized'),
        'transportation_provided'        => (string)($_POST['transportation_provided'] ?? 'No'),
        'safety_equipment'               => (string)($_POST['safety_equipment'] ?? 'Yes'),
        'other_facilities'               => trim((string)($_POST['other_facilities'] ?? '')),
        'pf_esic_benefit'                => (string)($_POST['pf_esic_benefit'] ?? 'Yes'),
        'min_salary'                     => floatval($_POST['min_salary'] ?? 0),
        'max_salary'                     => floatval($_POST['max_salary'] ?? 0),
        'in_hand_salary'                 => !empty($_POST['in_hand_salary']) ? floatval($_POST['in_hand_salary']) : null,
        'salary_type'                    => (string)($_POST['salary_type'] ?? 'Monthly'),
        'overtime_available'             => (string)($_POST['overtime_available'] ?? 'No'),
        'bonus_details'                  => trim((string)($_POST['bonus_details'] ?? '')),
        'additional_benefits'            => trim((string)($_POST['additional_benefits'] ?? '')),
        'application_deadline'           => (string)($_POST['application_deadline'] ?? ''),
        'interview_date'                 => (string)($_POST['interview_date'] ?? ''),
        'interview_end_date'             => !empty($_POST['interview_end_date']) ? (string)$_POST['interview_end_date'] : null,
        'target_joining_date'            => (string)($_POST['target_joining_date'] ?? ''),
        'interview_mode'                 => (string)($_POST['interview_mode'] ?? 'Plant Campus Walk-in'),
        'interview_location'             => trim((string)($_POST['interview_location'] ?? '')),
        'interview_process'              => trim((string)($_POST['interview_process'] ?? '')),
        'number_of_rounds'               => (int)($_POST['number_of_rounds'] ?? 2),
        'technical_interview_required'   => isset($_POST['technical_interview_required']) ? 1 : 0,
        'practical_assessment_required'  => isset($_POST['practical_assessment_required']) ? 1 : 0,
        'document_verification_required' => isset($_POST['document_verification_required']) ? 1 : 0,
        'contact_person'                 => trim((string)($_POST['contact_person'] ?? ($company['hr_contact_name'] ?? ''))),
        'contact_email'                  => trim((string)($_POST['contact_email'] ?? ($company['hr_contact_email'] ?? ''))),
        'contact_phone'                  => trim((string)($_POST['contact_phone'] ?? ($company['hr_contact_phone'] ?? ''))),
        'wizard_step'                    => (int)($_POST['current_step'] ?? 1)
    ];

    if ($action === 'save_draft') {
        $res = $reqCtrl->saveDraft($companyId, $payload, $reqId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
            header("Location: " . app_url('/views/industry/requirements.php'));
            exit;
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'submit_requirement') {
        $res = $reqCtrl->submitRequirement($companyId, $payload, $reqId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
            header("Location: " . app_url('/views/industry/requirement_details.php?id=' . $res['requirement_id']));
            exit;
        } else {
            $_SESSION['flash_error'] = $res['message'];
            $validationErrors = $res['errors'] ?? [];
            $validationWarnings = $res['warnings'] ?? [];
        }
    }
}

// Pre-fill defaults from existing requisition if editing
$fData = $existingReq ?? [];
$selectedTrades = $existingReq['trades'] ?? [
    ['trade_name' => 'Fitter', 'vacancies' => 5, 'qualification' => 'ITI', 'board_type' => 'Either', 'passing_year_from' => '2022', 'passing_year_to' => '2026', 'min_experience' => 'Fresher']
];
// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-file-earmark-plus me-2 text-primary"></i>
                    <?= $reqId ? "Edit Requisition ({$existingReq['requirement_id']})" : "Multi-Step Manpower Requisition Wizard" ?>
                </h4>
                <p class="text-muted small mb-0">Follow the 7-step guided process to configure trades, work conditions, compensation, and interview dates.</p>
            </div>
            <div>
                <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Requirements
                </a>
            </div>
        </div>

        <!-- 8-Step Visual Stepper Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-center" id="wizardStepper">
                    <div class="step-indicator active" data-step="1">
                        <span class="step-num">1</span>
                        <div class="step-label">General</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="2">
                        <span class="step-num">2</span>
                        <div class="step-label">Trades</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="3">
                        <span class="step-num">3</span>
                        <div class="step-label">Location</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="4">
                        <span class="step-num">4</span>
                        <div class="step-label">Salary</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="5">
                        <span class="step-num">5</span>
                        <div class="step-label">Dates</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="6">
                        <span class="step-num">6</span>
                        <div class="step-label">Interview</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-indicator" data-step="7">
                        <span class="step-num">7</span>
                        <div class="step-label">Review</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Wizard Card - Full Width Balanced Container -->
        <div class="card border-0 shadow-sm w-100 mb-4">
            <div class="card-body p-4">
                <form id="requirementWizardForm" method="POST" action="<?= app_url('/views/industry/requirement_form.php' . ($reqId ? '?id=' . $reqId : '')) ?>">
                    <?= renderCSRFField() ?>
                    <input type="hidden" name="current_step" id="currentStepInput" value="<?= $initialStep ?>">
                    <input type="hidden" name="submit_action" id="submitActionInput" value="draft">

                    <!-- STEP 1: GENERAL INFORMATION -->
                    <div class="wizard-pane active" id="stepPane1">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 1</span>General Requirement Information
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Requirement Title / Role Designation <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="f_title" class="form-control" placeholder="e.g., CNC Machine Operator & Tool Room Technician" value="<?= htmlspecialchars($fData['title'] ?? '') ?>" required>
                                <div class="form-text small">Minimum 5 characters. Must reflect shop floor job profile.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Associated Company</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($company['company_name'] ?? '') ?>" readonly>
                                <div class="form-text small">Strictly bound to authenticated enterprise.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Company Profile / Website Link</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-globe"></i></span>
                                    <input type="url" name="company_website" id="f_company_website" class="form-control" placeholder="https://www.company.com or public company profile" value="<?= htmlspecialchars($fData['company_website'] ?? ($company['website'] ?? '')) ?>">
                                    <?php $webPreview = $fData['company_website'] ?? ($company['website'] ?? ''); ?>
                                    <?php if (!empty($webPreview)): ?>
                                        <a href="<?= htmlspecialchars($webPreview) ?>" target="_blank" class="btn btn-outline-secondary" title="Preview company website / profile" id="previewWebsiteBtn">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text small">Official company website or public digital profile link accessible to candidates.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Industry Sector <span class="text-danger">*</span></label>
                                <input type="text" name="industry_sector" class="form-control" value="<?= htmlspecialchars($fData['industry_sector'] ?? ($company['industry_type'] ?? 'Automotive & Industrial Automation')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Employment Type <span class="text-danger">*</span></label>
                                <select name="employment_type" id="f_emp_type" class="form-select" required>
                                    <?php 
                                    $empOptions = ['Full Time', 'Apprenticeship', 'Fixed Term', 'OJT'];
                                    foreach ($empOptions as $eo): ?>
                                        <option value="<?= $eo ?>" <?= ($fData['employment_type'] ?? 'Full Time') === $eo ? 'selected' : '' ?>><?= $eo ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Hiring Priority <span class="text-danger">*</span></label>
                                <select name="priority" id="f_priority" class="form-select" required>
                                    <option value="Medium" <?= ($fData['priority'] ?? 'Medium') === 'Medium' ? 'selected' : '' ?>>Medium Priority</option>
                                    <option value="High" <?= ($fData['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High Priority</option>
                                    <option value="Urgent" <?= ($fData['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent / Immediate Batch</option>
                                    <option value="Low" <?= ($fData['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>Low Priority</option>
                                </select>
                            </div>



                            <div class="col-12">
                                <label class="form-label small fw-semibold">Detailed Job Description & Responsibilities <span class="text-danger">*</span></label>
                                <textarea name="job_description" id="f_job_description" class="form-control" rows="4" placeholder="Detail the operating machinery, tool maintenance, shift responsibilities, and expected technical work..." required><?= htmlspecialchars($fData['job_description'] ?? '') ?></textarea>
                                <div class="form-text small">Minimum 20 characters.</div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: TRADE & VACANCY DISTRIBUTION -->
                    <div class="wizard-pane d-none" id="stepPane2">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 2</span>Target ITI Trades & Vacancy Allocation
                        </h5>

                        <div class="alert alert-info py-2 px-3 small d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="bi bi-info-circle me-1"></i>
                                Configure multi-trade technician demand. The total vacancies will be dynamically summed across all trade items.
                            </div>
                            <div>
                                Total Vacancies: <span class="badge bg-primary fs-6" id="totalVacanciesBadge"><?= $fData['quantity_required'] ?? 5 ?></span>
                                <input type="hidden" name="quantity_required" id="f_quantity_required" value="<?= $fData['quantity_required'] ?? 5 ?>">
                            </div>
                        </div>

                        <div id="tradesContainer">
                            <?php foreach ($selectedTrades as $idx => $tr): ?>
                                <div class="trade-item border rounded p-3 mb-3 bg-light position-relative" data-trade-index="<?= $idx ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <i class="bi bi-tools me-1 text-primary"></i>Trade Position #<span class="trade-num"><?= $idx + 1 ?></span>
                                        </h6>
                                        <?php if ($idx > 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-trade-btn">
                                                <i class="bi bi-trash"></i> Remove Trade
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">ITI Trade Discipline <span class="text-danger">*</span></label>
                                            <select name="trades[<?= $idx ?>][trade_name]" class="form-select form-select-sm trade-select" required>
                                                <?php
                                                $tradesListMaster = [
                                                    'Fitter', 'Electrician', 'Machinist', 'Turner', 'Welder (Gas & Electric)',
                                                    'Instrument Mechanic', 'Tool & Die Maker', 'Electronics Mechanic',
                                                    'Wireman', 'Mechanic Motor Vehicle (MMV)', 'COPA (Computer Operator)',
                                                    'Refrigeration & AC Mechanic', 'Sheet Metal Worker', 'Draughtsman (Mechanical)'
                                                ];
                                                foreach ($tradesListMaster as $tm): ?>
                                                    <option value="<?= $tm ?>" <?= ($tr['trade_name'] ?? '') === $tm ? 'selected' : '' ?>><?= $tm ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Vacancies <span class="text-danger">*</span></label>
                                            <input type="number" name="trades[<?= $idx ?>][vacancies]" class="form-control form-control-sm trade-vacancies" min="1" max="500" value="<?= $tr['vacancies'] ?? 1 ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Qualification</label>
                                            <select name="trades[<?= $idx ?>][qualification]" class="form-select form-select-sm">
                                                <option value="ITI" <?= ($tr['qualification'] ?? 'ITI') === 'ITI' ? 'selected' : '' ?>>ITI</option>
                                                <option value="Diploma" <?= ($tr['qualification'] ?? '') === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                                                <option value="10th + ITI" <?= ($tr['qualification'] ?? '') === '10th + ITI' ? 'selected' : '' ?>>10th + ITI</option>
                                                <option value="12th + ITI" <?= ($tr['qualification'] ?? '') === '12th + ITI' ? 'selected' : '' ?>>12th + ITI</option>
                                                <option value="NAC / Apprentice" <?= ($tr['qualification'] ?? '') === 'NAC / Apprentice' ? 'selected' : '' ?>>NAC / Apprentice</option>
                                                <option value="Any Technical" <?= ($tr['qualification'] ?? '') === 'Any Technical' ? 'selected' : '' ?>>Any Technical Qualification</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Gender</label>
                                            <select name="trades[<?= $idx ?>][gender]" class="form-select form-select-sm">
                                                <option value="Any" <?= ($tr['gender'] ?? 'Any') === 'Any' ? 'selected' : '' ?>>Any</option>
                                                <option value="Male" <?= ($tr['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="Female" <?= ($tr['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Passing Batch Range</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="trades[<?= $idx ?>][passing_year_from]" class="form-control" placeholder="From (e.g. 2022)" value="<?= htmlspecialchars($tr['passing_year_from'] ?? '2022') ?>">
                                                <span class="input-group-text">to</span>
                                                <input type="text" name="trades[<?= $idx ?>][passing_year_to]" class="form-control" placeholder="To (e.g. 2026)" value="<?= htmlspecialchars($tr['passing_year_to'] ?? '2026') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Required Skills for this Trade</label>
                                            <input type="text" name="trades[<?= $idx ?>][required_skills]" class="form-control form-control-sm" placeholder="e.g. Blue print reading, Vernier calliper, CNC setup" value="<?= htmlspecialchars($tr['required_skills'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Experience Requirement</label>
                                            <select name="trades[<?= $idx ?>][min_experience]" class="form-select form-select-sm">
                                                <option value="Fresher" <?= ($tr['min_experience'] ?? 'Fresher') === 'Fresher' ? 'selected' : '' ?>>Fresher / Apprentice</option>
                                                <option value="1 Year" <?= ($tr['min_experience'] ?? '') === '1 Year' ? 'selected' : '' ?>>1 Year Experience</option>
                                                <option value="2+ Years" <?= ($tr['min_experience'] ?? '') === '2+ Years' ? 'selected' : '' ?>>2+ Years Experience</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Maximum Age</label>
                                            <input type="number" name="trades[<?= $idx ?>][max_age]" class="form-control form-control-sm" min="18" max="60" placeholder="e.g. 28 (Optional)" value="<?= htmlspecialchars((string)($tr['max_age'] ?? '')) ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addTradeBtn">
                            <i class="bi bi-plus-circle me-1"></i>Add Another ITI Trade
                        </button>
                    </div>

                    <!-- STEP 3: LOCATION & WORK CONDITIONS -->
                    <div class="wizard-pane d-none" id="stepPane3">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 3</span>Work Location & Factory Site Conditions
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Work City / Industrial Zone <span class="text-danger">*</span></label>
                                <input type="text" name="job_location_city" class="form-control" value="<?= htmlspecialchars($fData['job_location_city'] ?? ($company['city'] ?? 'Pune')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                                <input type="text" name="job_location_district" class="form-control" value="<?= htmlspecialchars($fData['job_location_district'] ?? ($company['district'] ?? 'Pune')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                                <input type="text" name="job_location_state" class="form-control" value="<?= htmlspecialchars($fData['job_location_state'] ?? ($company['state'] ?? 'Maharashtra')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Plant PIN Code <span class="text-danger">*</span></label>
                                <input type="text" name="job_location_pincode" class="form-control" pattern="[1-9][0-9]{5}" value="<?= htmlspecialchars($fData['job_location_pincode'] ?? ($company['pincode'] ?? '411001')) ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Working Hours per Shift</label>
                                <input type="text" name="working_hours" class="form-control" value="<?= htmlspecialchars($fData['working_hours'] ?? '8 Hours') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Weekly Working Days</label>
                                <select name="weekly_working_days" class="form-select">
                                    <option value="6" selected>6 Days a Week</option>
                                    <option value="5">5 Days a Week</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Rotational Shifts Allowed</label>
                                <select name="rotational_shift" class="form-select">
                                    <option value="Yes" selected>Yes, Rotational</option>
                                    <option value="No">No, Fixed General Shift</option>
                                </select>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mt-4 mb-2">On-Site Facilities & Candidate Welfare</h6>
                            <div class="col-md-3">
                                <?php
                                $savedAccom = $fData['accommodation_provided'] ?? 'No';
                                $savedAccomArr = array_map('trim', explode(',', (string)$savedAccom));
                                $hasAccomFree = in_array('Yes', $savedAccomArr) || in_array('Company Provided (Free)', $savedAccomArr);
                                $hasAccomSub  = in_array('Subsidized', $savedAccomArr) || in_array('Subsidized Hostel / Room', $savedAccomArr);
                                $hasAccomNo   = in_array('No', $savedAccomArr) || in_array('No Accommodation', $savedAccomArr) || (!$hasAccomFree && !$hasAccomSub);

                                $initialLabels = [];
                                if ($hasAccomFree) $initialLabels[] = 'Company Provided (Free)';
                                if ($hasAccomSub)  $initialLabels[] = 'Subsidized Hostel / Room';
                                if (empty($initialLabels)) $initialLabels[] = 'No Accommodation';
                                $displayAccomText = implode(', ', $initialLabels);
                                ?>
                                <label class="form-label small fw-semibold">Accommodation Facility</label>
                                <div class="dropdown position-relative" id="accomDropdownWrapper">
                                    <button class="form-select text-start d-flex justify-content-between align-items-center w-100" type="button" id="accomDropdownBtn" aria-expanded="false">
                                        <span class="text-truncate me-1" id="accomDisplay"><?= htmlspecialchars($displayAccomText) ?></span>
                                    </button>
                                    <ul class="dropdown-menu w-100 p-2 shadow-sm border" aria-labelledby="accomDropdownBtn" style="min-width: 250px;">
                                        <li>
                                            <div class="dropdown-item rounded p-2 d-flex align-items-center cursor-pointer">
                                                <input class="form-check-input m-0 me-2 accom-option" type="checkbox" name="accommodation_provided[]" value="No Accommodation" id="accom_no" <?= $hasAccomNo ? 'checked' : '' ?>>
                                                <label class="form-check-label w-100 cursor-pointer user-select-none" for="accom_no">No Accommodation</label>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-item rounded p-2 d-flex align-items-center cursor-pointer">
                                                <input class="form-check-input m-0 me-2 accom-option" type="checkbox" name="accommodation_provided[]" value="Company Provided (Free)" id="accom_free" <?= $hasAccomFree ? 'checked' : '' ?>>
                                                <label class="form-check-label w-100 cursor-pointer user-select-none" for="accom_free">Company Provided (Free)</label>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-item rounded p-2 d-flex align-items-center cursor-pointer">
                                                <input class="form-check-input m-0 me-2 accom-option" type="checkbox" name="accommodation_provided[]" value="Subsidized Hostel / Room" id="accom_subsidized" <?= $hasAccomSub ? 'checked' : '' ?>>
                                                <label class="form-check-label w-100 cursor-pointer user-select-none" for="accom_subsidized">Subsidized Hostel / Room</label>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Canteen / Food Facility</label>
                                <select name="food_facility" class="form-select">
                                    <option value="Subsidized" selected>Subsidized Canteen Meal</option>
                                    <option value="Yes">Free Food / Lunch Provided</option>
                                    <option value="No">Self-Managed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Transportation Facility</label>
                                <select name="transportation_provided" class="form-select">
                                    <option value="No" selected>No Transport Provided</option>
                                    <option value="Yes">Company Bus Routes Provided</option>
                                    <option value="Subsidized">Subsidized Bus Pass</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">PPE & Safety Equipment</label>
                                <select name="safety_equipment" class="form-select">
                                    <option value="Yes" selected>Yes, Provided by Company</option>
                                    <option value="No">Candidate Own</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Other Plant Facilities / Medical Room / Insurance</label>
                                <input type="text" name="other_facilities" class="form-control" placeholder="e.g. Free medical first-aid clinic, Group personal accident coverage..." value="<?= htmlspecialchars($fData['other_facilities'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: SALARY & COMPENSATION -->
                    <div class="wizard-pane d-none" id="stepPane4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 4</span>Salary, Compensation & Statutory Benefits
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Minimum Salary / Stipend (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="min_salary" id="f_min_salary" class="form-control" value="<?= $fData['min_salary'] ?? 15000 ?>" min="5000" step="500" required>
                                <div class="form-text small">Must be &ge; ₹12,000 for auto-approval.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Maximum Salary / Stipend (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="max_salary" id="f_max_salary" class="form-control" value="<?= $fData['max_salary'] ?? 20000 ?>" min="5000" step="500" required>
                                <div class="form-text small">Must be &ge; Minimum Salary.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Salary Frequency / Type <span class="text-danger">*</span></label>
                                <select name="salary_type" class="form-select" required>
                                    <option value="Monthly" selected>Monthly Gross</option>
                                    <option value="Stipend">Apprenticeship Monthly Stipend</option>
                                    <option value="Annual">Annual CTC</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">PF & ESIC Statutory Benefit <span class="text-danger">*</span></label>
                                <select name="pf_esic_benefit" class="form-select" required>
                                    <option value="Yes" selected>Yes, PF & ESIC Covered</option>
                                    <option value="No">No (Apprenticeship Exemption)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Overtime (OT) Availability</label>
                                <select name="overtime_available" class="form-select">
                                    <option value="No" selected>No Overtime</option>
                                    <option value="Yes">Yes, Paid OT Available</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Additional Remuneration Benefits</label>
                                <textarea name="additional_benefits" class="form-control" rows="2" placeholder="e.g. Uniform provided, annual increment policy, career progression to permanent staff..."><?= htmlspecialchars($fData['additional_benefits'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: IMPORTANT DATES -->
                    <div class="wizard-pane d-none" id="stepPane5">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 5</span>Recruitment Timelines & Important Dates
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Application Deadline Date <span class="text-danger">*</span></label>
                                <input type="date" name="application_deadline" id="f_application_deadline" class="form-control" value="<?= $fData['application_deadline'] ?? date('Y-m-d', strtotime('+15 days')) ?>" required>
                                <div class="form-text small">Must be on or after today.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Interview Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="interview_date" id="f_interview_date" class="form-control" value="<?= $fData['interview_date'] ?? date('Y-m-d', strtotime('+20 days')) ?>" required>
                                <div class="form-text small">Must be on or after deadline.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Interview End Date</label>
                                <input type="date" name="interview_end_date" id="f_interview_end_date" class="form-control" value="<?= $fData['interview_end_date'] ?? date('Y-m-d', strtotime('+22 days')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Target Joining Date <span class="text-danger">*</span></label>
                                <input type="date" name="target_joining_date" id="f_target_joining_date" class="form-control" value="<?= $fData['target_joining_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>" required>
                                <div class="form-text small">Must be on or after interview date.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Requirement Expiry Date</label>
                                <input type="date" name="requirement_expiry_date" class="form-control" value="<?= $fData['requirement_expiry_date'] ?? date('Y-m-d', strtotime('+45 days')) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- STEP 6: INTERVIEW & RECRUITMENT PROCESS -->
                    <div class="wizard-pane d-none" id="stepPane6">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 6</span>Interview Logistics & Evaluation Details
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Interview Mode <span class="text-danger">*</span></label>
                                <select name="interview_mode" class="form-select" required>
                                    <option value="Plant Campus Walk-in" selected>Plant Campus Walk-in</option>
                                    <option value="Face to Face">Face to Face (Corporate Office)</option>
                                    <option value="Virtual / Online">Virtual / Video Interview</option>
                                    <option value="Hybrid">Hybrid (Online Screening + Plant Practical)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Number of Interview Rounds</label>
                                <input type="number" name="number_of_rounds" class="form-control" min="1" max="5" value="<?= $fData['number_of_rounds'] ?? 2 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Interview Location Details</label>
                                <input type="text" name="interview_location" class="form-control" placeholder="e.g. Factory Gate 2, HR Conference Hall" value="<?= htmlspecialchars($fData['interview_location'] ?? '') ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-semibold d-block">Mandatory Evaluation Checkpoints</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="technical_interview_required" id="chkTech" value="1" checked>
                                        <label class="form-check-label small" for="chkTech">Technical Interview Required</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="practical_assessment_required" id="chkPract" value="1" checked>
                                        <label class="form-check-label small" for="chkPract">Practical Machine Test</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="document_verification_required" id="chkDoc" value="1" checked>
                                        <label class="form-check-label small" for="chkDoc">Original Document Verification</label>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mt-4 mb-2">On-Site Recruitment Coordinator</h6>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Contact Person Name</label>
                                <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($fData['contact_person'] ?? ($company['hr_contact_name'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Coordinator Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($fData['contact_email'] ?? ($company['hr_contact_email'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Coordinator Phone</label>
                                <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($fData['contact_phone'] ?? ($company['hr_contact_phone'] ?? '')) ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Interview Rounds & Process Guidelines</label>
                                <textarea name="interview_process" class="form-control" rows="2" placeholder="e.g. Round 1: Trade theory written assessment (30 mins); Round 2: Machine test on lathe/milling tool; Round 3: HR documentation..."><?= htmlspecialchars($fData['interview_process'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 7: REVIEW & FINAL SUBMISSION -->
                    <div class="wizard-pane d-none" id="stepPane7">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                            <span class="badge bg-primary me-2">Step 7</span>Review Requisition & Confirm Submission
                        </h5>

                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="bi bi-shield-lock me-1"></i>
                            Please review the consolidated specification summary before submission. Once submitted, requirements pass through the validation engine.
                        </div>

                        <!-- Live Summary Grid -->
                        <div class="card bg-light border mb-4">
                            <div class="card-body p-4" id="reviewSummaryContent">
                                <!-- Populated dynamically by JavaScript -->
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                                    <div>Compiling summary...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Footer Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top mt-4">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" style="display: none;">
                                <i class="bi bi-arrow-left me-1"></i>Previous Step
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-warning" id="saveDraftBtn">
                                <i class="bi bi-file-earmark me-1"></i>Save Draft & Resume Later
                            </button>
                            <button type="button" class="btn btn-primary px-4" id="nextStepBtn">
                                Next Step <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                            <button type="button" class="btn btn-success px-4" id="submitReqBtn" style="display: none;">
                                <i class="bi bi-send-check me-1"></i>Confirm & Submit Requirement
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<style>
#wizardStepper .step-indicator {
    cursor: pointer;
    opacity: 0.6;
    transition: all 0.2s ease;
}
#wizardStepper .step-indicator.active {
    opacity: 1;
    font-weight: bold;
}
#wizardStepper .step-indicator.completed .step-num {
    background-color: var(--bs-success);
    color: #fff;
}
#wizardStepper .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #e2e8f0;
    color: #475569;
    font-weight: bold;
    margin-bottom: 4px;
}
#wizardStepper .step-indicator.active .step-num {
    background-color: var(--bs-primary);
    color: #fff;
}
#wizardStepper .step-label {
    font-size: 0.75rem;
    color: #64748b;
}
#wizardStepper .step-divider {
    flex-grow: 1;
    height: 2px;
    background-color: #e2e8f0;
    margin: 0 4px;
    margin-bottom: 14px;
    min-width: 15px;
}
#accomDropdownWrapper {
    position: relative;
}
#accomDropdownWrapper .dropdown-menu {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    min-width: 260px !important;
    z-index: 1060 !important;
    margin-top: 4px !important;
    transform: none !important;
    background-color: #ffffff !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
}
#accomDropdownWrapper .dropdown-menu.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
#accomDropdownWrapper .dropdown-item:hover {
    background-color: #f1f5f9;
}
#accomDropdownWrapper .dropdown-item {
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentStep = <?= $initialStep ?>;
    const totalSteps = 7;
    const form = document.getElementById('requirementWizardForm');
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitReqBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const stepInput = document.getElementById('currentStepInput');
    const actionInput = document.getElementById('submitActionInput');

    function updateStep(step) {
        currentStep = step;
        stepInput.value = currentStep;

        // Panes
        for (let i = 1; i <= totalSteps; i++) {
            const pane = document.getElementById('stepPane' + i);
            if (pane) {
                if (i === currentStep) {
                    pane.classList.remove('d-none');
                } else {
                    pane.classList.add('d-none');
                }
            }

            // Stepper indicators
            const indicator = document.querySelector(`.step-indicator[data-step="${i}"]`);
            if (indicator) {
                indicator.classList.remove('active');
                if (i === currentStep) {
                    indicator.classList.add('active');
                } else if (i < currentStep) {
                    indicator.classList.add('completed');
                }
            }
        }

        // Button visibility
        prevBtn.style.display = (currentStep === 1) ? 'none' : 'inline-block';
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            compileReviewSummary();
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showInlineNotice(msg) {
        let box = document.getElementById('wizardStepNotice');
        if (!box) {
            box = document.createElement('div');
            box.id = 'wizardStepNotice';
            box.className = 'alert alert-warning alert-dismissible fade show mb-4 shadow-sm';
            box.setAttribute('role', 'alert');
            const mainForm = document.getElementById('requirementWizardForm');
            if (mainForm) {
                mainForm.parentNode.insertBefore(box, mainForm);
            }
        }
        box.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Notice:</strong> ${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearInlineNotice() {
        const box = document.getElementById('wizardStepNotice');
        if (box) {
            box.style.display = 'none';
        }
    }

    function validateCurrentStep() {
        clearInlineNotice();
        if (currentStep === 1) {
            const title = document.getElementById('f_title').value.trim();
            const desc = document.getElementById('f_job_description').value.trim();
            if (title.length < 5) {
                showInlineNotice('Requirement title must be at least 5 characters.');
                document.getElementById('f_title').focus();
                return false;
            }
            if (desc.length < 20) {
                showInlineNotice('Job description must be at least 20 characters.');
                document.getElementById('f_job_description').focus();
                return false;
            }
        } else if (currentStep === 2) {
            const trades = document.querySelectorAll('.trade-select');
            if (trades.length === 0) {
                showInlineNotice('At least one target ITI trade must be added.');
                return false;
            }
            recalcTotalVacancies();
        } else if (currentStep === 4) {
            const minSal = parseFloat(document.getElementById('f_min_salary').value || 0);
            const maxSal = parseFloat(document.getElementById('f_max_salary').value || 0);
            if (minSal <= 0) {
                showInlineNotice('Minimum salary must be greater than zero.');
                return false;
            }
            if (maxSal < minSal) {
                showInlineNotice('Maximum salary cannot be less than minimum salary.');
                return false;
            }
        } else if (currentStep === 5) {
            const deadStr = document.getElementById('f_application_deadline').value;
            const intStr = document.getElementById('f_interview_date').value;
            const joinStr = document.getElementById('f_target_joining_date').value;

            if (!deadStr || !intStr || !joinStr) {
                showInlineNotice('Please provide valid Application Deadline, Interview Date, and Joining Date.');
                return false;
            }

            const today = new Date().toISOString().slice(0, 10);
            if (deadStr < today) {
                showInlineNotice('Application deadline cannot be in the past.');
                return false;
            }
            if (intStr < deadStr) {
                showInlineNotice('Interview date must occur on or after application deadline.');
                return false;
            }
            if (joinStr < intStr) {
                showInlineNotice('Target joining date must occur on or after interview date.');
                return false;
            }
        }
        return true;
    }

    nextBtn.addEventListener('click', function () {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                updateStep(currentStep + 1);
            }
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            updateStep(currentStep - 1);
        }
    });

    // Stepper header clicks
    document.querySelectorAll('.step-indicator').forEach(ind => {
        ind.addEventListener('click', function () {
            const target = parseInt(this.getAttribute('data-step'));
            if (target < currentStep || validateCurrentStep()) {
                updateStep(target);
            }
        });
    });

    // Save Draft Button
    saveDraftBtn.addEventListener('click', function () {
        actionInput.value = 'save_draft';
        form.submit();
    });

    // Final Submit Button
    submitBtn.addEventListener('click', function () {
        actionInput.value = 'submit_requirement';
        form.submit();
    });

    // Trade Dynamic Add / Remove / Calculate
    let tradeCounter = <?= count($selectedTrades) ?>;
    const addTradeBtn = document.getElementById('addTradeBtn');
    const container = document.getElementById('tradesContainer');

    function recalcTotalVacancies() {
        let total = 0;
        document.querySelectorAll('.trade-vacancies').forEach(inp => {
            total += parseInt(inp.value || 0);
        });
        document.getElementById('totalVacanciesBadge').textContent = total;
        document.getElementById('f_quantity_required').value = total;
    }

    container.addEventListener('input', function (e) {
        if (e.target.classList.contains('trade-vacancies')) {
            recalcTotalVacancies();
        }
    });

    addTradeBtn.addEventListener('click', function () {
        tradeCounter++;
        const item = document.createElement('div');
        item.className = 'trade-item border rounded p-3 mb-3 bg-light position-relative';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-tools me-1 text-primary"></i>Trade Position #<span class="trade-num">${tradeCounter}</span>
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-trade-btn">
                    <i class="bi bi-trash"></i> Remove Trade
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">ITI Trade Discipline <span class="text-danger">*</span></label>
                    <select name="trades[${tradeCounter}][trade_name]" class="form-select form-select-sm trade-select" required>
                        <option value="Electrician">Electrician</option>
                        <option value="Fitter">Fitter</option>
                        <option value="Machinist">Machinist</option>
                        <option value="Turner">Turner</option>
                        <option value="Welder (Gas & Electric)">Welder (Gas & Electric)</option>
                        <option value="Tool & Die Maker">Tool & Die Maker</option>
                        <option value="Instrument Mechanic">Instrument Mechanic</option>
                        <option value="COPA (Computer Operator)">COPA (Computer Operator)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Vacancies <span class="text-danger">*</span></label>
                    <input type="number" name="trades[${tradeCounter}][vacancies]" class="form-control form-control-sm trade-vacancies" min="1" max="500" value="5" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Qualification</label>
                    <select name="trades[${tradeCounter}][qualification]" class="form-select form-select-sm">
                        <option value="ITI" selected>ITI</option>
                        <option value="Diploma">Diploma</option>
                        <option value="10th + ITI">10th + ITI</option>
                        <option value="12th + ITI">12th + ITI</option>
                        <option value="NAC / Apprentice">NAC / Apprentice</option>
                        <option value="Any Technical">Any Technical Qualification</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="trades[${tradeCounter}][gender]" class="form-select form-select-sm">
                        <option value="Any" selected>Any</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Passing Batch Range</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="trades[${tradeCounter}][passing_year_from]" class="form-control" placeholder="From" value="2022">
                        <span class="input-group-text">to</span>
                        <input type="text" name="trades[${tradeCounter}][passing_year_to]" class="form-control" placeholder="To" value="2026">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Required Skills for this Trade</label>
                    <input type="text" name="trades[${tradeCounter}][required_skills]" class="form-control form-control-sm" placeholder="e.g. Wiring, Soldering, Circuit tracing">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Experience Requirement</label>
                    <select name="trades[${tradeCounter}][min_experience]" class="form-select form-select-sm">
                        <option value="Fresher">Fresher / Apprentice</option>
                        <option value="1 Year">1 Year Experience</option>
                        <option value="2+ Years">2+ Years Experience</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Maximum Age</label>
                    <input type="number" name="trades[${tradeCounter}][max_age]" class="form-control form-control-sm" min="18" max="60" placeholder="e.g. 28 (Optional)">
                </div>
            </div>
        `;
        container.appendChild(item);
        recalcTotalVacancies();
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-trade-btn')) {
            const item = e.target.closest('.trade-item');
            if (document.querySelectorAll('.trade-item').length > 1) {
                item.remove();
                recalcTotalVacancies();
            } else {
                showInlineNotice('At least one trade position must remain.');
            }
        }
    });

    // Compile Review Summary
    function compileReviewSummary() {
        const title = document.getElementById('f_title').value;
        const totalVac = document.getElementById('f_quantity_required').value;
        const empType = document.getElementById('f_emp_type').value;
        const priority = document.getElementById('f_priority').value;
        const minSal = document.getElementById('f_min_salary').value;
        const maxSal = document.getElementById('f_max_salary').value;
        const deadline = document.getElementById('f_application_deadline').value;
        const joining = document.getElementById('f_target_joining_date').value;
        const companyWebsite = document.getElementById('f_company_website') ? document.getElementById('f_company_website').value.trim() : '';

        let tradesHtml = '';
        document.querySelectorAll('.trade-item').forEach((item, idx) => {
            const trName = item.querySelector('.trade-select').value;
            const vac = item.querySelector('.trade-vacancies').value;
            tradesHtml += `<span class="badge bg-primary-subtle text-primary border border-primary me-2 mb-1">${trName}: ${vac} Vacancies</span>`;
        });

        const websiteHtml = companyWebsite 
            ? `<div class="mt-2 small text-muted"><i class="bi bi-globe me-1 text-primary"></i><strong>Company Profile / Website:</strong> <a href="${companyWebsite.startsWith('http') ? companyWebsite : 'https://' + companyWebsite}" target="_blank" class="fw-semibold text-decoration-none">${companyWebsite} <i class="bi bi-box-arrow-up-right small"></i></a></div>`
            : '';

        const summaryContainer = document.getElementById('reviewSummaryContent');
        summaryContainer.innerHTML = `
            <div class="row g-3">
                <div class="col-md-8">
                    <h5 class="fw-bold text-dark mb-1">${title}</h5>
                    <div class="text-muted small mb-2"><i class="bi bi-tag me-1"></i>${empType} &bull; Priority: <span class="badge bg-secondary">${priority}</span></div>
                    <div class="mb-3">${tradesHtml}</div>
                    ${websiteHtml}
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Total Vacancies</span>
                    <h3 class="fw-bold text-primary mb-0">${totalVac}</h3>
                </div>
                <div class="col-md-4 border-top pt-2">
                    <span class="text-muted small d-block">Monthly Salary Range:</span>
                    <strong class="text-success">₹${Number(minSal).toLocaleString('en-IN')} &mdash; ₹${Number(maxSal).toLocaleString('en-IN')}</strong>
                </div>
                <div class="col-md-4 border-top pt-2">
                    <span class="text-muted small d-block">Application Deadline:</span>
                    <strong>${deadline}</strong>
                </div>
                <div class="col-md-4 border-top pt-2">
                    <span class="text-muted small d-block">Target Joining Date:</span>
                    <strong class="text-primary">${joining}</strong>
                </div>
            </div>
        `;
    }

    // Accommodation Multi-Select Handler
    const accomBtn = document.getElementById('accomDropdownBtn');
    const accomWrapper = document.getElementById('accomDropdownWrapper');
    const accomMenu = accomWrapper ? accomWrapper.querySelector('.dropdown-menu') : null;
    const accomCheckboxes = document.querySelectorAll('.accom-option');
    const accomDisplay = document.getElementById('accomDisplay');

    if (accomBtn && accomMenu) {
        accomBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isCurrentlyOpen = accomMenu.classList.contains('show');
            if (isCurrentlyOpen) {
                accomMenu.classList.remove('show');
                accomBtn.setAttribute('aria-expanded', 'false');
            } else {
                accomMenu.classList.add('show');
                accomBtn.setAttribute('aria-expanded', 'true');
            }
        });

        // Keep open while clicking inside dropdown menu
        accomMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        // Close when clicking anywhere outside
        document.addEventListener('click', function (e) {
            if (accomWrapper && !accomWrapper.contains(e.target)) {
                accomMenu.classList.remove('show');
                accomBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function updateAccomDisplay() {
        if (!accomDisplay) return;
        const checkedValues = Array.from(accomCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (checkedValues.length === 0) {
            accomDisplay.textContent = 'No Accommodation';
            const noCb = document.getElementById('accom_no');
            if (noCb) noCb.checked = true;
        } else {
            accomDisplay.textContent = checkedValues.join(', ');
        }
    }

    accomCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.id === 'accom_no' && this.checked) {
                // If "No Accommodation" is selected, uncheck other choices
                accomCheckboxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            } else if (this.checked && this.id !== 'accom_no') {
                // If any positive accommodation option is checked, uncheck "No Accommodation"
                const noCb = document.getElementById('accom_no');
                if (noCb) noCb.checked = false;
            }
            updateAccomDisplay();
        });
    });

    // Initialize first view
    updateStep(currentStep);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

