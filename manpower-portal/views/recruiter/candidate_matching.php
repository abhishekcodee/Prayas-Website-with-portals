<?php
/**
 * Recruiter Candidate Sourcing & Algorithmic Matching Console
 * Organization: PRAYAS – The Automation Academy
 * Module: Part 09 Candidate Matching Engine
 */

declare(strict_types=1);

$pageTitle = 'Candidate Sourcing & Matching — PRAYAS Recruitment Desk';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../models/CandidateMatchingModel.php';

requireRole(['recruiter', 'recruitment_admin', 'super_admin']);

$pdo = Database::getConnection();
$matchingModel = new CandidateMatchingModel($pdo);
$userId = getUserId();

// Fetch approved requirements for dropdown
$reqListStmt = $pdo->query("SELECT mr.id, mr.requirement_id, mr.title, mr.quantity_required, c.company_name 
    FROM `manpower_requirements` mr 
    JOIN `companies` c ON c.id = mr.company_id 
    WHERE mr.status IN ('APPROVED', 'IN_PROGRESS', 'PARTIALLY_FULFILLED') 
    ORDER BY mr.id DESC");
$approvedReqs = $reqListStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected requirement ID from GET or default to first
$selectedReqId = !empty($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : (!empty($approvedReqs[0]['id']) ? (int)$approvedReqs[0]['id'] : 0);

// POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'run_matching' && $selectedReqId > 0) {
        $runResult = $matchingModel->executeMatchingRun($selectedReqId, $userId);
        if ($runResult['success']) {
            $_SESSION['flash_success'] = $runResult['message'];
        } else {
            $_SESSION['flash_error'] = $runResult['message'];
        }
    } elseif ($action === 'shortlist') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $remarks = trim((string)($_POST['remarks'] ?? ''));
        if ($matchId > 0) {
            $matchingModel->shortlistCandidate($matchId, $userId, $remarks);
            $_SESSION['flash_success'] = 'Candidate successfully shortlisted into the recruitment screening queue.';
        }
    } elseif ($action === 'reject') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $reason  = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($matchId > 0 && !empty($reason)) {
            $matchingModel->rejectCandidate($matchId, $userId, $reason);
            $_SESSION['flash_warning'] = 'Candidate rejected with recorded rationale.';
        } else {
            $_SESSION['flash_error'] = 'Rejection reason is mandatory.';
        }
    } elseif ($action === 'share_industry') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        if ($matchId > 0) {
            $matchingModel->shareWithIndustry($matchId, $userId);
            $_SESSION['flash_success'] = 'Candidate profile authorized and released to industry portal.';
        }
    }

    header("Location: " . app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId . '&' . http_build_query(array_diff_key($_GET, ['requirement_id' => '']))));
    exit;
}

