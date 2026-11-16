<?php
/**
 * Industry Candidate Matches & Sourcing Pipeline Explorer
 * 
 * Project: Industry Manpower Requirement Portal
 * Organization: PRAYAS – The Automation Academy
 * Technology: Core PHP + Bootstrap 5 + PRAYAS Design System
 */

declare(strict_types=1);

$pageTitle = 'Candidate Matches — PRAYAS Industry Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../controllers/IndustryController.php';

requireRole(['industry_admin', 'industry_user']);

$companyId = getCompanyId();
if (!$companyId) {
    header("Location: " . app_url('/views/auth/login.php'));
    exit;
}

$industryCtrl = new IndustryController();

$page = max(1, (int)($_GET['page'] ?? 1));
$reqFilter = !empty($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : 0;
$tradeFilter = trim((string)($_GET['trade'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$scoreCategory = trim((string)($_GET['score_category'] ?? ''));
$searchKeyword = trim((string)($_GET['search'] ?? ''));

$filters = [
    'requirement_id' => $reqFilter,
    'trade'          => $tradeFilter,
    'status'         => $statusFilter,
    'search'         => $searchKeyword
];

if (!empty($scoreCategory)) {
    if ($scoreCategory === 'excellent') {
        $filters['min_score'] = '90.0';
    } elseif ($scoreCategory === 'strong') {
        $filters['min_score'] = '75.0';
    } elseif ($scoreCategory === 'possible') {
        $filters['min_score'] = '60.0';
    }
}

$candidatesData = $industryCtrl->getCandidateMatches($companyId, $filters, $page, 15);
$reqs = $industryCtrl->getMyRequirements($companyId, [], 1, 100);

// Aggregate KPI counts for company matches
$pdo = Database::getConnection();
$kpiStmt = $pdo->prepare("SELECT 
        COUNT(*) as total_matches,
        SUM(CASE WHEN cm.match_score >= 90.0 THEN 1 ELSE 0 END) as excellent_count,
        SUM(CASE WHEN cm.match_score >= 75.0 AND cm.match_score < 90.0 THEN 1 ELSE 0 END) as strong_count,
        SUM(CASE WHEN cm.match_score >= 60.0 AND cm.match_score < 75.0 THEN 1 ELSE 0 END) as possible_count,
        SUM(CASE WHEN cm.status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted_count,
        SUM(CASE WHEN cm.status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_count
    FROM `candidate_matches` cm
    JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
    WHERE mr.company_id = :cid AND (cm.is_shared_with_industry = 1 OR cm.status != 'Matched')");
$kpiStmt->execute([':cid' => $companyId]);
$kpi = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>

<div id="main-content" class="flex-grow-1">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="page-container">
        <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>Candidate Matches & Sourcing Pipeline</h4>
                <p class="text-muted small mb-0">Review qualified ITI candidates matched to your approved manpower requirements by PRAYAS sourcing algorithms.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= app_url('/views/industry/requirements.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-briefcase me-1"></i>My Requisitions
                </a>
                <a href="<?= app_url('/views/industry/dashboard.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <!-- Privacy Assurance Banner (DPDP Compliant) -->
        <div class="alert alert-light border shadow-sm py-2 px-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="small">
                <i class="bi bi-shield-lock-fill text-success me-2 fs-6"></i>
                <strong>Privacy-Preserving Candidate Profile:</strong> Candidate credentials and test scores are verified and anonymized in strict compliance with DPDP data minimization guidelines. Direct student outreach is facilitated exclusively through PRAYAS scheduled interview rounds.
            </div>
            <span class="badge bg-success-subtle text-success border border-success">DPDP & Fair Sourcing Compliant</span>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Total Matched</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= (int)($kpi['total_matches'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Verified Profiles</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-success border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-success">Excellent Match</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= (int)($kpi['excellent_count'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Score &ge; 90%</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-primary border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-primary">Strong Match</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= (int)($kpi['strong_count'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Score 75% &ndash; 89%</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-warning border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-warning-emphasis">Possible Match</span>
                    <h3 class="fw-bold text-warning mb-0 mt-1"><?= (int)($kpi['possible_count'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Score 60% &ndash; 74%</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-info border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-info">Shortlisted</span>
                    <h3 class="fw-bold text-info mb-0 mt-1"><?= (int)($kpi['shortlisted_count'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Ready for Rounds</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 border-top border-secondary border-3">
                    <span class="text-muted small text-uppercase fw-semibold text-secondary">Scheduled</span>
                    <h3 class="fw-bold text-secondary mb-0 mt-1"><?= (int)($kpi['scheduled_count'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Plant Walk-ins</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= app_url('/views/industry/candidate_matches.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="requirement_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Requisitions</option>
                            <?php foreach ($reqs['data'] as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $reqFilter === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['requirement_id']) ?> &mdash; <?= htmlspecialchars($r['title']) ?> (<?= (int)$r['quantity_required'] ?> Vacancies)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="trade" class="form-control form-control-sm" placeholder="Filter by Trade (e.g. Fitter, Electrician)" value="<?= htmlspecialchars($tradeFilter) ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Pipeline Stages</option>
                            <option value="Matched" <?= $statusFilter === 'Matched' ? 'selected' : '' ?>>Matched</option>
                            <option value="Shortlisted" <?= $statusFilter === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                            <option value="Scheduled" <?= $statusFilter === 'Scheduled' ? 'selected' : '' ?>>Interview Scheduled</option>
                            <option value="Selected" <?= $statusFilter === 'Selected' ? 'selected' : '' ?>>Selected</option>
                            <option value="Joined" <?= $statusFilter === 'Joined' ? 'selected' : '' ?>>Joined</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="score_category" class="form-select form-select-sm">
                            <option value="">All Match Categories</option>
                            <option value="excellent" <?= $scoreCategory === 'excellent' ? 'selected' : '' ?>>Excellent Match (&ge; 90%)</option>
                            <option value="strong" <?= $scoreCategory === 'strong' ? 'selected' : '' ?>>Strong Match (75% &ndash; 89%)</option>
                            <option value="possible" <?= $scoreCategory === 'possible' ? 'selected' : '' ?>>Possible Match (60% &ndash; 74%)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="<?= app_url('/views/industry/candidate_matches.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Matches Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Candidate Ref</th>
                                <th>Target Trade & Batch</th>
                                <th>Requisition Code</th>
                                <th>Match Score & Tier</th>
                                <th>Pipeline Stage</th>
                                <th>Matched On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($candidatesData['data'])): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6 class="fw-bold">No Candidate Matches Found</h6>
                                        <p class="small text-muted mb-0">As PRAYAS sourcing algorithms scan accredited ITI student batches against your active requisitions, matched candidates will appear here automatically.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidatesData['data'] as $c): ?>
                                    <?php
                                    $score = (float)$c['match_score'];
                                    $category = match (true) {
                                        $score >= 90.0 => 'Excellent Match',
                                        $score >= 75.0 => 'Strong Match',
                                        $score >= 60.0 => 'Possible Match',
                                        default        => 'Low Match'
                                    };
                                    $catBadge = match ($category) {
                                        'Excellent Match' => 'bg-success',
                                        'Strong Match'    => 'bg-primary',
                                        'Possible Match'  => 'bg-warning text-dark',
                                        default           => 'bg-secondary'
                                    };
                                    $stageBadge = match ($c['status']) {
                                        'Joined'      => 'bg-success',
                                        'Selected'    => 'bg-warning text-dark',
                                        'Scheduled'   => 'bg-info text-dark',
                                        'Shortlisted' => 'bg-primary',
                                        default       => 'bg-secondary'
                                    };
                                    // Generate compliant anonymized reference
                                    $candRef = 'PRAYAS-ITI-' . str_pad((string)($c['candidate_source_id'] ?? $c['id']), 4, '0', STR_PAD_LEFT);
                                    $details = !empty($c['match_details_json']) ? json_decode($c['match_details_json'], true) : [];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-primary"><?= $candRef ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($c['candidate_name'] ?: 'Verified Trainee') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($c['trade']) ?></div>
                                            <small class="text-muted">Batch: <?= htmlspecialchars($c['passing_year'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= app_url('/views/industry/requirement_details.php?id=' . $c['requirement_id']) ?>" class="font-monospace small fw-bold text-decoration-none">
                                                <?= htmlspecialchars($c['requirement_code']) ?>
                                            </a>
                                            <div class="text-muted small text-truncate" style="max-width: 200px;"><?= htmlspecialchars($c['requirement_title']) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge <?= $catBadge ?> px-2 py-1"><?= number_format($score, 1) ?>%</span>
                                                <div class="progress" style="width: 50px; height: 5px;">
                                                    <div class="progress-bar <?= $score >= 75 ? 'bg-success' : ($score >= 60 ? 'bg-warning' : 'bg-secondary') ?>" style="width: <?= min(100, $score) ?>%"></div>
                                                </div>
                                            </div>
                                            <small class="badge bg-light text-dark border" style="font-size: 0.68rem;"><?= $category ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $stageBadge ?>"><?= htmlspecialchars($c['status']) ?></span>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars(substr((string)$c['matched_at'], 0, 10)) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.78rem;" 
                                                    data-bs-toggle="modal" data-bs-target="#matchModal_<?= $c['id'] ?>">
                                                <i class="bi bi-info-circle me-1"></i>Match Breakdown
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Explanation Modal for Candidate -->
                                    <div class="modal fade" id="matchModal_<?= $c['id'] ?>" tabindex="-1" aria-labelledby="modalLabel_<?= $c['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-light">
                                                    <div>
                                                        <h6 class="modal-title fw-bold" id="modalLabel_<?= $c['id'] ?>">
                                                            <i class="bi bi-cpu text-primary me-2"></i>Matching Explanation: <?= $candRef ?>
                                                        </h6>
                                                        <small class="text-muted">Requisition: <?= htmlspecialchars($c['requirement_code']) ?> &bull; <?= htmlspecialchars($c['requirement_title']) ?></small>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <!-- Score Summary Banner -->
                                                    <div class="card border-0 bg-light p-3 mb-4 rounded">
                                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                            <div>
                                                                <span class="text-muted small text-uppercase fw-semibold">Overall Match Score</span>
                                                                <h2 class="fw-bold mb-0 text-dark"><?= number_format($score, 1) ?> <span class="fs-6 text-muted">/ 100.0</span></h2>
                                                            </div>
                                                            <div class="text-end">
                                                                <span class="badge <?= $catBadge ?> fs-6 mb-1"><?= $category ?></span>
                                                                <div class="text-muted small">Status: <strong><?= htmlspecialchars($c['eligibility_status'] ?? 'ELIGIBLE') ?></strong></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- 7 Dimensions Breakdown -->
                                                    <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>7-Dimension Algorithmic Evaluation</h6>
                                                    <div class="list-group list-group-flush border rounded mb-4">
                                                        <!-- 1. Trade Alignment -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-tools text-primary me-2"></i>1. Technical Trade Alignment</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['trade_score'] ?? 0), 1) ?> / 30.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['trade']['notes'] ?? 'Exact trade match against requirement specifications') ?></small>
                                                        </div>

                                                        <!-- 2. Qualification Tier -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-mortarboard text-primary me-2"></i>2. Qualification Tier</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['qual_score'] ?? 0), 1) ?> / 15.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['qual']['notes'] ?? 'ITI Passed certificate tier') ?></small>
                                                        </div>

                                                        <!-- 3. Passing Batch Window -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-calendar-check text-primary me-2"></i>3. Passing Batch Window</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['batch_score'] ?? 0), 1) ?> / 15.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['batch']['notes'] ?? 'Candidate batch within requested graduation window') ?></small>
                                                        </div>

                                                        <!-- 4. Skills Match -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-cpu text-primary me-2"></i>4. Technical Skills Alignment</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['skills_score'] ?? 0), 1) ?> / 20.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['skills']['notes'] ?? 'Technical alignment based on coursework and tool familiarity') ?></small>
                                                        </div>

                                                        <!-- 5. Location Proximity -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-geo-alt text-primary me-2"></i>5. Location & Zone Proximity</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['location_score'] ?? 0), 1) ?> / 10.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['location']['notes'] ?? 'Maharashtra industrial corridor residency') ?></small>
                                                        </div>

                                                        <!-- 6. Experience Tier -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-briefcase text-primary me-2"></i>6. Experience Tier</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['exp_score'] ?? 0), 1) ?> / 5.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['experience']['notes'] ?? 'Meets experience criteria') ?></small>
                                                        </div>

                                                        <!-- 7. Availability -->
                                                        <div class="list-group-item p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-semibold text-dark"><i class="bi bi-clock-history text-primary me-2"></i>7. Joining Availability</span>
                                                                <span class="fw-bold font-monospace text-primary"><?= number_format((float)($c['avail_score'] ?? 0), 1) ?> / 5.0 pts</span>
                                                            </div>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($details['availability']['notes'] ?? 'Standard active candidate availability') ?></small>
                                                        </div>
                                                    </div>

                                                    <!-- Privacy & Interview Coordination Notice -->
                                                    <div class="alert alert-info py-2 px-3 small rounded mb-0">
                                                        <i class="bi bi-info-circle-fill me-1"></i>
                                                        Candidate contact details and physical credentials are held by PRAYAS Academic Recruiter Desk. When you schedule an interview, PRAYAS coordinates batch transport and verification directly.
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($candidatesData['total_pages'] > 1): ?>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing page <?= $candidatesData['page'] ?> of <?= $candidatesData['total_pages'] ?> (Total <?= $candidatesData['total'] ?> candidates)</small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $candidatesData['total_pages']; $i++): ?>
                                    <li class="page-item <?= $i === $candidatesData['page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&requirement_id=<?= $reqFilter ?>&trade=<?= urlencode($tradeFilter) ?>&status=<?= urlencode($statusFilter) ?>&score_category=<?= urlencode($scoreCategory) ?>&search=<?= urlencode($searchKeyword) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
