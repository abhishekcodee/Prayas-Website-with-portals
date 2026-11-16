<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once "../db.php";

$pageTitle = "Dashboard";


/*
|--------------------------------------------------------------------------
| DASHBOARD DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$totalCandidates     = 0;
$profilesCompleted   = 0;
$aptitudeCompleted   = 0;

$shortlistedCount    = 0;
$selectedCount       = 0;
$rejectedCount       = 0;

$recentCandidates    = [];


/*
|--------------------------------------------------------------------------
| FETCH DASHBOARD DATA
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | 1. TOTAL CANDIDATES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

    $totalCandidates = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | 2. COMPLETED PROFILES
    |--------------------------------------------------------------------------
    |
    | profile_confirmed = 1 means candidate confirmed/completed profile.
    |
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE profile_confirmed = 1
    ");

    $profilesCompleted = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | 3. APTITUDE COMPLETED
    |--------------------------------------------------------------------------
    |
    | Aptitude module abhi build nahi hua hai.
    | Isliye temporarily 0.
    |
    */

    $aptitudeCompleted = 0;


    /*
    |--------------------------------------------------------------------------
    | 4. SHORTLISTED CANDIDATES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'Shortlisted'
    ");

    $shortlistedCount = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | 5. SELECTED CANDIDATES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'Selected'
    ");

    $selectedCount = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | 6. REJECTED CANDIDATES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'Rejected'
    ");

    $rejectedCount = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | 7. RECENT REGISTRATIONS
    |--------------------------------------------------------------------------
    |
    | Latest 5 candidates dashboard par show honge.
    |
    */

    $stmt = $pdo->query("
        SELECT
            id,
            registration_no,
            full_name,
            status,
            created_at

        FROM users

        ORDER BY created_at DESC, id DESC

        LIMIT 5
    ");

    $recentCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    /*
     * Development ke time error dekhne ke liye.
     * Production me actual DB error user ko show nahi karna.
     */

    die(
        "Dashboard database error: "
        . htmlspecialchars($e->getMessage())
    );
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function dashboardEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| STATUS CSS CLASS
|--------------------------------------------------------------------------
*/

function dashboardStatusClass($status)
{
    $status = strtolower(
        trim((string) $status)
    );

    switch ($status) {

        case 'selected':
            return 'status-selected';

        case 'shortlisted':
            return 'status-shortlisted';

        case 'rejected':
            return 'status-rejected';

        case 'active':
            return 'status-active';

        case 'blocked':
            return 'status-blocked';

        case 'under review':
            return 'status-review';

        default:
            return 'status-pending';
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle); ?> | PRAYAS Admin
    </title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include "includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "includes/header.php"; ?>

        <main class="admin-content">

            <!-- Welcome -->

            <section class="welcome-section">

                <div class="welcome-content">

                    <span class="section-label">
                        RECRUITMENT OVERVIEW
                    </span>

                    <h2>
                        Welcome back,
                        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>
                    </h2>

                    <p>
                        Monitor candidates, assessments and recruitment
                        activity from one place.
                    </p>

                </div>

                <a href="candidates/index.php"
                   class="primary-btn">

                    <span>View Candidates</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>

                </a>

            </section>


            <!-- Statistics -->

            <section class="stats-grid">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon-wrapper stat-icon-coral">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>

                        <span class="stat-badge badge-coral">
                            All
                        </span>

                    </div>

                    <h3 class="stat-number" data-target="<?= $totalCandidates; ?>">
                        <?= number_format($totalCandidates); ?>
                    </h3>

                    <p>Total Candidates</p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon-wrapper stat-icon-emerald">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>

                        <span class="stat-badge badge-emerald">
                            Profile
                        </span>

                    </div>

                    <h3 class="stat-number" data-target="<?= $profilesCompleted; ?>">
                        <?= number_format($profilesCompleted); ?>
                    </h3>

                    <p>Profiles Completed</p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon-wrapper stat-icon-amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>

                        <span class="stat-badge badge-amber">
                            Test
                        </span>

                    </div>

                    <h3 class="stat-number" data-target="<?= $aptitudeCompleted; ?>">
                        <?= number_format($aptitudeCompleted); ?>
                    </h3>

                    <p>Aptitude Completed</p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon-wrapper stat-icon-indigo">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                        </div>

                        <span class="stat-badge badge-indigo">
                            Hiring
                        </span>

                    </div>

                    <h3 class="stat-number" data-target="<?= $selectedCount; ?>">
                        <?= number_format($selectedCount); ?>
                    </h3>

                    <p>Selected Candidates</p>

                </div>

            </section>


            <!-- Dashboard Grid -->

            <section class="dashboard-grid">

                <!-- Recent Candidates -->

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                CANDIDATES
                            </span>

                            <h3>
                                Recent Registrations
                            </h3>

                        </div>

                        <a href="candidates/index.php"
                           class="view-link">

                            <span>View All</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>

                        </a>

                    </div>


                    <div class="table-responsive">

                        <table class="admin-table">

                            <thead>

                            <tr>

                                <th>Candidate</th>

                                <th>Registration ID</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                            </thead>

                            <tbody>

<?php if (!empty($recentCandidates)): ?>

    <?php foreach ($recentCandidates as $candidate): ?>

        <?php

        $candidateStatus =
            $candidate['status']
            ?? 'Pending';

        $registrationNo =
            $candidate['registration_no']
            ?: 'Not Assigned';

        ?>

        <tr>

            <!-- Candidate -->

            <td>

                <div class="candidate-cell">

                    <div class="candidate-avatar">

                        <?= strtoupper(
                            substr(
                                trim(
                                    $candidate['full_name']
                                    ?? 'C'
                                ),
                                0,
                                1
                            )
                        ); ?>

                    </div>

                    <div>

                        <strong>

                            <?= dashboardEscape(
                                $candidate['full_name']
                                ?? 'Unknown Candidate'
                            ); ?>

                        </strong>

                    </div>

                </div>

            </td>


            <!-- Registration ID -->

            <td>

                <span class="reg-id-badge">
                    <?= dashboardEscape(
                        $registrationNo
                    ); ?>
                </span>

            </td>


            <!-- Status -->

            <td>

                <span class="candidate-status
                    <?= dashboardStatusClass(
                        $candidateStatus
                    ); ?>">

                    <span class="status-dot"></span>
                    <?= dashboardEscape(
                        $candidateStatus
                    ); ?>

                </span>

            </td>


            <!-- Action -->

            <td>

                <a
                    href="candidates/view.php?id=<?= (int) $candidate['id']; ?>"
                    class="table-action-btn"
                >

                    View

                </a>

            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>

        <td
            colspan="4"
            class="empty-state"
        >

            No candidates registered yet.

        </td>

    </tr>

<?php endif; ?>

</tbody>

                        </table>

                    </div>

                </div>


                <!-- Recruitment Pipeline -->

                <div class="panel pipeline-panel">

                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                PIPELINE
                            </span>

                            <h3>
                                Recruitment Status
                            </h3>

                        </div>

                    </div>


                    <div class="pipeline-list">

                        <?php 
                            $maxCount = max(1, $totalCandidates);
                            $regPercent = round(($totalCandidates / $maxCount) * 100);
                            $shortPercent = $totalCandidates > 0 ? round(($shortlistedCount / $totalCandidates) * 100) : 0;
                            $selPercent = $totalCandidates > 0 ? round(($selectedCount / $totalCandidates) * 100) : 0;
                            $rejPercent = $totalCandidates > 0 ? round(($rejectedCount / $totalCandidates) * 100) : 0;
                        ?>

                        <div class="pipeline-item">

                            <div class="pipeline-item-top">

                                <div>

                                    <strong>Registered</strong>

                                    <span>
                                        Total candidates
                                    </span>

                                </div>

                                <b class="pipeline-count" data-target="<?= $totalCandidates; ?>"><?= number_format($totalCandidates); ?></b>

                            </div>

                            <div class="pipeline-bar-wrapper">

                                <div class="pipeline-bar-fill fill-registered" style="width: 0%;" data-percentage="<?= $totalCandidates > 0 ? 100 : 0; ?>"></div>

                            </div>

                        </div>


                        <div class="pipeline-item">

                            <div class="pipeline-item-top">

                                <div>

                                    <strong>Shortlisted</strong>

                                    <span>
                                        Under consideration
                                    </span>

                                </div>

                                <b class="pipeline-count" data-target="<?= $shortlistedCount; ?>"><?= number_format($shortlistedCount); ?></b>

                            </div>

                            <div class="pipeline-bar-wrapper">

                                <div class="pipeline-bar-fill fill-shortlisted" style="width: 0%;" data-percentage="<?= $shortPercent; ?>"></div>

                            </div>

                        </div>


                        <div class="pipeline-item">

                            <div class="pipeline-item-top">

                                <div>

                                    <strong>Selected</strong>

                                    <span>
                                        Successfully selected
                                    </span>

                                </div>

                                <b class="pipeline-count" data-target="<?= $selectedCount; ?>"><?= number_format($selectedCount); ?></b>

                            </div>

                            <div class="pipeline-bar-wrapper">

                                <div class="pipeline-bar-fill fill-selected" style="width: 0%;" data-percentage="<?= $selPercent; ?>"></div>

                            </div>

                        </div>


                        <div class="pipeline-item">

                            <div class="pipeline-item-top">

                                <div>

                                    <strong>Rejected</strong>

                                    <span>
                                        Not selected
                                    </span>

                                </div>

                                <b class="pipeline-count" data-target="<?= $rejectedCount; ?>"><?= number_format($rejectedCount); ?></b>

                            </div>

                            <div class="pipeline-bar-wrapper">

                                <div class="pipeline-bar-fill fill-rejected" style="width: 0%;" data-percentage="<?= $rejPercent; ?>"></div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </main>

    </div>

</div>

<script src="assets/js/admin.js"></script>

</body>

</html>