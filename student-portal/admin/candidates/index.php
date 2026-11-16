<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../db.php";

$pageTitle = "Candidates";

/* =========================
   SEARCH
========================= */

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        u.id,
        u.registration_no,
        u.full_name,
        u.email,
        u.mobile,
        u.status,
        u.current_step,
        u.registration_completed,
        u.profile_confirmed,

        sp.photo,

        CASE
            WHEN EXISTS (
                SELECT 1
                FROM video_upload vu
                WHERE vu.user_id = u.id
            )
            THEN 1
            ELSE 0
        END AS video_uploaded

    FROM users u

    LEFT JOIN student_profile sp
        ON sp.user_id = u.id

    WHERE 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            u.full_name LIKE :search
            OR u.registration_no LIKE :search
            OR u.email LIKE :search
            OR u.mobile LIKE :search
        )
    ";

    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY u.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Candidates | PRAYAS Admin
</title>

<link rel="stylesheet"
      href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include "../includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "../includes/header.php"; ?>

        <main class="admin-content">

            <!-- PAGE INTRO -->

            <section class="candidate-page-head">

                <div>

                    <span class="section-label">
                        RECRUITMENT
                    </span>

                    <h2>Candidate Management</h2>

                    <p>
                        View and manage all registered candidates
                        from one place.
                    </p>

                </div>

                <div class="candidate-total">

                    <span>Total Candidates</span>

                    <strong>
                        <?= count($candidates); ?>
                    </strong>

                </div>

            </section>


            <!-- SEARCH / FILTER -->

            <section class="candidate-toolbar">

                <form method="GET"
                      class="candidate-search-form">

                    <div class="search-box">

                        <span>⌕</span>

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search); ?>"
                            placeholder="Search name, registration ID, email or mobile">

                    </div>

                    <button
                        type="submit"
                        class="primary-btn">

                        Search

                    </button>

                    <?php if ($search !== ''): ?>

                        <a
                            href="index.php"
                            class="reset-btn">

                            Reset

                        </a>

                    <?php endif; ?>

                </form>

            </section>


            <!-- CANDIDATE TABLE -->

            <section class="panel candidate-panel">

                <div class="panel-header">

                    <div>

                        <span class="section-label">
                            DATABASE
                        </span>

                        <h3>
                            Registered Candidates
                        </h3>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="admin-table candidate-table">

                        <thead>

                        <tr>

                            <th>Candidate</th>

                            <th>Contact</th>

                            <th>Profile</th>

                            <th>Aptitude</th>

                            <th>Video</th>

                            <th>Current Step</th>

                            <th>Action</th>

                        </tr>

                        </thead>


                        <tbody>

                        <?php if (!empty($candidates)): ?>

                            <?php foreach ($candidates as $candidate): ?>

                                <?php

                                $fullName = trim($candidate['full_name'] ?? '');

                                if ($fullName === '') {
                                $fullName = 'Candidate';
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | PHOTO
                                |--------------------------------------------------------------------------
                                */

                                $photoUrl = null;

                                if (!empty($candidate['photo'])) {

                                    $photoFile =
                                        "../../uploads/photos/" .
                                        basename($candidate['photo']);

                                    if (file_exists($photoFile)) {

                                        $photoUrl =
                                            "../../uploads/photos/" .
                                            rawurlencode(
                                                basename(
                                                    $candidate['photo']
                                                )
                                            );

                                    }

                                }

                                ?>

                                <tr>

                                    <!-- Candidate -->

                                    <td>

                                        <div class="candidate-info">

                                            <?php if ($photoUrl): ?>

                                                <img
                                                    src="<?= htmlspecialchars($photoUrl); ?>"
                                                    class="candidate-avatar-img"
                                                    alt="Candidate">

                                            <?php else: ?>

                                                <div class="candidate-avatar-placeholder">

                                                    <?= strtoupper(
                                                        substr(
                                                            $fullName,
                                                            0,
                                                            1
                                                        )
                                                    ); ?>

                                                </div>

                                            <?php endif; ?>


                                            <div>

                                                <strong>
                                                    <?= htmlspecialchars($fullName); ?>
                                                </strong>

                                                <span>
                                                    ID:
                                                    <?= htmlspecialchars(
                                                $candidate['registration_no']
                                                ?? ('USR-' . $candidate['id'])
                                            ); ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Contact -->

                                    <td>

                                        <div class="candidate-contact">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $candidate['email']
                                                    ?? '-'
                                                ); ?>
                                            </strong>

                                            <span>
                                                <?= htmlspecialchars(
                                                    $candidate['mobile']
                                                    ?? '-'
                                                ); ?>
                                            </span>

                                        </div>

                                    </td>


                                    <!-- Profile -->

                                    <td>

                                        <?php if (!empty(
                                            $candidate['profile_confirmed']
                                        )): ?>

                                            <span class="status-badge success">
                                                Completed
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge warning">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Aptitude -->

                                 <!-- Aptitude -->

                                    <td>

                                    <span class="status-badge neutral">
                                    Not Available
                                    </span>

                                </td>


                                    <!-- Video -->

                                    <td>

                                        <?php if (!empty(
                                            $candidate['video_uploaded']
                                        )): ?>

                                            <span class="status-badge success">
                                                Uploaded
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge neutral">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Current Step -->

                                    <td>

                                        <span class="step-badge">

                                            Step
                                            <?= htmlspecialchars(
                                                $candidate['current_step']
                                                ?? '-'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- Action -->

                                    <td>

                                        <a
                                            href="view.php?id=<?= (int)$candidate['id']; ?>"
                                            class="view-btn">

                                            View

                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-state">

                                    <?php if ($search !== ''): ?>

                                        No candidate found for
                                        "<strong><?= htmlspecialchars($search); ?></strong>"

                                    <?php else: ?>

                                        No candidates registered yet.

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>

</div>

<script src="../assets/js/admin.js"></script>

</body>

</html>