// Filters
$filters = [
    'eligibility' => $_GET['eligibility'] ?? '',
    'status'      => $_GET['status'] ?? '',
    'score_tier'  => $_GET['score_tier'] ?? '',
    'search'      => trim((string)($_GET['search'] ?? ''))
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$matches = [];
$metrics = [];
$currentReq = null;

if ($selectedReqId > 0) {
    $matches = $matchingModel->getMatchesForRequirement($selectedReqId, $filters, $limit, $offset);
    $metrics = $matchingModel->getMatchingMetrics($selectedReqId);

    $reqStmt = $pdo->prepare("SELECT mr.*, c.company_name FROM `manpower_requirements` mr JOIN `companies` c ON c.id = mr.company_id WHERE mr.id = :id LIMIT 1");
    $reqStmt->execute([':id' => $selectedReqId]);
    $currentReq = $reqStmt->fetch(PDO::FETCH_ASSOC);
}
// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="page-container">
            <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--prayas-navy, #0F172A);">
                        <i class="bi bi-cpu text-primary me-2"></i>Candidate Sourcing & Algorithmic Matching
                    </h4>
                    <p class="text-muted small mb-0">
                        7-factor weighted scoring (Trade 30%, Qual 15%, Batch 15%, Skills 20%, Location 10%, Exp 5%, Avail 5%) across accredited PRAYAS ITI databases.
                    </p>
                </div>
                <?php if ($selectedReqId > 0): ?>
                    <form method="POST" action="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>">
                        <?= renderCSRFField() ?>
                        <input type="hidden" name="action" value="run_matching">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm" style="background: var(--prayas-coral, #EF6B6B); border-color: var(--prayas-coral, #EF6B6B);">
                            <i class="bi bi-arrow-repeat me-1"></i>Run Matching Algorithm
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Requirement Selector Bar -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
                <div class="card-body p-3">
                    <form method="GET" action="<?= app_url('views/recruiter/candidate_matching.php') ?>" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted mb-1">Target Manpower Requisition</label>
                            <select name="requirement_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php if (empty($approvedReqs)): ?>
                                    <option value="">No approved requisitions available</option>
                                <?php else: ?>
                                    <?php foreach ($approvedReqs as $r): ?>
                                        <option value="<?= (int)$r['id'] ?>" <?= $selectedReqId === (int)$r['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['requirement_id']) ?> — <?= htmlspecialchars($r['title']) ?> (<?= htmlspecialchars($r['company_name']) ?>, <?= (int)$r['quantity_required'] ?> Vacancies)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Score Tier</label>
                            <select name="score_tier" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Tiers</option>
                                <option value="tier1" <?= ($filters['score_tier'] ?? '') === 'tier1' ? 'selected' : '' ?>>Excellent Match (&ge; 90%)</option>
                                <option value="tier2" <?= ($filters['score_tier'] ?? '') === 'tier2' ? 'selected' : '' ?>>Strong Match (75% &ndash; 89%)</option>
                                <option value="tier3" <?= ($filters['score_tier'] ?? '') === 'tier3' ? 'selected' : '' ?>>Possible Match (60% &ndash; 74%)</option>
                                <option value="below70" <?= ($filters['score_tier'] ?? '') === 'below70' ? 'selected' : '' ?>>Low Match (&lt; 60%)</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="Matched" <?= ($filters['status'] ?? '') === 'Matched' ? 'selected' : '' ?>>Matched Only</option>
                                <option value="Shortlisted" <?= ($filters['status'] ?? '') === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted Only</option>
                                <option value="Rejected" <?= ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected Only</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <a href="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($currentReq): ?>
                <!-- Operational Metrics KPI Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 10px;">
                            <div class="text-muted small">Total Scanned</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= number_format((int)($metrics['total_matched'] ?? 0)) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3 border-start border-4 border-success" style="border-radius: 10px;">
                            <div class="text-muted small">Eligible Candidates</div>
                            <h4 class="fw-bold mb-0 text-success"><?= number_format((int)($metrics['eligible_count'] ?? 0)) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3 border-start border-4 border-primary" style="border-radius: 10px;">
                            <div class="text-muted small">Shortlisted</div>
                            <h4 class="fw-bold mb-0 text-primary"><?= number_format((int)($metrics['shortlisted_count'] ?? 0)) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 10px;">
                            <div class="text-muted small">Tier 1 (&ge;85%)</div>
                            <h4 class="fw-bold mb-0 text-success"><?= number_format((int)($metrics['tier1_count'] ?? 0)) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 10px;">
                            <div class="text-muted small">Tier 2 (70-84%)</div>
                            <h4 class="fw-bold mb-0 text-info"><?= number_format((int)($metrics['tier2_count'] ?? 0)) ?></h4>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 10px;">
                            <div class="text-muted small">Released to Industry</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= number_format((int)($metrics['shared_with_industry'] ?? 0)) ?></h4>
                        </div>
                    </div>
                </div>

                <!-- Matches Table -->
                <div class="card border-0 shadow-sm" style="border-radius: 10px;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-people me-2" style="color: var(--prayas-coral, #EF6B6B);"></i>Ranked Candidate Pool for <?= htmlspecialchars($currentReq['requirement_id']) ?>
                        </h6>
                        <span class="badge bg-light text-secondary border">Showing <?= count($matches) ?> candidates</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($matches)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h6 class="fw-bold">No Matching Candidates Found</h6>
                                <p class="small text-muted mb-3">Click 'Run Matching Algorithm' above to scan both candidate databases against this requisition.</p>
                                <form method="POST" action="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>">
                                    <?= renderCSRFField() ?>
                                    <input type="hidden" name="action" value="run_matching">
                                    <button type="submit" class="btn btn-primary btn-sm" style="background: var(--prayas-coral, #EF6B6B); border-color: var(--prayas-coral, #EF6B6B);">
                                        <i class="bi bi-arrow-repeat me-1"></i>Execute Matching Engine
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary small">
                                        <tr>
                                            <th style="width: 20%;">Candidate Name</th>
                                            <th style="width: 14%;">Trade</th>
                                            <th style="width: 10%;">Passing Year</th>
                                            <th style="width: 12%;">Source DB</th>
                                            <th style="width: 18%;">Matching Score & Tier</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 16%; text-align: right;">Recruiter Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($matches as $m): ?>
                                            <?php 
                                                $score = floatval($m['match_score']);
                                                $scoreBadge = match(true) {
                                                    $score >= 90.0 => 'bg-success',
                                                    $score >= 75.0 => 'bg-primary',
                                                    $score >= 60.0 => 'bg-info text-dark',
                                                    default        => 'bg-secondary'
                                                };
                                                $tierName = match(true) {
                                                    $score >= 90.0 => 'Excellent Match',
                                                    $score >= 75.0 => 'Strong Match',
                                                    $score >= 60.0 => 'Possible Match',
                                                    default        => 'Low Match'
                                                };
                                                $isEligible = ($m['eligibility_status'] === 'ELIGIBLE');
                                                $details = !empty($m['match_details_json']) ? json_decode($m['match_details_json'], true) : [];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['candidate_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.72rem;">ID: #<?= (int)$m['candidate_source_id'] ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['trade']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="small text-secondary"><?= htmlspecialchars($m['passing_year'] ?: 'N/A') ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                        <?= $m['candidate_source'] === 'prayas_dashboard' ? 'PRAYAS ITI' : 'ITI Students' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge <?= $scoreBadge ?>" style="font-size: 0.82rem; min-width: 48px;">
                                                            <?= number_format($score, 1) ?>%
                                                        </span>
                                                        <div class="progress flex-grow-1" style="height: 6px;">
                                                            <div class="progress-bar <?= $scoreBadge ?>" style="width: <?= min(100, $score) ?>%;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                                        <span class="text-muted" style="font-size: 0.7rem;"><?= $tierName ?></span>
                                                        <?php if (!$isEligible): ?>
                                                            <span class="badge bg-danger text-white" style="font-size: 0.65rem;">
                                                                <i class="bi bi-x-circle me-1"></i>Not Eligible
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border small">
                                                        <?= htmlspecialchars($m['status']) ?>
                                                    </span>
                                                    <?php if ((bool)$m['is_shared_with_industry']): ?>
                                                        <div class="text-success small" style="font-size: 0.68rem;">
                                                            <i class="bi bi-eye-fill me-1"></i>Shared with HR
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right;">
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Explanation Modal Trigger -->
                                                        <button type="button" class="btn btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#explainModal<?= (int)$m['id'] ?>" title="View 7-Factor Score Explanation">
                                                            <i class="bi bi-info-circle"></i>
                                                        </button>

                                                        <?php if ($m['status'] !== 'Shortlisted'): ?>
                                                            <button type="button" class="btn btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#shortlistModal<?= (int)$m['id'] ?>" title="Shortlist Candidate">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($m['status'] === 'Shortlisted' && !(bool)$m['is_shared_with_industry']): ?>
                                                            <form method="POST" action="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>" class="d-inline" onsubmit="return confirm('Authorize candidate profile release to industry portal?');">
                                                                <?= renderCSRFField() ?>
                                                                <input type="hidden" name="action" value="share_industry">
                                                                <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-primary py-1 px-2" title="Release to Industry">
                                                                    <i class="bi bi-share"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <?php if ($m['status'] !== 'Rejected'): ?>
                                                            <button type="button" class="btn btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int)$m['id'] ?>" title="Reject Candidate">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Score Explanation Modal -->
                                                    <div class="modal fade" id="explainModal<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content border-0 shadow">
                                                                <div class="modal-header py-2 text-white" style="background: var(--prayas-navy, #0F172A);">
                                                                    <h6 class="modal-title fw-bold">Score Breakdown — <?= htmlspecialchars($m['candidate_name']) ?></h6>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                        <div>
                                                                            <span class="text-muted small">Total Algorithmic Score</span>
                                                                            <h4 class="fw-bold mb-0 text-primary"><?= number_format($score, 1) ?> / 100</h4>
                                                                        </div>
                                                                        <div>
                                                                            <span class="badge <?= $isEligible ? 'bg-success' : 'bg-danger' ?> px-3 py-2">
                                                                                <?= $isEligible ? 'ELIGIBLE' : 'NOT ELIGIBLE' ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>

                                                                    <?php if (!$isEligible && !empty($m['eligibility_reasons'])): ?>
                                                                        <div class="alert alert-danger small py-2 mb-3">
                                                                            <strong>Gate Failure:</strong> <?= htmlspecialchars($m['eligibility_reasons']) ?>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <table class="table table-sm table-bordered small mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th>Scoring Dimension</th>
                                                                                <th style="width: 25%;">Points Awarded</th>
                                                                                <th>Diagnostic Notes</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><strong>Trade Alignment</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['trade_score']), 1) ?> / 30.0</td>
                                                                                <td><?= htmlspecialchars($details['trade']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Qualification Alignment</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['qual_score']), 1) ?> / 15.0</td>
                                                                                <td><?= htmlspecialchars($details['qual']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Passing Batch Year</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['batch_score']), 1) ?> / 15.0</td>
                                                                                <td><?= htmlspecialchars($details['batch']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Technical Skills</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['skills_score']), 1) ?> / 20.0</td>
                                                                                <td><?= htmlspecialchars($details['skills']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Location Proximity</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['location_score']), 1) ?> / 10.0</td>
                                                                                <td><?= htmlspecialchars($details['location']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Work Experience</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['exp_score']), 1) ?> / 5.0</td>
                                                                                <td><?= htmlspecialchars($details['experience']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Joining Availability</strong></td>
                                                                                <td class="fw-bold"><?= number_format(floatval($m['avail_score']), 1) ?> / 5.0</td>
                                                                                <td><?= htmlspecialchars($details['availability']['notes'] ?? 'Evaluated') ?></td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <div class="modal-footer py-2">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Shortlist Modal -->
                                                    <div class="modal fade" id="shortlistModal<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content border-0 shadow">
                                                                <form method="POST" action="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>">
                                                                    <?= renderCSRFField() ?>
                                                                    <input type="hidden" name="action" value="shortlist">
                                                                    <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                                                                    <div class="modal-header bg-success text-white py-2">
                                                                        <h6 class="modal-title fw-bold">Shortlist <?= htmlspecialchars($m['candidate_name']) ?></h6>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body p-3">
                                                                        <p class="small text-muted mb-2">Shortlisting moves the candidate into the active recruitment screening queue.</p>
                                                                        <label class="form-label small fw-semibold">Recruiter Remarks (Optional)</label>
                                                                        <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Candidate technical background and screening comments..."></textarea>
                                                                    </div>
                                                                    <div class="modal-footer py-2">
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success btn-sm">Confirm Shortlist</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Reject Modal -->
                                                    <div class="modal fade" id="rejectModal<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content border-0 shadow">
                                                                <form method="POST" action="<?= app_url('views/recruiter/candidate_matching.php?requirement_id=' . $selectedReqId) ?>">
                                                                    <?= renderCSRFField() ?>
                                                                    <input type="hidden" name="action" value="reject">
                                                                    <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                                                                    <div class="modal-header bg-danger text-white py-2">
                                                                        <h6 class="modal-title fw-bold">Reject <?= htmlspecialchars($m['candidate_name']) ?></h6>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body p-3">
                                                                        <label class="form-label small fw-semibold">Mandatory Rejection Rationale <span class="text-danger">*</span></label>
                                                                        <textarea name="rejection_reason" class="form-control form-control-sm" rows="3" required placeholder="Specify why this candidate is not suitable for this requisition..."></textarea>
                                                                    </div>
                                                                    <div class="modal-footer py-2">
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
