<?php
/**
 * Industry Recruitment Pipeline Dashboard & Candidate Tracking
 * Organization: PRAYAS – The Automation Academy
 * Module: Part 10 Recruitment Pipeline Management System
 */

declare(strict_types=1);

$pageTitle = 'Industry Recruitment Pipeline — PRAYAS Portal';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../controllers/PipelineController.php';

requireRole(['industry_admin', 'industry_user', 'company_admin', 'super_admin']);

$pdo = Database::getConnection();
$pipelineCtrl = new PipelineController($pdo);
$userId = getUserId();
$userRole = getUserRole();
$companyId = getCompanyId();

if (!$companyId && $userRole === 'super_admin' && !empty($_GET['company_id'])) {
    $companyId = (int)$_GET['company_id'];
}

if (!$companyId) {
    echo "<div class='container p-5 text-center'><h4>Error: No company affiliated with this account. Please contact PRAYAS administration.</h4></div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// -----------------------------------------------------------------------------
// AJAX TIMELINE FETCHER (IDOR & TENANT PROTECTED)
// -----------------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'timeline' && !empty($_GET['match_id'])) {
    $matchId = (int)$_GET['match_id'];
    $stmt = $pdo->prepare("SELECT cm.id, mr.company_id 
        FROM `candidate_matches` cm 
        JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
        WHERE cm.id = :id LIMIT 1");
    $stmt->execute([':id' => $matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match || ($userRole !== 'super_admin' && (int)$match['company_id'] !== $companyId)) {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid record.']);
        exit;
    }

    $history = $pipelineCtrl->getStageHistory($matchId);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

// -----------------------------------------------------------------------------
// POST REQUEST PROCESSING (EVALUATIONS & JOININGS)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'record_feedback') {
        $interviewId = (int)($_POST['interview_id'] ?? 0);
        // Tenant verification
        $checkStmt = $pdo->prepare("SELECT mr.company_id FROM `interviews` i 
            JOIN `candidate_matches` cm ON cm.id = i.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE i.id = :id LIMIT 1");
        $checkStmt->execute([':id' => $interviewId]);
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($userRole === 'super_admin' || (int)$row['company_id'] === $companyId)) {
            $res = $pipelineCtrl->recordInterviewFeedback($interviewId, $_POST, $userId);
            if ($res['success']) {
                $_SESSION['flash_success'] = $res['message'];
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Unauthorized interview action.';
        }
    } elseif ($action === 'confirm_joining') {
        $selectionId = (int)($_POST['selection_id'] ?? 0);
        // Tenant verification
        $checkStmt = $pdo->prepare("SELECT mr.company_id FROM `selections` s 
            JOIN `candidate_matches` cm ON cm.id = s.match_id 
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id 
            WHERE s.id = :id LIMIT 1");
        $checkStmt->execute([':id' => $selectionId]);
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($userRole === 'super_admin' || (int)$row['company_id'] === $companyId)) {
            $res = $pipelineCtrl->confirmJoining($selectionId, $_POST, $userId);
            if ($res['success']) {
                $_SESSION['flash_success'] = $res['message'];
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Unauthorized joining confirmation action.';
        }
    }

    $redirectQuery = http_build_query($_GET);
    header("Location: " . app_url('/views/industry/recruitment_pipeline.php') . ($redirectQuery ? '?' . $redirectQuery : ''));
    exit;
}

// -----------------------------------------------------------------------------
// DATA FETCHING & FILTER SETUP
// -----------------------------------------------------------------------------
$reqFilter    = !empty($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : null;
$stageFilter  = !empty($_GET['stage']) ? trim((string)$_GET['stage']) : null;
$tradeFilter  = !empty($_GET['trade']) ? trim((string)$_GET['trade']) : null;
$searchFilter = !empty($_GET['search']) ? trim((string)$_GET['search']) : null;

// Dashboard Stats & Funnel
$dashStats = $pipelineCtrl->getIndustryDashboardStats($companyId);
$kpis = $dashStats['kpis'] ?? [];
$funnel = $dashStats['funnel'] ?? [];
$upcomingInterviews = $dashStats['upcoming_interviews'] ?? [];
$tradeProgress = $dashStats['trade_progress'] ?? [];
$plantProgress = $dashStats['plant_progress'] ?? [];
$alerts = $dashStats['alerts'] ?? [];

// Requisitions dropdown scoped to company
$reqStmt = $pdo->prepare("SELECT id, requirement_id, title, quantity_required FROM `manpower_requirements` WHERE company_id = :cid ORDER BY id DESC");
$reqStmt->execute([':cid' => $companyId]);
$companyReqs = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals for Vacancies & Fulfillments
$totalVacancies = 0;
foreach ($companyReqs as $cr) {
    $totalVacancies += (int)$cr['quantity_required'];
}
$positionsFulfilled = (int)($kpis['candidates_joined'] ?? 0);

// Fetch Candidates list with privacy protections
$candSql = "SELECT cm.id, cm.requirement_id, cm.match_score, cm.trade_score, cm.status, 
                   cm.trade, cm.passing_year, cm.eligibility_status, cm.matched_at,
                   mr.requirement_id as req_code, mr.title as req_title,
                   p.plant_name, p.plant_code,
                   i.id as interview_id, i.interview_datetime, i.interview_mode, i.venue_or_link, i.status as interview_status,
                   s.id as selection_id, s.offered_designation, s.offered_ctc,
                   ol.id as offer_id, ol.letter_reference_no, ol.status as offer_status, ol.validity_date,
                   j.id as joining_id, j.actual_joining_date, j.joining_status
            FROM `candidate_matches` cm
            JOIN `manpower_requirements` mr ON mr.id = cm.requirement_id
            LEFT JOIN `manufacturing_plants` p ON p.id = mr.plant_id
            LEFT JOIN `interviews` i ON i.match_id = cm.id AND i.id = (SELECT MAX(id) FROM `interviews` WHERE match_id = cm.id)
            LEFT JOIN `selections` s ON s.match_id = cm.id
            LEFT JOIN `offer_letters` ol ON ol.selection_id = s.id
            LEFT JOIN `joinings` j ON j.selection_id = s.id
            WHERE mr.company_id = :cid AND (cm.is_shared_with_industry = 1 OR cm.status != 'Matched')";

$candParams = [':cid' => $companyId];

if ($reqFilter) {
    $candSql .= " AND cm.requirement_id = :rid";
    $candParams[':rid'] = $reqFilter;
}
if ($stageFilter) {
    $candSql .= " AND cm.status = :stg";
    $candParams[':stg'] = $stageFilter;
}
if ($tradeFilter) {
    $candSql .= " AND cm.trade = :trd";
    $candParams[':trd'] = $tradeFilter;
}
if ($searchFilter) {
    $candSql .= " AND (cm.trade LIKE :s1 OR mr.requirement_id LIKE :s2 OR mr.title LIKE :s3)";
    $candParams[':s1'] = "%{$searchFilter}%";
    $candParams[':s2'] = "%{$searchFilter}%";
    $candParams[':s3'] = "%{$searchFilter}%";
}

$candSql .= " ORDER BY cm.id DESC LIMIT 100";
$candStmt = $pdo->prepare($candSql);
$candStmt->execute($candParams);
$candidates = $candStmt->fetchAll(PDO::FETCH_ASSOC);

function getIndustryStageBadgeClass(string $stage): string {
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

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <!-- Enterprise Page Header -->
        <div class="card-enterprise mb-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #ffffff; border: none;">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge" style="background-color: #EF6B6B; color: #ffffff; font-weight: 700; letter-spacing: 0.5px; margin-right: 12px; font-size: 0.75rem;">
                                RECRUITMENT DESK 2.0
                            </span>
                            <span class="text-white-50 small">PRAYAS Industry Partnership</span>
                        </div>
                        <h2 class="h3 fw-bold mb-1 text-white">Recruitment Pipeline &amp; Candidate Dashboard</h2>
                        <p class="text-white-50 mb-0 small">
                            Real-time tracking of vetted ITI candidates across 16 hiring stages, interview drives, formal offer dispatches, and factory onboarding confirmations.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-outline-light btn-sm me-2">
                            <i class="bi bi-briefcase me-1"></i>My Requisitions
                        </a>
                        <a href="<?= app_url('/views/industry/dashboard.php') ?>" class="btn btn-sm text-white" style="background-color: #EF6B6B;">
                            <i class="bi bi-speedometer2 me-1"></i>Industry Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECRUITMENT ALERTS (IF ANY) -->
        <?php if (!empty($alerts)): ?>
            <div class="mb-4">
                <?php foreach ($alerts as $alt): ?>
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-2" role="alert">
                        <i class="bi <?= htmlspecialchars($alt['icon']) ?> fs-4 me-3 text-warning"></i>
                        <div class="flex-grow-1">
                            <strong><?= htmlspecialchars($alt['title']) ?>:</strong> <?= htmlspecialchars($alt['message']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 14 DATABASE-BACKED KPI CARDS -->
        <div class="row g-3 mb-4">
            <!-- Row 1: Sourcing & Pre-Interview KPIs -->
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-secondary">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">1. Matched Pool</div>
                    <div class="h4 fw-bold mb-0 text-dark"><?= (int)($kpis['total_matched_candidates'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Algorithmically Matched</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-info">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">2. Shortlisted</div>
                    <div class="h4 fw-bold mb-0 text-info"><?= (int)($kpis['total_shortlisted_candidates'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Approved for Review</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-primary">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">3. Screened</div>
                    <div class="h4 fw-bold mb-0 text-primary"><?= (int)($kpis['candidates_under_screening'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Telephonic &amp; Mobility</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-warning">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">4. Scheduled</div>
                    <div class="h4 fw-bold mb-0 text-warning"><?= (int)($kpis['interviews_scheduled'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Upcoming Rounds</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4" style="border-left-color: #6f42c1 !important;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">5. Completed</div>
                    <div class="h4 fw-bold mb-0" style="color: #6f42c1;"><?= (int)($kpis['interviews_completed'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Evaluated Rounds</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-success">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">6. Selected</div>
                    <div class="h4 fw-bold mb-0 text-success"><?= (int)($kpis['candidates_selected'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Passed Evaluation</small>
                </div>
            </div>

            <!-- Row 2: Offers & Onboarding KPIs -->
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4" style="border-left-color: #20c997 !important;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">7. Offers Released</div>
                    <div class="h4 fw-bold mb-0 text-dark"><?= (int)($kpis['offers_released'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Formal Letter Issued</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-success">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">8. Offers Accepted</div>
                    <div class="h4 fw-bold mb-0 text-success"><?= (int)($kpis['offers_accepted'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Candidate Consented</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-danger">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">9. Offers Declined</div>
                    <div class="h4 fw-bold mb-0 text-danger"><?= (int)($funnel['offer_declined'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Candidate Opted Out</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-warning">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">10. Joining Pending</div>
                    <div class="h4 fw-bold mb-0 text-warning"><?= (int)($kpis['candidates_awaiting_joining'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Transit to Factory</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-success">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">11. Joined &amp; Onboarded</div>
                    <div class="h4 fw-bold mb-0 text-success"><?= (int)($kpis['candidates_joined'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Commenced Shift</small>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card-enterprise p-3 text-center h-100 border-start border-4 border-dark">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">12. Dropouts / No Show</div>
                    <div class="h4 fw-bold mb-0 text-dark"><?= (int)($funnel['no_show'] ?? 0) ?></div>
                    <small class="text-muted" style="font-size: 0.65rem;">Failed to Report</small>
                </div>
            </div>

            <!-- Row 3: Rejections & Total Fulfillment Metric -->
            <div class="col-12 col-md-6">
                <div class="card-enterprise p-3 d-flex align-items-center justify-content-between h-100 border-start border-4 border-danger">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">13. Total Pipeline Rejections</div>
                        <div class="h3 fw-bold mb-0 text-danger"><?= (int)($kpis['candidates_rejected'] ?? 0) ?></div>
                        <small class="text-muted">Screening / Technical / Practical Disqualifications</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-danger border px-3 py-2">
                            <i class="bi bi-x-circle me-1"></i><?= (int)($kpis['candidates_withdrawn'] ?? 0) ?> Withdrawn
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card-enterprise p-3 d-flex align-items-center justify-content-between h-100 border-start border-4" style="border-left-color: #EF6B6B !important;">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">14. Positions Fulfilled vs Open Vacancies</div>
                        <div class="h3 fw-bold mb-0" style="color: #0F172A;">
                            <?= $positionsFulfilled ?> <span class="fs-6 text-muted fw-normal">/ <?= $totalVacancies ?> Vacancies Demanded</span>
                        </div>
                        <?php 
                            $overallPct = $totalVacancies > 0 ? min(100, round(($positionsFulfilled / $totalVacancies) * 100, 1)) : 0;
                        ?>
                        <div class="progress mt-2" style="height: 6px; width: 220px;">
                            <div class="progress-bar" style="background-color: #EF6B6B; width: <?= (int)$overallPct ?>%"></div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="h4 fw-bold mb-0" style="color: #EF6B6B;"><?= $overallPct ?>%</div>
                        <small class="text-muted">Plant Fulfillment Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 16-STAGE RECRUITMENT FUNNEL VISUAL PROGRESS -->
        <div class="card-enterprise mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-funnel-fill me-2" style="color: #EF6B6B;"></i>16-Stage Recruitment Funnel Progression
                </h6>
                <span class="badge bg-light text-secondary border">Real-Time Sync</span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex flex-wrap gap-2 text-center">
                    <?php 
                        $stageSteps = [
                            'Matched'         => ['lbl' => '1. Matched', 'cnt' => $funnel['total_sourced'] ?? 0, 'bg' => 'bg-secondary text-white'],
                            'Shortlisted'     => ['lbl' => '2. Shortlisted', 'cnt' => $funnel['shortlisted'] ?? 0, 'bg' => 'bg-info text-dark'],
                            'Screened'        => ['lbl' => '3. Screened', 'cnt' => $funnel['screened'] ?? 0, 'bg' => 'bg-primary text-white'],
                            'Scheduled'       => ['lbl' => '4. Scheduled', 'cnt' => $funnel['scheduled'] ?? 0, 'bg' => 'bg-warning text-dark'],
                            'Completed'       => ['lbl' => '5. Completed', 'cnt' => $funnel['interviewed'] ?? 0, 'bg' => 'bg-indigo text-white', 'style' => 'background:#6f42c1;color:#fff;'],
                            'Selected'        => ['lbl' => '6. Selected', 'cnt' => $funnel['selected'] ?? 0, 'bg' => 'bg-success text-white'],
                            'Offer Released'  => ['lbl' => '7. Offer Rel.', 'cnt' => $funnel['offers_released'] ?? 0, 'bg' => 'bg-teal text-white', 'style' => 'background:#20c997;color:#fff;'],
                            'Offer Accepted'  => ['lbl' => '8. Offer Acc.', 'cnt' => $funnel['offers_accepted'] ?? 0, 'bg' => 'bg-success text-white'],
                            'Offer Declined'  => ['lbl' => '9. Declined', 'cnt' => $funnel['offer_declined'] ?? 0, 'bg' => 'bg-danger text-white'],
                            'Joining Pending' => ['lbl' => '10. J-Pending', 'cnt' => $funnel['joining_pending'] ?? 0, 'bg' => 'bg-warning text-dark'],
                            'Joined'          => ['lbl' => '11. Joined', 'cnt' => $funnel['joined'] ?? 0, 'bg' => 'bg-success text-white'],
                            'Rejected'        => ['lbl' => '12. Rejected', 'cnt' => $funnel['rejected'] ?? 0, 'bg' => 'bg-danger text-white'],
                            'On Hold'         => ['lbl' => '13. On Hold', 'cnt' => $funnel['on_hold'] ?? 0, 'bg' => 'bg-dark text-white'],
                            'Withdrawn'       => ['lbl' => '14. Withdrawn', 'cnt' => $funnel['withdrawn'] ?? 0, 'bg' => 'bg-secondary text-white'],
                            'No Show'         => ['lbl' => '15. No Show', 'cnt' => $funnel['no_show'] ?? 0, 'bg' => 'bg-danger text-white'],
                            'Closed'          => ['lbl' => '16. Closed', 'cnt' => $funnel['closed'] ?? 0, 'bg' => 'bg-dark text-white']
                        ];
                    ?>
                    <?php foreach ($stageSteps as $stKey => $stData): ?>
                        <div class="flex-grow-1 p-2 border rounded" style="min-width: 105px; background: #f8fafc;">
                            <div class="badge <?= $stData['bg'] ?> mb-1 w-100" style="<?= $stData['style'] ?? '' ?>"><?= htmlspecialchars($stData['lbl']) ?></div>
                            <div class="h5 fw-bold mb-0 text-dark"><?= (int)$stData['cnt'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- UPCOMING ACTIVITIES & PROGRESS ROW -->
        <div class="row g-4 mb-4">
            <!-- Upcoming Interview Drives -->
            <div class="col-lg-6">
                <div class="card-enterprise h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-calendar-event me-2 text-warning"></i>Upcoming Interviews (Next 7 Days)
                        </h6>
                        <span class="badge bg-warning text-dark"><?= count($upcomingInterviews) ?> Scheduled</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($upcomingInterviews)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-calendar-check display-6 d-block mb-2 text-secondary opacity-50"></i>
                                <p class="small mb-0">No upcoming interviews scheduled for your plants in the next 7 days.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingInterviews as $ui): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <i class="bi bi-person-badge me-1 text-primary"></i>PRAYAS-ITI-<?= str_pad((string)$ui['id'], 4, '0', STR_PAD_LEFT) ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($ui['trade']) ?></span>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <span><i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($ui['interview_datetime'])) ?></span> &bull;
                                                <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ui['venue_or_link']) ?></span>
                                            </div>
                                            <div style="font-size: 0.72rem;" class="text-secondary mt-1">
                                                Req: <?= htmlspecialchars($ui['req_code']) ?> &bull; <?= htmlspecialchars($ui['interview_round']) ?> (<?= htmlspecialchars($ui['interview_mode']) ?>)
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#interviewFeedbackModal" data-interview-id="<?= (int)$ui['id'] ?>" data-candidate-code="PRAYAS-ITI-<?= str_pad((string)$ui['id'], 4, '0', STR_PAD_LEFT) ?>">
                                            <i class="bi bi-star-half me-1"></i>Feedback
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Plant & Trade-Wise Progress -->
            <div class="col-lg-6">
                <div class="card-enterprise h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-buildings me-2 text-primary"></i>Plant &amp; Trade Fulfillment Progress
                        </h6>
                        <span class="badge bg-light text-dark border">By Manufacturing Unit</span>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="small fw-bold text-uppercase text-muted mb-2">Manufacturing Plants</h6>
                        <?php if (empty($plantProgress)): ?>
                            <p class="text-muted small">No plant progress records available.</p>
                        <?php else: ?>
                            <div class="mb-4">
                                <?php foreach ($plantProgress as $pl): ?>
                                    <?php 
                                        $pDem = (int)$pl['vacancies_demanded'];
                                        $pJoi = (int)$pl['candidates_joined'];
                                        $pPct = $pDem > 0 ? min(100, round(($pJoi / $pDem) * 100)) : 0;
                                    ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between small">
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($pl['plant_name']) ?> (<?= htmlspecialchars($pl['plant_code']) ?>)</span>
                                            <span class="text-muted"><?= $pJoi ?> / <?= $pDem ?> joined (<?= $pPct ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?= $pPct ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="small fw-bold text-uppercase text-muted mb-2">Trade-Wise Distribution</h6>
                        <?php if (empty($tradeProgress)): ?>
                            <p class="text-muted small mb-0">No active trades in requisition demand.</p>
                        <?php else: ?>
                            <div>
                                <?php foreach ($tradeProgress as $tp): ?>
                                    <?php 
                                        $tDem = (int)$tp['vacancies_demanded'];
                                        $tJoi = (int)$tp['candidates_joined'];
                                        $tPct = $tDem > 0 ? min(100, round(($tJoi / $tDem) * 100)) : 0;
                                    ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between small">
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($tp['trade_name']) ?></span>
                                            <span class="text-muted"><?= $tJoi ?> / <?= $tDem ?> (<?= $tPct ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" style="background-color: #EF6B6B; width: <?= $tPct ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- MULTI-FACTOR FILTER BAR -->
        <div class="card-enterprise mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/industry/recruitment_pipeline.php') ?>" class="row g-2 align-items-center">
                    <?php if ($userRole === 'super_admin'): ?>
                        <input type="hidden" name="company_id" value="<?= $companyId ?>">
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Target Requisition</label>
                        <select name="requirement_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Active Requisitions</option>
                            <?php foreach ($companyReqs as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $reqFilter === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['requirement_id']) ?> &mdash; <?= htmlspecialchars($r['title']) ?> (<?= (int)$r['quantity_required'] ?> Pos)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Pipeline Stage</label>
                        <select name="stage" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All 16 Stages</option>
                            <?php foreach (PipelineController::STAGES as $stageKey => $stageLabel): ?>
                                <option value="<?= htmlspecialchars($stageLabel) ?>" <?= $stageFilter === $stageLabel ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stageLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Trade Discipline</label>
                        <input type="text" name="trade" class="form-control form-control-sm" placeholder="e.g. Electrician, Fitter" value="<?= htmlspecialchars($tradeFilter ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Search Keywords</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Trade, Requisition Code..." value="<?= htmlspecialchars($searchFilter ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex gap-1 align-self-end">
                        <button type="submit" class="btn btn-sm flex-grow-1 text-white" style="background-color: #EF6B6B;">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="<?= app_url('/views/industry/recruitment_pipeline.php') ?><?= ($userRole === 'super_admin') ? '?company_id=' . $companyId : '' ?>" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- CANDIDATES IN PIPELINE TABLE -->
        <div class="card-enterprise">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-people-fill me-2" style="color: #EF6B6B;"></i>Matched Candidates in Active Pipeline
                    <span class="badge bg-light text-dark border ms-2"><?= count($candidates) ?> Profiles</span>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted"><i class="bi bi-shield-check me-1 text-success"></i>DPDP Compliant PII Masking Active</small>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($candidates)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-inbox display-4 d-block mb-3 text-secondary opacity-50"></i>
                        <h5>No candidates found in current recruitment stage filter</h5>
                        <p class="small mb-0">Adjust your filter options or contact PRAYAS recruitment desk to expedite candidate sourcing.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-prayas">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th style="width: 24%;">Candidate ID &amp; Trade</th>
                                    <th style="width: 22%;">Requisition &amp; Plant</th>
                                    <th style="width: 14%;">Match Score</th>
                                    <th style="width: 14%;">Pipeline Stage</th>
                                    <th style="width: 14%;">Key Milestones</th>
                                    <th style="width: 12%;" class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $cand): ?>
                                    <?php 
                                        $maskedCode = 'PRAYAS-ITI-' . str_pad((string)$cand['id'], 4, '0', STR_PAD_LEFT);
                                        $scoreVal = (float)$cand['match_score'];
                                        $barColor = $scoreVal >= 85 ? 'bg-success' : ($scoreVal >= 70 ? 'bg-primary' : ($scoreVal >= 50 ? 'bg-warning' : 'bg-danger'));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                <i class="bi bi-person-badge-fill me-1 text-secondary"></i><?= htmlspecialchars($maskedCode) ?>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($cand['trade']) ?></span>
                                                <span class="ms-1">Batch <?= htmlspecialchars((string)($cand['passing_year'] ?? 'N/A')) ?></span>
                                            </div>
                                            <div style="font-size: 0.7rem;" class="text-secondary mt-1">
                                                Sourced: <?= date('d M Y', strtotime($cand['matched_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold small text-primary"><?= htmlspecialchars($cand['req_code']) ?></div>
                                            <div class="small text-dark text-truncate" style="max-width: 220px;"><?= htmlspecialchars($cand['req_title']) ?></div>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($cand['plant_name'] ?? 'Main Facility') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="h6 mb-0 fw-bold me-2"><?= number_format($scoreVal, 1) ?>%</div>
                                                <div class="progress flex-grow-1" style="height: 6px; width: 45px;">
                                                    <div class="progress-bar <?= $barColor ?>" style="width: <?= min(100, (int)$scoreVal) ?>%"></div>
                                                </div>
                                            </div>
                                            <div style="font-size: 0.68rem;" class="text-muted mt-1">
                                                <?= htmlspecialchars($cand['eligibility_status'] ?? 'ELIGIBLE') ?> &bull; <?= (float)($cand['trade_score'] ?? 0) ?> pts trade
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= getIndustryStageBadgeClass($cand['status']) ?> py-1 px-2 rounded-pill">
                                                <?= htmlspecialchars($cand['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($cand['status'] === 'Scheduled' && !empty($cand['interview_datetime'])): ?>
                                                <div class="small fw-semibold text-warning">
                                                    <i class="bi bi-calendar-event me-1"></i><?= date('d M, H:i', strtotime($cand['interview_datetime'])) ?>
                                                </div>
                                                <div style="font-size: 0.7rem;" class="text-muted text-truncate" style="max-width: 140px;">
                                                    <?= htmlspecialchars($cand['venue_or_link'] ?? '') ?>
                                                </div>
                                            <?php elseif ($cand['status'] === 'Completed' || $cand['status'] === 'Selected'): ?>
                                                <div class="small fw-semibold text-success">
                                                    <i class="bi bi-check2-circle me-1"></i>Cleared Evaluation
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
                                                <div style="font-size: 0.7rem;" class="text-muted">Commenced Shift</div>
                                            <?php else: ?>
                                                <div class="small text-muted">&mdash;</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Action
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <!-- Action 1: View Audit Timeline -->
                                                    <li>
                                                        <a class="dropdown-item btn-view-timeline" href="#" data-match-id="<?= (int)$cand['id'] ?>" data-candidate-code="<?= htmlspecialchars($maskedCode) ?>">
                                                            <i class="bi bi-clock-history me-2 text-primary"></i>View Timeline
                                                        </a>
                                                    </li>

                                                    <!-- Action 2: Submit Interview Feedback -->
                                                    <?php if (!empty($cand['interview_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#interviewFeedbackModal" data-interview-id="<?= (int)$cand['interview_id'] ?>" data-candidate-code="<?= htmlspecialchars($maskedCode) ?>">
                                                                <i class="bi bi-star-half me-2 text-warning"></i>Submit Feedback
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>

                                                    <!-- Action 3: View Formal Offer Letter -->
                                                    <?php if (!empty($cand['offer_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= app_url('/views/industry/offer_letter_view.php?offer_id=' . (int)$cand['offer_id']) ?>" target="_blank">
                                                                <i class="bi bi-file-earmark-pdf me-2" style="color: #20c997;"></i>View Offer Letter
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>

                                                    <!-- Action 4: Confirm Physical Joining -->
                                                    <?php if (!empty($cand['selection_id'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#confirmJoiningModal" data-selection-id="<?= (int)$cand['selection_id'] ?>" data-candidate-code="<?= htmlspecialchars($maskedCode) ?>">
                                                                <i class="bi bi-person-check me-2 text-success"></i>Confirm Plant Joining
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
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

    </main>
</div>

<!-- =========================================================================== -->
<!-- MODAL: INTERVIEW EVALUATION FEEDBACK -->
<!-- =========================================================================== -->
<div class="modal fade" id="interviewFeedbackModal" tabindex="-1" aria-labelledby="interviewFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/industry/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="record_feedback">
                <input type="hidden" name="interview_id" id="feedbackInterviewId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="interviewFeedbackModalLabel">
                        <i class="bi bi-star-half text-warning me-2"></i>Technical Evaluation Feedback
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate Profile Reference:</small>
                        <strong id="feedbackCandidateCode" class="text-dark"></strong>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Technical Knowledge (1-5)</label>
                            <select name="technical_rating" class="form-select form-select-sm" required>
                                <option value="5">5 - Outstanding</option>
                                <option value="4" selected>4 - Very Good</option>
                                <option value="3">3 - Competent</option>
                                <option value="2">2 - Needs Guidance</option>
                                <option value="1">1 - Inadequate</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Practical Skills (1-5)</label>
                            <select name="practical_rating" class="form-select form-select-sm" required>
                                <option value="5">5 - Excellent Machine Operation</option>
                                <option value="4" selected>4 - Good Hands-on Skill</option>
                                <option value="3">3 - Basic Familiarity</option>
                                <option value="2">2 - Poor Execution</option>
                                <option value="1">1 - Unable to Perform</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Safety Awareness (1-5)</label>
                            <select name="safety_rating" class="form-select form-select-sm">
                                <option value="5">5 - Rigorous PPE &amp; Hazard Protocols</option>
                                <option value="4" selected>4 - Standard Industrial Safety</option>
                                <option value="3">3 - Average</option>
                                <option value="2">2 - Careless</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Hiring Recommendation <span class="text-danger">*</span></label>
                            <select name="recommendation" class="form-select form-select-sm" required>
                                <option value="Strongly Recommend">Strongly Recommend</option>
                                <option value="Recommend" selected>Recommend for Hiring</option>
                                <option value="Hold for Comparison">Hold for Comparison</option>
                                <option value="Do Not Recommend">Do Not Recommend (Reject)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Evaluator Assessment Notes</label>
                        <textarea name="detailed_evaluation_notes" class="form-control form-control-sm" rows="3" placeholder="Candidate demonstrated good understanding of wiring schemas and multimeter diagnostics..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Evaluation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL: CONFIRM PLANT PHYSICAL JOINING -->
<!-- =========================================================================== -->
<div class="modal fade" id="confirmJoiningModal" tabindex="-1" aria-labelledby="confirmJoiningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= app_url('/views/industry/recruitment_pipeline.php') ?>">
                <?= renderCSRFField() ?>
                <input type="hidden" name="action" value="confirm_joining">
                <input type="hidden" name="selection_id" id="joiningSelectionId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="confirmJoiningModalLabel">
                        <i class="bi bi-person-check text-success me-2"></i>Confirm Factory Physical Joining
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 p-2">
                        <small class="text-muted d-block">Candidate Profile Reference:</small>
                        <strong id="joiningCandidateCode" class="text-dark"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reporting Status <span class="text-danger">*</span></label>
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
                            <input type="text" name="plant_reporting_unit" class="form-control form-control-sm" placeholder="e.g. Shopfloor Unit 2">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Onboarding Remarks</label>
                        <textarea name="onboarding_remarks" class="form-control form-control-sm" rows="2" placeholder="Plant ID badge issued, safety orientation briefing completed."></textarea>
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
<!-- MODAL: VISUAL 16-STAGE TIMELINE AUDIT TRAIL -->
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
                    <h6 class="fw-bold mb-1" id="timelineModalCandidateCode"></h6>
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
    // Feedback modal setup
    var feedbackModal = document.getElementById('interviewFeedbackModal');
    if (feedbackModal) {
        feedbackModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('feedbackInterviewId').value = button.getAttribute('data-interview-id') || '';
            document.getElementById('feedbackCandidateCode').textContent = button.getAttribute('data-candidate-code') || '';
        });
    }

    // Joining modal setup
    var joiningModal = document.getElementById('confirmJoiningModal');
    if (joiningModal) {
        joiningModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('joiningSelectionId').value = button.getAttribute('data-selection-id') || '';
            document.getElementById('joiningCandidateCode').textContent = button.getAttribute('data-candidate-code') || '';
        });
    }

    // Timeline modal AJAX loader
    var timelineModal = new bootstrap.Modal(document.getElementById('timelineModal'));
    document.querySelectorAll('.btn-view-timeline').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var matchId = this.getAttribute('data-match-id');
            var candidateCode = this.getAttribute('data-candidate-code');
            document.getElementById('timelineModalCandidateCode').textContent = candidateCode;
            var container = document.getElementById('timelineContainer');
            container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading audit trail...</div>';
            timelineModal.show();

            fetch('<?= app_url('/views/industry/recruitment_pipeline.php') ?>?ajax=timeline&match_id=' + matchId)
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
                .catch(function (err) {
                    container.innerHTML = '<div class="alert alert-danger">Error retrieving timeline history.</div>';
                });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
