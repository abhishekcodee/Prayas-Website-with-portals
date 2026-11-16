<?php
/**
 * End-to-End Recruitment Pipeline Console (Screening to Joining)
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + PDO + MySQL + Bootstrap 5
 * Module: Part 10 Recruitment Pipeline Management System
 */

declare(strict_types=1);

$pageTitle = 'Recruitment Pipeline (Screening to Joining) — PRAYAS Recruitment Desk';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/PipelineController.php';

requireRole(['recruiter', 'recruitment_admin', 'super_admin']);

$pdo = Database::getConnection();
$pipelineCtrl = new PipelineController($pdo);
$userId = getUserId();

// -----------------------------------------------------------------------------
// AJAX TIMELINE FETCHER (AUDIT TRAIL)
// -----------------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'timeline' && !empty($_GET['match_id'])) {
    $matchId = (int)$_GET['match_id'];
    $history = $pipelineCtrl->getStageHistory($matchId);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

// -----------------------------------------------------------------------------
// POST REQUEST PROCESSING (LIFECYCLE TRANSITIONS & BULK ACTIONS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'record_screening') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $res = $pipelineCtrl->recordScreening($matchId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'schedule_interview') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $res = $pipelineCtrl->scheduleInterview($matchId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'record_feedback') {
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        $res = $pipelineCtrl->recordInterviewFeedback($interviewId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'record_selection') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $res = $pipelineCtrl->recordSelectionDecision($matchId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'release_offer') {
        $selectionId = (int)($_POST['selection_id'] ?? 0);
        $res = $pipelineCtrl->createAndReleaseOffer($selectionId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'record_offer_response') {
        $offerId = (int)($_POST['offer_id'] ?? 0);
        $response = trim((string)($_POST['offer_response'] ?? ''));
        $notes = trim((string)($_POST['response_notes'] ?? ''));
        $res = $pipelineCtrl->recordOfferResponse($offerId, $response, $notes, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'confirm_joining') {
        $selectionId = (int)($_POST['selection_id'] ?? 0);
        $res = $pipelineCtrl->confirmJoining($selectionId, $_POST, $userId);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    } elseif ($action === 'toggle_sharing') {
        $matchId = (int)($_POST['match_id'] ?? 0);
        $shareVal = !empty($_POST['share_with_industry']) ? 1 : 0;
        $pdo->prepare("UPDATE `candidate_matches` SET `is_shared_with_industry` = :sh WHERE id = :id")
            ->execute([':sh' => $shareVal, ':id' => $matchId]);
        $_SESSION['flash_success'] = $shareVal ? 'Candidate profile shared with employer.' : 'Candidate profile unshared from employer.';
    } elseif ($action === 'bulk_action') {
        $bulkOp = trim((string)($_POST['bulk_operation'] ?? ''));
        $selectedMatches = array_map('intval', (array)($_POST['selected_matches'] ?? []));
        $bulkRemarks = trim((string)($_POST['bulk_remarks'] ?? ''));
        $payload = [
            'remarks'            => $bulkRemarks,
            'interview_datetime' => $_POST['bulk_interview_datetime'] ?? date('Y-m-d H:i:s', strtotime('+3 days 10:00:00')),
            'venue_or_link'      => $_POST['bulk_venue'] ?? 'Main Plant Office',
            'interview_mode'     => $_POST['bulk_mode'] ?? 'In-Person',
            'interview_round'    => 'Round 1 - Technical'
        ];
        $res = $pipelineCtrl->executeBulkAction($bulkOp, $selectedMatches, $payload, $userId, null);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    }

    // Preserve GET query string on redirect
    $redirectQuery = http_build_query($_GET);
    header("Location: " . app_url('/views/recruiter/recruitment_pipeline.php') . ($redirectQuery ? '?' . $redirectQuery : ''));
    exit;
}

// -----------------------------------------------------------------------------
// FILTER & QUERY SETUP
// -----------------------------------------------------------------------------
$reqFilter     = !empty($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : null;
$compFilter    = !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null;
$stageFilter   = !empty($_GET['stage']) ? trim((string)$_GET['stage']) : null;
$searchFilter  = !empty($_GET['search']) ? trim((string)$_GET['search']) : null;
$tradeFilter   = !empty($_GET['trade']) ? trim((string)$_GET['trade']) : null;

// Fetch Requisition Dropdown list
$reqList = $pdo->query("SELECT mr.id, mr.requirement_id, mr.title, c.company_name 
    FROM `manpower_requirements` mr 
    JOIN `companies` c ON c.id = mr.company_id 
    ORDER BY mr.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Companies Dropdown list
$compList = $pdo->query("SELECT id, company_name FROM `companies` ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Real-time Funnel Metrics
$funnel = $pipelineCtrl->getPipelineFunnelMetrics($reqFilter, $compFilter);

// Filter params for candidate listing
$filters = [
    'requirement_id' => $reqFilter,
    'company_id'     => $compFilter,
    'stage'          => $stageFilter,
    'search'         => $searchFilter,
    'trade'          => $tradeFilter
];

$candidates = $pipelineCtrl->getPipelineCandidates($filters, 100, 0);

// Helper for stage badge color
function getStageBadgeClass(string $stage): string {
    return match ($stage) {
        'Matched'          => 'bg-secondary text-white',
        'Shortlisted'      => 'bg-info text-dark',
        'Screened'         => 'bg-primary text-white',
        'Scheduled'        => 'bg-warning text-dark',
        'Completed'        => 'bg-indigo text-white',
        'Selected'         => 'bg-success text-white',
        'Offer Released'   => 'bg-teal text-white',
        'Offer Accepted'   => 'bg-success text-white',
        'Offer Declined'   => 'bg-danger text-white',
        'Joining Pending'  => 'bg-warning text-dark',
        'Joined'           => 'bg-success text-white',
        'Rejected'         => 'bg-danger text-white',
        'On Hold'          => 'bg-dark text-white',
        'Withdrawn'        => 'bg-secondary text-white',
        'No Show'          => 'bg-danger text-white',
        'Closed'           => 'bg-dark text-white',
        default            => 'bg-light text-dark'
    };
}
// Include layout partials now that headers/redirects are safely handled
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Page Header -->
        <div class="card-enterprise mb-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #ffffff; border: none;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge" style="background-color: #EF6B6B; color: #ffffff; font-weight: 700; letter-spacing: 0.5px; margin-right: 12px; font-size: 0.75rem;">
                                RECRUITMENT OPERATIONS
                            </span>
                            <span class="text-white-50 small">PRAYAS Automated Pipeline Engine</span>
                        </div>
                        <h2 class="h4 fw-bold mb-1 text-white">
                            Recruitment Pipeline (Screening to Joining)
                        </h2>
                        <p class="text-white-50 small mb-0">
                            Comprehensive lifecycle workflow: Screening &rarr; Interviews &rarr; Selection &rarr; Offer &rarr; Ground Joining Confirmation.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= app_url('/views/recruiter/candidate_matching.php') ?><?= $reqFilter ? '?requirement_id=' . $reqFilter : '' ?>" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-cpu me-1"></i>Sourcing &amp; Matching
                        </a>
                        <a href="<?= app_url('/views/recruiter/recruitment_reports.php') ?><?= $reqFilter ? '?requirement_id=' . $reqFilter : '' ?>" class="btn btn-sm text-white" style="background-color: #EF6B6B;">
                            <i class="bi bi-pie-chart-fill me-1"></i>Pipeline Reports &amp; Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-Time Pipeline Funnel KPI Bar -->
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3 border-secondary">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">1. Sourced</div>
                    <div class="h5 fw-bold mb-0 text-dark"><?= (int)($funnel['total_sourced'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Matched Pools</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3 border-info">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">2. Shortlisted</div>
                    <div class="h5 fw-bold mb-0 text-info"><?= (int)($funnel['shortlisted'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Approved Pools</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3 border-primary">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">3. Screened</div>
                    <div class="h5 fw-bold mb-0 text-primary"><?= (int)($funnel['screened'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Passed Telephonic</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3 border-warning">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">4. Interviewed</div>
                    <div class="h5 fw-bold mb-0 text-warning"><?= (int)($funnel['interviewed'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= (int)($funnel['scheduled'] ?? 0) ?> Scheduled</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3 border-success">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">5. Selected</div>
                    <div class="h5 fw-bold mb-0 text-success"><?= (int)($funnel['selected'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.68rem;">Cleared Rounds</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-2 h-100 border-start border-3" style="border-left-color: #20c997 !important;">
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">6. Offers &amp; Joined</div>
                    <div class="h5 fw-bold mb-0 text-dark"><?= (int)($funnel['joined'] ?? 0) ?> <span class="fs-6 text-muted fw-normal">/ <?= (int)($funnel['offers_released'] ?? 0) ?></span></div>
                    <small class="text-muted" style="font-size: 0.68rem;"><?= (int)($funnel['offers_accepted'] ?? 0) ?> Accepted</small>
                </div>
            </div>
        </div>

        <!-- Multi-Factor Filter Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Requisition</label>
                        <select name="requirement_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Requisitions</option>
                            <?php foreach ($reqList as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $reqFilter === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['requirement_id']) ?> &mdash; <?= htmlspecialchars($r['title']) ?> (<?= htmlspecialchars($r['company_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Company</label>
                        <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Companies</option>
                            <?php foreach ($compList as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $compFilter === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Pipeline Stage</label>
                        <select name="stage" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Stages</option>
                            <?php foreach (PipelineController::STAGES as $stageKey => $stageLabel): ?>
                                <option value="<?= htmlspecialchars($stageLabel) ?>" <?= $stageFilter === $stageLabel ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stageLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Search Candidate / Trade</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Candidate, Trade, Roll No..." value="<?= htmlspecialchars($searchFilter ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex gap-1 align-self-end">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- FORM WRAPPER FOR SAFE BULK ACTIONS -->
        <form id="bulkActionForm" method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
            <?= renderCSRFField() ?>
            <input type="hidden" name="action" value="bulk_action">

            <!-- SAFE BULK ACTION TOOLBAR -->
            <div class="card border-0 shadow-sm mb-3" style="background: #f8fafc;">
                <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark" id="selectedCountBadge">0 Selected</span>
                        <span class="small text-muted">Select candidates using checkboxes below to perform bulk recruitment transitions:</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select name="bulk_operation" id="bulkOperationSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="">-- Choose Bulk Action --</option>
                            <option value="bulk_shortlist">Bulk Shortlist Candidates</option>
                            <option value="bulk_advance">Bulk Advance Next Stage</option>
                            <option value="bulk_share">Bulk Release / Share with Employer</option>
                            <option value="bulk_hold">Bulk Place on Hold</option>
                            <option value="bulk_schedule_interview">Bulk Schedule Interview Drive</option>
                        </select>
                        <input type="text" name="bulk_remarks" class="form-control form-control-sm" placeholder="Bulk action remarks..." style="width: 200px;">
                        <button type="submit" id="btnExecuteBulk" class="btn btn-primary btn-sm" disabled>
                            <i class="bi bi-play-fill me-1"></i>Execute Bulk Action
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pipeline Candidates Active Grid / Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-people-fill text-primary me-2"></i>Candidates in Pipeline
                        <span class="badge bg-light text-dark border ms-2"><?= count($candidates) ?> Records</span>
                    </h6>
                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>Showing latest 100 matched records
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($candidates)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3 text-secondary opacity-50"></i>
                            <h5>No candidates found in current pipeline filter</h5>
                            <p class="small mb-3">Adjust your filter criteria or source fresh candidates from the Algorithmic Matching Engine.</p>
                            <a href="<?= app_url('/views/recruiter/candidate_matching.php') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-cpu me-1"></i>Run Candidate Matching
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-muted">
                                    <tr>
                                        <th style="width: 4%;" class="text-center">
                                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                        </th>
                                        <th style="width: 24%;">Candidate &amp; Trade</th>
                                        <th style="width: 20%;">Requisition &amp; Industry</th>
                                        <th style="width: 12%;">Match Score</th>
                                        <th style="width: 14%;">Pipeline Stage</th>
                                        <th style="width: 12%;">Stage Milestones</th>
                                        <th style="width: 14%;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidates as $cand): ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="selected_matches[]" value="<?= (int)$cand['id'] ?>" class="form-check-input match-checkbox">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($cand['candidate_name']) ?></div>
                                                <div class="small text-muted">
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($cand['trade']) ?></span>
                                                    <span class="ms-1">Batch <?= htmlspecialchars((string)($cand['passing_year'] ?? 'N/A')) ?></span>
                                                </div>
                                                <div style="font-size: 0.72rem;" class="text-secondary">
                                                    Ref: #<?= (int)($cand['candidate_source_id'] ?? $cand['id']) ?> | <?= htmlspecialchars($cand['candidate_source']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold small text-primary"><?= htmlspecialchars($cand['req_code']) ?></div>
                                                <div class="small text-dark text-truncate" style="max-width: 240px;"><?= htmlspecialchars($cand['req_title']) ?></div>
                                                <div class="small text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($cand['company_name']) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="h6 mb-0 fw-bold me-2"><?= number_format((float)$cand['match_score'], 1) ?>%</div>
                                                    <?php
                                                        $scoreVal = (float)$cand['match_score'];
                                                        $progressColor = $scoreVal >= 85 ? 'bg-success' : ($scoreVal >= 70 ? 'bg-primary' : ($scoreVal >= 50 ? 'bg-warning' : 'bg-danger'));
                                                    ?>
                                                    <div class="progress flex-grow-1" style="height: 6px; width: 50px;">
                                                        <div class="progress-bar <?= $progressColor ?>" style="width: <?= min(100, (int)$scoreVal) ?>%"></div>
                                                    </div>
                                                </div>
                                                <div style="font-size: 0.7rem;" class="text-muted mt-1">
                                                    <?= htmlspecialchars($cand['eligibility_status'] ?? 'ELIGIBLE') ?> &bull; <?= (float)($cand['trade_score'] ?? 0) ?> pts trade
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= getStageBadgeClass($cand['status']) ?> py-1 px-2 rounded-pill">
                                                    <?= htmlspecialchars($cand['status']) ?>
                                                </span>
                                                <div class="mt-1" style="font-size: 0.7rem;">
                                                    <?php if (!empty($cand['is_shared_with_industry'])): ?>
                                                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Shared with Employer</span>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="bi bi-lock-fill me-1"></i>Internal Desk Only</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <!-- Milestone status detail -->
                                                <?php if ($cand['status'] === 'Scheduled' && !empty($cand['interview_datetime'])): ?>
                                                    <div class="small fw-semibold text-warning">
                                                        <i class="bi bi-calendar-event me-1"></i><?= date('d M, H:i', strtotime($cand['interview_datetime'])) ?>
                                                    </div>
                                                    <div style="font-size: 0.7rem;" class="text-muted text-truncate" style="max-width: 140px;">
                                                        <?= htmlspecialchars($cand['venue_or_link'] ?? '') ?>
                                                    </div>
                                                <?php elseif ($cand['status'] === 'Completed' || $cand['status'] === 'Selected'): ?>
                                                    <div class="small fw-semibold text-success">
                                                        <i class="bi bi-check2-circle me-1"></i>Cleared Interview
                                                    </div>
                                                    <?php if (!empty($cand['offered_designation'])): ?>
                                                        <div style="font-size: 0.7rem;" class="text-muted"><?= htmlspecialchars($cand['offered_designation']) ?></div>
                                                    <?php endif; ?>
                                                <?php elseif ($cand['status'] === 'Offer Released' || $cand['status'] === 'Offer Accepted'): ?>
                                                    <div class="small fw-semibold" style="color: #20c997;">
                                                        <i class="bi bi-file-earmark-check me-1"></i><?= htmlspecialchars($cand['letter_reference_no'] ?? 'Offer Released') ?>
                                                    </div>
                                                    <div style="font-size: 0.7rem;" class="text-muted">₹ <?= number_format((float)($cand['offered_ctc'] ?? 0)) ?>/mo</div>
                                                <?php elseif ($cand['status'] === 'Joined'): ?>
                                                    <div class="small fw-semibold text-success">
                                                        <i class="bi bi-person-check-fill me-1"></i>Joined: <?= date('d M Y', strtotime($cand['actual_joining_date'] ?? 'now')) ?>
                                                    </div>
                                                    <div style="font-size: 0.7rem;" class="text-muted">Onboarding Confirmed</div>
                                                <?php else: ?>
                                                    <div class="small text-muted">&mdash;</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Action Triggers based on stage -->
                                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <!-- Audit Timeline -->
                                                        <li>
                                                            <a class="dropdown-item btn-view-timeline" href="#" data-match-id="<?= (int)$cand['id'] ?>" data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>">
                                                                <i class="bi bi-clock-history text-primary me-2"></i>View Timeline History
                                                            </a>
                                                        </li>

                                                        <!-- Stage 3: Screening -->
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#screeningModal" 
                                                               data-match-id="<?= (int)$cand['id'] ?>" 
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>"
                                                               data-trade="<?= htmlspecialchars($cand['trade']) ?>">
                                                                <i class="bi bi-telephone-outbound text-primary me-2"></i>Record Screening
                                                            </a>
                                                        </li>

                                                        <!-- Stage 4: Schedule Interview -->
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#scheduleInterviewModal"
                                                               data-match-id="<?= (int)$cand['id'] ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>"
                                                               data-company-name="<?= htmlspecialchars($cand['company_name']) ?>">
                                                                <i class="bi bi-calendar-plus text-warning me-2"></i>Schedule Interview
                                                            </a>
                                                        </li>

                                                        <!-- Stage 5: Interview Feedback (if interview scheduled) -->
                                                        <?php if (!empty($cand['interview_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#interviewFeedbackModal"
                                                               data-interview-id="<?= (int)$cand['interview_id'] ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>">
                                                                <i class="bi bi-star-half text-info me-2"></i>Submit Interview Feedback
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>

                                                        <!-- Stage 7: Selection Decision -->
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#selectionModal"
                                                               data-match-id="<?= (int)$cand['id'] ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>"
                                                               data-trade="<?= htmlspecialchars($cand['trade']) ?>">
                                                                <i class="bi bi-award text-success me-2"></i>Selection Decision
                                                            </a>
                                                        </li>

                                                        <!-- Stage 8: Release Offer (if selected) -->
                                                        <?php if (!empty($cand['selection_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#offerReleaseModal"
                                                               data-selection-id="<?= (int)$cand['selection_id'] ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>"
                                                               data-desig="<?= htmlspecialchars($cand['offered_designation'] ?? '') ?>"
                                                               data-ctc="<?= htmlspecialchars((string)($cand['offered_ctc'] ?? '')) ?>">
                                                                <i class="bi bi-file-earmark-text text-teal me-2"></i>Release Offer Letter
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>

                                                        <!-- Printable Offer Letter (if offer released) -->
                                                        <?php if (!empty($cand['offer_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= app_url('/views/recruiter/offer_letter_view.php?offer_id=' . (int)$cand['offer_id']) ?>" target="_blank">
                                                                <i class="bi bi-file-earmark-pdf me-2" style="color: #20c997;"></i>View / Print Offer Letter
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>

                                                        <!-- Stage 9: Candidate Offer Response (if offer released) -->
                                                        <?php if (!empty($cand['offer_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#offerResponseModal"
                                                               data-offer-id="<?= (int)$cand['offer_id'] ?>"
                                                               data-ref-no="<?= htmlspecialchars($cand['letter_reference_no'] ?? '') ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>">
                                                                <i class="bi bi-envelope-check text-primary me-2"></i>Record Offer Response
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>

                                                        <!-- Stage 10: Confirm Physical Joining (if selected / offer accepted) -->
                                                        <?php if (!empty($cand['selection_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#confirmJoiningModal"
                                                               data-selection-id="<?= (int)$cand['selection_id'] ?>"
                                                               data-candidate-name="<?= htmlspecialchars($cand['candidate_name']) ?>"
                                                               data-company-name="<?= htmlspecialchars($cand['company_name']) ?>">
                                                                <i class="bi bi-person-check text-success me-2"></i>Confirm Physical Joining
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>

                                                        <li><hr class="dropdown-divider"></li>

                                                        <!-- Toggle Sharing with Industry -->
                                                        <li>
                                                            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>" class="px-2 py-1">
                                                                <?= renderCSRFField() ?>
                                                                <input type="hidden" name="action" value="toggle_sharing">
                                                                <input type="hidden" name="match_id" value="<?= (int)$cand['id'] ?>">
                                                                <input type="hidden" name="share_with_industry" value="<?= empty($cand['is_shared_with_industry']) ? 1 : 0 ?>">
                                                                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 w-100 text-start text-dark">
                                                                    <i class="bi bi-share text-secondary me-2"></i>
                                                                    <?= empty($cand['is_shared_with_industry']) ? 'Share with Employer' : 'Unshare from Employer' ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
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
        </form>

    </main>
</div>

<!-- =========================================================================== -->
<!-- MODAL 1: CANDIDATE SCREENING ASSESSMENT (STAGE 3) -->
<!-- =========================================================================== -->
<div class="modal fade" id="screeningModal" tabindex="-1" aria-labelledby="screeningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="record_screening">
                <input type="hidden" name="match_id" id="screeningMatchId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="screeningModalLabel">
                        <i class="bi bi-telephone-outbound text-primary me-2"></i>Record Candidate Screening
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate:</small>
                        <strong id="screeningCandidateName" class="text-dark"></strong> (<span id="screeningCandidateTrade"></span>)
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Screening Outcome <span class="text-danger">*</span></label>
                        <select name="screening_outcome" class="form-select form-select-sm" required>
                            <option value="Passed">Passed (Advance to Screened)</option>
                            <option value="On Hold">On Hold (Pending Clarification)</option>
                            <option value="Needs Re-Screening">Needs Re-Screening</option>
                            <option value="Failed">Failed (Reject Candidate)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Willing to Relocate?</label>
                            <select name="willing_to_relocate" class="form-select form-select-sm">
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="Conditional">Conditional</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Availability</label>
                            <select name="candidate_availability" class="form-select form-select-sm">
                                <option value="Immediate">Immediate</option>
                                <option value="Within 7 Days">Within 7 Days</option>
                                <option value="Within 15 Days">Within 15 Days</option>
                                <option value="Within 30 Days">Within 30 Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Expected Salary / Stipend (₹/month)</label>
                        <input type="text" name="expected_salary" class="form-control form-control-sm" placeholder="e.g. 16500">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Telephonic Screening Notes</label>
                        <textarea name="screening_notes" class="form-control form-control-sm" rows="3" placeholder="Key observations on technical understanding, communication, shift flexibility..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Screening Assessment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 2: SCHEDULE INTERVIEW (STAGE 4) -->
<!-- =========================================================================== -->
<div class="modal fade" id="scheduleInterviewModal" tabindex="-1" aria-labelledby="scheduleInterviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="schedule_interview">
                <input type="hidden" name="match_id" id="interviewMatchId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="scheduleInterviewModalLabel">
                        <i class="bi bi-calendar-plus text-warning me-2"></i>Schedule Technical Interview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate &amp; Client:</small>
                        <strong id="interviewCandidateName" class="text-dark"></strong> &mdash; <span id="interviewCompanyName" class="text-primary"></span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="interview_datetime" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Interview Mode</label>
                            <select name="interview_mode" class="form-select form-select-sm">
                                <option value="In-Person">In-Person (Plant/Center)</option>
                                <option value="Online Video">Online Video (Google Meet / Zoom)</option>
                                <option value="Telephonic">Telephonic</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Interview Round</label>
                        <select name="interview_round" class="form-select form-select-sm">
                            <option value="Round 1 - Technical Assessment">Round 1 - Technical Assessment</option>
                            <option value="Round 2 - Plant Ground Practical">Round 2 - Plant Ground Practical</option>
                            <option value="Round 3 - HR & Fitment">Round 3 - HR & Fitment</option>
                            <option value="Final Comprehensive Drive">Final Comprehensive Drive</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Venue Address or Online Meeting Link <span class="text-danger">*</span></label>
                        <input type="text" name="venue_or_link" class="form-control form-control-sm" placeholder="Plant Gate No. 2, Chakan, Pune OR meet.google.com/xyz" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Interviewer / Evaluator Name</label>
                            <input type="text" name="interviewer_name" class="form-control form-control-sm" placeholder="e.g. Rajesh Patil (Plant Head)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Interviewer Designation</label>
                            <input type="text" name="interviewer_designation" class="form-control form-control-sm" placeholder="e.g. Senior Production Manager">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Interview Instructions / Agenda</label>
                        <textarea name="interview_remarks" class="form-control form-control-sm" rows="2" placeholder="Carry original ITI marksheet, safety boots required for shopfloor evaluation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold">Confirm &amp; Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 3: INTERVIEW EVALUATION FEEDBACK (STAGE 5 & 6) -->
<!-- =========================================================================== -->
<div class="modal fade" id="interviewFeedbackModal" tabindex="-1" aria-labelledby="interviewFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="record_feedback">
                <input type="hidden" name="interview_id" id="feedbackInterviewId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="interviewFeedbackModalLabel">
                        <i class="bi bi-star-half text-info me-2"></i>Record Interview Evaluation Feedback
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate Evaluation:</small>
                        <strong id="feedbackCandidateName" class="text-dark"></strong>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Technical Rating</label>
                            <select name="technical_rating" class="form-select form-select-sm">
                                <option value="5">5 - Excellent</option>
                                <option value="4" selected>4 - Very Good</option>
                                <option value="3">3 - Satisfactory</option>
                                <option value="2">2 - Marginal</option>
                                <option value="1">1 - Unsatisfactory</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Practical Skills</label>
                            <select name="practical_rating" class="form-select form-select-sm">
                                <option value="5">5 - Excellent</option>
                                <option value="4" selected>4 - Very Good</option>
                                <option value="3">3 - Satisfactory</option>
                                <option value="2">2 - Marginal</option>
                                <option value="1">1 - Unsatisfactory</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Trade Knowledge</label>
                            <select name="trade_knowledge_rating" class="form-select form-select-sm">
                                <option value="5">5 - Excellent</option>
                                <option value="4" selected>4 - Very Good</option>
                                <option value="3">3 - Satisfactory</option>
                                <option value="2">2 - Marginal</option>
                                <option value="1">1 - Unsatisfactory</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Communication</label>
                            <select name="communication_rating" class="form-select form-select-sm">
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Good</option>
                                <option value="3" selected>3 - Acceptable</option>
                                <option value="2">2 - Poor</option>
                                <option value="1">1 - Inadequate</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Industrial Safety</label>
                            <select name="safety_rating" class="form-select form-select-sm">
                                <option value="5">5 - Excellent</option>
                                <option value="4" selected>4 - Good</option>
                                <option value="3">3 - Standard</option>
                                <option value="2">2 - Careless</option>
                                <option value="1">1 - Hazardous</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold">Overall Rating</label>
                            <select name="overall_rating" class="form-select form-select-sm">
                                <option value="5">5 / 5</option>
                                <option value="4" selected>4 / 5</option>
                                <option value="3">3 / 5</option>
                                <option value="2">2 / 5</option>
                                <option value="1">1 / 5</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Final Recommendation <span class="text-danger">*</span></label>
                        <select name="recommendation" class="form-select form-select-sm" required>
                            <option value="Strongly Recommended">Strongly Recommended (Clear to Select)</option>
                            <option value="Recommended" selected>Recommended</option>
                            <option value="Hold">Hold for Standby</option>
                            <option value="Not Recommended">Not Recommended (Reject)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Detailed Evaluator Comments</label>
                        <textarea name="feedback_comments" class="form-control form-control-sm" rows="3" placeholder="Solid grasp of blueprint reading, good welding machine setup speed, fit for production shopfloor."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info btn-sm text-white">Save Evaluation Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 4: CANDIDATE SELECTION DECISION (STAGE 7) -->
<!-- =========================================================================== -->
<div class="modal fade" id="selectionModal" tabindex="-1" aria-labelledby="selectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="record_selection">
                <input type="hidden" name="match_id" id="selectionMatchId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="selectionModalLabel">
                        <i class="bi bi-award text-success me-2"></i>Candidate Selection Decision
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate:</small>
                        <strong id="selectionCandidateName" class="text-dark"></strong> (<span id="selectionCandidateTrade"></span>)
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Selection Status <span class="text-danger">*</span></label>
                        <select name="selection_status" class="form-select form-select-sm" required>
                            <option value="Selected">Selected (Approved for Employment)</option>
                            <option value="On Hold">On Hold (Keep in Talent Reserve)</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Offered Designation</label>
                        <input type="text" name="offered_designation" class="form-control form-control-sm" placeholder="e.g. Junior CNC Operator / ITI Technician">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Offered Gross CTC (₹/Month)</label>
                            <input type="number" step="100" name="offered_ctc" class="form-control form-control-sm" placeholder="18500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Joining Date</label>
                            <input type="date" name="target_joining_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+10 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Decision Remarks / Justification</label>
                        <textarea name="decision_remarks" class="form-control form-control-sm" rows="2" placeholder="Candidate met all technical metrics in the practical drive; approved by plant manager."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm Official Selection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 5: RELEASE OFFER LETTER (STAGE 8) -->
<!-- =========================================================================== -->
<div class="modal fade" id="offerReleaseModal" tabindex="-1" aria-labelledby="offerReleaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="release_offer">
                <input type="hidden" name="selection_id" id="offerSelectionId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="offerReleaseModalLabel">
                        <i class="bi bi-file-earmark-text text-teal me-2"></i>Release Formal Offer Letter
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Selected Candidate:</small>
                        <strong id="offerCandidateName" class="text-dark"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirmed Job Designation <span class="text-danger">*</span></label>
                        <input type="text" name="job_designation" id="offerDesignation" class="form-control form-control-sm" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Monthly Salary / CTC (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="100" name="salary_offered" id="offerCtc" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Salary Type</label>
                            <select name="salary_type" class="form-select form-select-sm">
                                <option value="Monthly" selected>Monthly Gross</option>
                                <option value="Stipend">Apprenticeship Stipend</option>
                                <option value="Hourly">Hourly / Piece Rate</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Offer Validity Date</label>
                            <input type="date" name="validity_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Proposed Joining Date</label>
                            <input type="date" name="proposed_joining_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+12 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Plant / Work Location</label>
                        <input type="text" name="job_location" class="form-control form-control-sm" placeholder="Main Manufacturing Unit, Chakan MIDC, Pune">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Statutory &amp; Welfare Benefits Summary</label>
                        <input type="text" name="benefits_summary" class="form-control form-control-sm" value="PF, ESIC, Industrial Transport, Subsidized Canteen, PPE Kit">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Offer Remarks / Terms</label>
                        <textarea name="offer_remarks" class="form-control form-control-sm" rows="2" placeholder="Subject to clear physical medical fitness check on reporting day."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Issue &amp; Release Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 6: RECORD CANDIDATE OFFER RESPONSE (STAGE 9) -->
<!-- =========================================================================== -->
<div class="modal fade" id="offerResponseModal" tabindex="-1" aria-labelledby="offerResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="record_offer_response">
                <input type="hidden" name="offer_id" id="responseOfferId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="offerResponseModalLabel">
                        <i class="bi bi-envelope-check text-primary me-2"></i>Record Candidate Offer Response
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Offer Reference &amp; Candidate:</small>
                        <strong id="responseRefNo" class="text-primary"></strong> &mdash; <span id="responseCandidateName"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Candidate Decision <span class="text-danger">*</span></label>
                        <select name="offer_response" class="form-select form-select-sm" required>
                            <option value="Accepted">Accepted (Candidate signed and agreed to join)</option>
                            <option value="Declined">Declined (Candidate declined the offer)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Response Rationale / Notes</label>
                        <textarea name="response_notes" class="form-control form-control-sm" rows="3" placeholder="Confirmed telephonically and received signed acceptance via WhatsApp / Email."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Candidate Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 7: CONFIRM PHYSICAL JOINING (STAGE 10) -->
<!-- =========================================================================== -->
<div class="modal fade" id="confirmJoiningModal" tabindex="-1" aria-labelledby="confirmJoiningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="confirm_joining">
                <input type="hidden" name="selection_id" id="joiningSelectionId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="confirmJoiningModalLabel">
                        <i class="bi bi-person-check text-success me-2"></i>Confirm Ground Physical Joining
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate &amp; Plant:</small>
                        <strong id="joiningCandidateName" class="text-dark"></strong> &mdash; <span id="joiningCompanyName" class="text-primary"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reporting &amp; Joining Status <span class="text-danger">*</span></label>
                        <select name="joining_status" class="form-select form-select-sm" required>
                            <option value="Joined">Joined (Successfully reported and commenced shift)</option>
                            <option value="Joining Delayed">Joining Delayed (Candidate requested extension)</option>
                            <option value="No Show">No Show (Failed to report on scheduled date)</option>
                            <option value="Did Not Join">Did Not Join (Formally revoked / opted out)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Actual Reporting Date</label>
                            <input type="date" name="actual_joining_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Plant / Reporting Unit</label>
                            <input type="text" name="plant_reporting_unit" class="form-control form-control-sm" placeholder="Shopfloor Unit 2 / Toolroom">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Delay Rationale (if applicable)</label>
                        <input type="text" name="delay_reason" class="form-control form-control-sm" placeholder="ITI exam certificate delay / transit time extension...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Onboarding Remarks</label>
                        <textarea name="onboarding_remarks" class="form-control form-control-sm" rows="2" placeholder="ID badge issued, induction safety training completed on Day 1."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm Onboarding</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL 8: VISUAL 16-STAGE TIMELINE AUDIT TRAIL -->
<!-- =========================================================================== -->
<div class="modal fade" id="timelineModal" tabindex="-1" aria-labelledby="timelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="timelineModalLabel">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Candidate Recruitment Audit Trail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <h6 class="fw-bold mb-1" id="timelineModalCandidateName"></h6>
                    <small class="text-muted">Chronological transition history across 16 hiring lifecycle stages.</small>
                </div>
                <div id="timelineContainer">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading audit trail...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Checkbox selection and bulk action toolbar logic
    var selectAll = document.getElementById('selectAllCheckbox');
    var checkboxes = document.querySelectorAll('.match-checkbox');
    var btnExecuteBulk = document.getElementById('btnExecuteBulk');
    var countBadge = document.getElementById('selectedCountBadge');

    function updateBulkState() {
        var checkedCount = 0;
        checkboxes.forEach(function (cb) {
            if (cb.checked) checkedCount++;
        });
        if (countBadge) {
            countBadge.textContent = checkedCount + ' Selected';
        }
        if (btnExecuteBulk) {
            btnExecuteBulk.disabled = (checkedCount === 0);
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateBulkState();
            if (!this.checked && selectAll) {
                selectAll.checked = false;
            }
        });
    });

    // Form submission validation for bulk action
    var bulkForm = document.getElementById('bulkActionForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            var op = document.getElementById('bulkOperationSelect').value;
            if (!op) {
                e.preventDefault();
                alert('Please select a valid bulk operation to execute.');
                return;
            }
            if (!confirm('Are you sure you want to execute bulk action "' + op + '" on selected candidates?')) {
                e.preventDefault();
            }
        });
    }

    // Modal 1: Screening
    var screeningModal = document.getElementById('screeningModal');
    if (screeningModal) {
        screeningModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('screeningMatchId').value = button.getAttribute('data-match-id') || '';
            document.getElementById('screeningCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
            document.getElementById('screeningCandidateTrade').textContent = button.getAttribute('data-trade') || '';
        });
    }

    // Modal 2: Schedule Interview
    var interviewModal = document.getElementById('scheduleInterviewModal');
    if (interviewModal) {
        interviewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('interviewMatchId').value = button.getAttribute('data-match-id') || '';
            document.getElementById('interviewCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
            document.getElementById('interviewCompanyName').textContent = button.getAttribute('data-company-name') || '';
        });
    }

    // Modal 3: Interview Feedback
    var feedbackModal = document.getElementById('interviewFeedbackModal');
    if (feedbackModal) {
        feedbackModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('feedbackInterviewId').value = button.getAttribute('data-interview-id') || '';
            document.getElementById('feedbackCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
        });
    }

    // Modal 4: Selection Decision
    var selectionModal = document.getElementById('selectionModal');
    if (selectionModal) {
        selectionModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('selectionMatchId').value = button.getAttribute('data-match-id') || '';
            document.getElementById('selectionCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
            document.getElementById('selectionCandidateTrade').textContent = button.getAttribute('data-trade') || '';
        });
    }

    // Modal 5: Release Offer
    var offerModal = document.getElementById('offerReleaseModal');
    if (offerModal) {
        offerModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('offerSelectionId').value = button.getAttribute('data-selection-id') || '';
            document.getElementById('offerCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
            document.getElementById('offerDesignation').value = button.getAttribute('data-desig') || '';
            document.getElementById('offerCtc').value = button.getAttribute('data-ctc') || '';
        });
    }

    // Modal 6: Offer Response
    var responseModal = document.getElementById('offerResponseModal');
    if (responseModal) {
        responseModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('responseOfferId').value = button.getAttribute('data-offer-id') || '';
            document.getElementById('responseRefNo').textContent = button.getAttribute('data-ref-no') || '';
            document.getElementById('responseCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
        });
    }

    // Modal 7: Confirm Joining
    var joiningModal = document.getElementById('confirmJoiningModal');
    if (joiningModal) {
        joiningModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('joiningSelectionId').value = button.getAttribute('data-selection-id') || '';
            document.getElementById('joiningCandidateName').textContent = button.getAttribute('data-candidate-name') || '';
            document.getElementById('joiningCompanyName').textContent = button.getAttribute('data-company-name') || '';
        });
    }

    // Modal 8: Timeline Audit Trail Loader
    var timelineModalEl = document.getElementById('timelineModal');
    var timelineModal = new bootstrap.Modal(timelineModalEl);
    document.querySelectorAll('.btn-view-timeline').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var matchId = this.getAttribute('data-match-id');
            var candidateName = this.getAttribute('data-candidate-name');
            document.getElementById('timelineModalCandidateName').textContent = candidateName;
            var container = document.getElementById('timelineContainer');
            container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading audit trail...</div>';
            timelineModal.show();

            fetch('<?= app_url('/views/recruiter/recruitment_pipeline.php') ?>?ajax=timeline&match_id=' + matchId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success || !data.history || data.history.length === 0) {
                        container.innerHTML = '<div class="alert alert-light border text-center text-muted">No transition history logged for this candidate profile yet.</div>';
                        return;
                    }
                    var html = '<ul class="timeline-list list-unstyled position-relative ps-4" style="border-left: 2px solid #e2e8f0; margin-left: 10px;">';
                    data.history.forEach(function (h) {
                        html += '<li class="mb-4 position-relative">' +
                            '<span class="position-absolute translate-middle p-1 bg-primary border border-light rounded-circle" style="left: -25px; top: 8px;"></span>' +
                            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                                '<strong class="text-dark">' + (h.previous_stage ? h.previous_stage + ' &rarr; ' : '') + '<span class="text-primary">' + h.new_stage + '</span></strong>' +
                                '<small class="text-muted">' + h.created_at + '</small>' +
                            '</div>' +
                            '<div class="small text-secondary mb-1">' + (h.notes ? h.notes : 'Stage updated.') + '</div>' +
                            '<div style="font-size: 0.72rem;" class="text-muted"><i class="bi bi-person me-1"></i>Changed by: ' + (h.changed_by_name || 'System / Automated') + '</div>' +
                        '</li>';
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                })
                .catch(function () {
                    container.innerHTML = '<div class="alert alert-danger">Error retrieving timeline history.</div>';
                });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
