<?php

session_start();

/* =========================================================
   ADMIN AUTH
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../db.php";

/* =========================================================
   ADMIN CANDIDATE REVIEW ACTION
========================================================= */

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_candidate_status'])) {

    $candidateId = filter_input(
        INPUT_POST,
        'candidate_id',
        FILTER_VALIDATE_INT
    );

    $newStatus = trim($_POST['status'] ?? '');
    $remarks   = trim($_POST['remarks'] ?? '');

    /*
     * IMPORTANT:
     * These values must match the allowed values
     * in users.status.
     */
    $allowedStatuses = [
        'Pending',
        'Active',
        'Blocked'
    ];

    if (!$candidateId) {

        $errorMessage = "Invalid candidate ID.";

    } elseif (!in_array(
        $newStatus,
        $allowedStatuses,
        true
    )) {

        $errorMessage = "Invalid candidate status.";

    } else {

        try {

            $reviewedBy =
                $_SESSION['admin_name']
                ?? $_SESSION['admin_email']
                ?? 'Administrator';

            $stmt = $pdo->prepare("
                UPDATE users

                SET
                    status = ?,
                    remarks = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW()

                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $remarks !== '' ? $remarks : null,
                $reviewedBy,
                $candidateId
            ]);

            header(
                "Location: view.php?id="
                . $candidateId
                . "&updated=1"
            );

            exit;

        } catch (PDOException $e) {

            $errorMessage =
                "Unable to update candidate: "
                . $e->getMessage();
        }
    }
}

if (isset($_GET['updated'])
    && $_GET['updated'] === '1') {

    $successMessage =
        "Candidate review updated successfully.";
}

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function showValue($value, $fallback = '-')
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    return e($value);
}

function formatDateValue($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '-';
    }

    return date('d M Y', $timestamp);
}

function formatMoney($amount)
{
    if ($amount === null || $amount === '') {
        return '-';
    }

    return '₹' . number_format((float)$amount, 0);
}

/* =========================================================
   CANDIDATE ID
========================================================= */

$userId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId) {

    header("Location: index.php");

    exit;
}

/* =========================================================
   CANDIDATE + PROFILE
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        u.id AS user_account_id,
        u.registration_no,
        u.full_name,
        u.email,
        u.mobile,
        u.status,
        u.remarks,
        u.reviewed_by,
        u.reviewed_at,
        u.first_login,
        u.current_step,
        u.registration_completed,
        u.profile_confirmed,
        u.created_at AS user_created_at,

        sp.*

    FROM users u

    LEFT JOIN student_profile sp
        ON sp.user_id = u.id

    WHERE u.id = ?

    LIMIT 1
");

$stmt->execute([$userId]);

$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {

    http_response_code(404);

    die("Candidate not found.");
}

/* =========================================================
   EDUCATION
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM education
    WHERE user_id = ?
    ORDER BY passing_year DESC, id DESC
");

$stmt->execute([$userId]);

$education = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   SKILLS
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM skills
    WHERE user_id = ?
    ORDER BY id ASC
");

$stmt->execute([$userId]);

$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   EXPERIENCE
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM experience
    WHERE user_id = ?
    ORDER BY start_date DESC, id DESC
");

$stmt->execute([$userId]);

$experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CERTIFICATES
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM certificates
    WHERE user_id = ?
    ORDER BY issue_date DESC, id DESC
");

$stmt->execute([$userId]);

$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   PROJECTS
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM projects
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$userId]);

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   HOBBIES
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM hobbies
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$userId]);

$hobbiesData = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   LANGUAGES
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        l.language_name,
        sl.can_read,
        sl.can_write,
        sl.can_speak

    FROM student_languages sl

    INNER JOIN languages l
        ON l.id = sl.language_id

    WHERE sl.user_id = ?

    ORDER BY l.language_name ASC
");

$stmt->execute([$userId]);

$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   SIBLINGS
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM student_siblings
    WHERE user_id = ?
    ORDER BY id ASC
");

$stmt->execute([$userId]);

$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   RESUME
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM resume
    WHERE user_id = ?
    ORDER BY uploaded_at DESC, id DESC
    LIMIT 1
");

$stmt->execute([$userId]);

$resume = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   VIDEO
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM video_upload
    WHERE user_id = ?
    ORDER BY uploaded_at DESC, id DESC
    LIMIT 1
");

$stmt->execute([$userId]);

$video = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   CALCULATED VALUES
========================================================= */

$displayName = !empty($candidate['full_name'])
    ? $candidate['full_name']
    : 'Candidate';

$registrationNo = !empty($candidate['registration_no'])
    ? $candidate['registration_no']
    : 'USR-' . $userId;

$status = !empty($candidate['status'])
    ? $candidate['status']
    : 'Pending';

$age = null;

if (!empty($candidate['dob'])) {

    try {

        $dobObject = new DateTime($candidate['dob']);

        $todayObject = new DateTime();

        $age = $dobObject->diff($todayObject)->y;

    } catch (Exception $exception) {

        $age = null;
    }
}

/* =========================================================
   PHOTO PATH
========================================================= */

$photoUrl = null;

if (!empty($candidate['photo'])) {

    $photoFile = basename($candidate['photo']);

    $possiblePhotos = [

        [
            'file' => "../../uploads/photos/" . $photoFile,
            'url'  => "../../uploads/photos/" . rawurlencode($photoFile)
        ],

        [
            'file' => "../../" . ltrim($candidate['photo'], "/"),
            'url'  => "../../" . ltrim($candidate['photo'], "/")
        ]

    ];

    foreach ($possiblePhotos as $photoPath) {

        if (is_file($photoPath['file'])) {

            $photoUrl = $photoPath['url'];

            break;
        }
    }
}

/* =========================================================
   RESUME PATH
========================================================= */

$resumeUrl = null;

if (!empty($resume['resume_file'])) {

    $resumeFile = basename($resume['resume_file']);

    $possibleResumePaths = [

        [
            'file' => "../../uploads/resumes/" . $resumeFile,
            'url'  => "../../uploads/resumes/" . rawurlencode($resumeFile)
        ],

        [
            'file' => "../../assets/uploads/resumes/" . $resumeFile,
            'url'  => "../../assets/uploads/resumes/" . rawurlencode($resumeFile)
        ],

        [
            'file' => "../../" . ltrim($resume['resume_file'], "/"),
            'url'  => "../../" . ltrim($resume['resume_file'], "/")
        ]

    ];

    foreach ($possibleResumePaths as $resumePath) {

        if (is_file($resumePath['file'])) {

            $resumeUrl = $resumePath['url'];

            break;
        }
    }
}

/* =========================================================
   VIDEO PATH
========================================================= */

$videoUrl = null;

if (!empty($video['video_file'])) {

    $videoFile = basename($video['video_file']);

    $possibleVideoPaths = [

        [
            'file' => "../../uploads/videos/" . $videoFile,
            'url'  => "../../uploads/videos/" . rawurlencode($videoFile)
        ],

        [
            'file' => "../../" . ltrim($video['video_file'], "/"),
            'url'  => "../../" . ltrim($video['video_file'], "/")
        ]

    ];

    foreach ($possibleVideoPaths as $videoPath) {

        if (is_file($videoPath['file'])) {

            $videoUrl = $videoPath['url'];

            break;
        }
    }
}

/* =========================================================
   GOVERNMENT ID IMAGE
========================================================= */

$govtIdUrl = null;

if (!empty($candidate['govt_id_image'])) {

    $govtFile = basename($candidate['govt_id_image']);

    $possibleGovtPaths = [

        [
            'file' => "../../uploads/documents/" . $govtFile,
            'url'  => "../../uploads/documents/" . rawurlencode($govtFile)
        ],

        [
            'file' => "../../" . ltrim($candidate['govt_id_image'], "/"),
            'url'  => "../../" . ltrim($candidate['govt_id_image'], "/")
        ]

    ];

    foreach ($possibleGovtPaths as $govtPath) {

        if (is_file($govtPath['file'])) {

            $govtIdUrl = $govtPath['url'];

            break;
        }
    }
}

/* =========================================================
   PWBD CERTIFICATE
========================================================= */

$pwbdCertificateUrl = null;

if (!empty($candidate['pwbd_certificate'])) {

    $pwbdFile = basename($candidate['pwbd_certificate']);

    $possiblePwbdPaths = [

        [
            'file' => "../../uploads/documents/" . $pwbdFile,
            'url'  => "../../uploads/documents/" . rawurlencode($pwbdFile)
        ],

        [
            'file' => "../../" . ltrim($candidate['pwbd_certificate'], "/"),
            'url'  => "../../" . ltrim($candidate['pwbd_certificate'], "/")
        ]

    ];

    foreach ($possiblePwbdPaths as $pwbdPath) {

        if (is_file($pwbdPath['file'])) {

            $pwbdCertificateUrl = $pwbdPath['url'];

            break;
        }
    }
}

$pageTitle = $displayName . " - Candidate Profile";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title><?= e($pageTitle) ?></title>

<link
    rel="stylesheet"
    href="../assets/css/admin.css"
>

<style>

/* =========================================================
   CANDIDATE 360
========================================================= */

.candidate-page {
    max-width: 1500px;
    margin: 0 auto;
}

.candidate-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.back-link {
    text-decoration: none;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}

.back-link:hover {
    color: #ef6b6b;
}

.candidate-profile-hero {
    background: #ffffff;
    border: 1px solid #e8edf3;
    border-radius: 18px;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    margin-bottom: 20px;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
}

.candidate-identity {
    display: flex;
    align-items: center;
    gap: 20px;
}

.candidate-photo {
    width: 105px;
    height: 105px;
    border-radius: 18px;
    overflow: hidden;
    background: #fff1f2;
    border: 1px solid #ffe4e6;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
}

.candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.candidate-photo-placeholder {
    font-size: 38px;
    font-weight: 800;
    color: #e25555;
}

.candidate-label {
    display: inline-block;
    color: #ef6b6b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.candidate-name {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 27px;
    line-height: 1.2;
}

.candidate-sub-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 18px;
    color: #64748b;
    font-size: 12px;
}

.profile-status {
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
}

.status-active {
    background: #ecfdf5;
    color: #15803d;
}

.status-pending {
    background: #fff7ed;
    color: #c2410c;
}

.status-blocked {
    background: #fef2f2;
    color: #b91c1c;
}

.candidate-status-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.status-card {
    background: #ffffff;
    border: 1px solid #e8edf3;
    border-radius: 14px;
    padding: 17px;
}

.status-card-label {
    display: block;
    color: #94a3b8;
    text-transform: uppercase;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
    margin-bottom: 8px;
}

.status-card-value {
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
}

.text-success {
    color: #15803d;
}

.text-warning {
    color: #b45309;
}

.text-muted {
    color: #64748b;
}

.profile-section {
    background: #ffffff;
    border: 1px solid #e8edf3;
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
}

.section-heading {
    padding: 18px 22px;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-heading span {
    display: block;
    color: #ef6b6b;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .8px;
    margin-bottom: 4px;
}

.section-heading h2 {
    margin: 0;
    color: #0f172a;
    font-size: 17px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}

.info-item {
    padding: 17px 21px;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-height: 77px;
}

.info-item-label {
    display: block;
    color: #94a3b8;
    text-transform: uppercase;
    font-size: 9px;
    letter-spacing: .5px;
    font-weight: 800;
    margin-bottom: 7px;
}

.info-item-value {
    color: #1e293b;
    font-size: 12px;
    line-height: 1.6;
    font-weight: 600;
    word-break: break-word;
}

.info-span-2 {
    grid-column: span 2;
}

.data-table-wrap {
    overflow-x: auto;
}

.profile-table {
    width: 100%;
    border-collapse: collapse;
}

.profile-table th {
    text-align: left;
    padding: 12px 17px;
    background: #f8fafc;
    color: #64748b;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}

.profile-table td {
    padding: 14px 17px;
    border-top: 1px solid #f1f5f9;
    color: #334155;
    font-size: 11px;
    vertical-align: top;
}

.profile-table td strong {
    color: #0f172a;
}

.skills-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 20px;
}

.skill-chip {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 10px;
    padding: 9px 12px;
}

.skill-name {
    color: #0f172a;
    font-size: 12px;
    font-weight: 700;
}

.skill-level {
    display: block;
    color: #64748b;
    font-size: 9px;
    margin-top: 3px;
}

.cards-grid {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

.detail-card {
    border: 1px solid #e8edf3;
    border-radius: 12px;
    padding: 16px;
}

.detail-card h3 {
    margin: 0 0 7px;
    color: #0f172a;
    font-size: 14px;
}

.detail-card .meta {
    color: #64748b;
    font-size: 10px;
    margin-bottom: 10px;
}

.detail-card p {
    margin: 7px 0 0;
    color: #475569;
    font-size: 11px;
    line-height: 1.7;
}

.language-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.language-badge {
    background: #f1f5f9;
    color: #475569;
    padding: 4px 7px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 700;
}

.file-grid {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.file-card {
    border: 1px solid #e8edf3;
    border-radius: 13px;
    padding: 18px;
}

.file-card h3 {
    margin: 0 0 7px;
    font-size: 14px;
    color: #0f172a;
}

.file-card p {
    color: #64748b;
    font-size: 10px;
    margin: 0 0 13px;
}

.file-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.file-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    border-radius: 7px;
    background: #ffffff;
    color: #334155;
    font-size: 10px;
    font-weight: 700;
}

.file-btn:hover {
    border-color: #ef6b6b;
    color: #e25555;
}

.file-btn-primary {
    background: #ef6b6b;
    border-color: #ef6b6b;
    color: #ffffff;
}

.file-btn-primary:hover {
    background: #e25555;
    color: #ffffff;
}

.video-wrapper {
    padding: 20px;
}

.video-wrapper video {
    width: 100%;
    max-width: 760px;
    border-radius: 12px;
    background: #0f172a;
}

.empty-state {
    padding: 24px;
    color: #94a3b8;
    font-size: 11px;
    text-align: center;
}

.declaration-box {
    margin: 20px;
    padding: 15px 17px;
    background: #f8fafc;
    border: 1px solid #e8edf3;
    border-radius: 10px;
    color: #475569;
    font-size: 11px;
    line-height: 1.7;
}

@media (max-width: 1200px) {

    .info-grid {
        grid-template-columns: repeat(3, 1fr);
    }

}

@media (max-width: 950px) {

    .candidate-status-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .candidate-profile-hero,
    .candidate-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .candidate-identity {
        align-items: flex-start;
    }

    .candidate-status-grid,
    .info-grid,
    .cards-grid,
    .file-grid {
        grid-template-columns: 1fr;
    }

    .info-span-2 {
        grid-column: span 1;
    }

}

/* =========================================================
   ADMIN REVIEW FORM
========================================================= */

.review-alert {
    margin-bottom: 18px;
    padding: 13px 16px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
}

.review-alert-success {
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    color: #15803d;
}

.review-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

.review-form {
    padding: 22px;
    border-top: 1px solid #eef2f7;
    background: #fafbfc;
}

.review-form-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 18px;
}

.review-field label {
    display: block;
    margin-bottom: 7px;

    color: #475569;

    font-size: 10px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .5px;
}

.review-control {
    width: 100%;

    border: 1px solid #dbe3ee;
    border-radius: 9px;

    background: #ffffff;

    color: #0f172a;

    font-family: inherit;
    font-size: 12px;

    padding: 11px 13px;

    outline: none;

    transition: .2s ease;
}

.review-control:focus {
    border-color: #ef6b6b;

    box-shadow:
        0 0 0 3px
        rgba(239, 107, 107, .10);
}

textarea.review-control {
    min-height: 100px;
    resize: vertical;
}

.review-actions {
    display: flex;
    justify-content: flex-end;

    margin-top: 16px;
}

.review-submit {
    border: none;
    border-radius: 9px;

    padding: 11px 20px;

    background: #ef6b6b;

    color: #ffffff;

    font-size: 11px;
    font-weight: 800;

    cursor: pointer;

    transition: .2s;
}

.review-submit:hover {
    background: #e25555;
    transform: translateY(-1px);
}

@media(max-width:700px) {

    .review-form-grid {
        grid-template-columns: 1fr;
    }

}

.toolbar-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.toolbar-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    color:#334155;
    background:#fff;
    border:1px solid #dbe3ee;
    transition:.3s;
    cursor:pointer;
}

.toolbar-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,.08);
}

.toolbar-btn.primary{
    background:#ef6b6b;
    color:#fff;
    border-color:#ef6b6b;
}

.toolbar-btn.success{
    background:#16a34a;
    color:#fff;
    border-color:#16a34a;
}

.toolbar-btn.dark{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
}

</style>

</head>

<body>

<div class="admin-layout">

    <?php include "../includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "../includes/header.php"; ?>

        <main class="admin-content">

            <div class="candidate-page">

            <?php if ($successMessage !== ''): ?>

    <div class="review-alert review-alert-success">

        <?= e($successMessage) ?>

    </div>

<?php endif; ?>


<?php if ($errorMessage !== ''): ?>

    <div class="review-alert review-alert-error">

        <?= e($errorMessage) ?>

    </div>

<?php endif; ?>

                <!-- =========================================
                     TOOLBAR
                ========================================== -->

                <div class="candidate-toolbar">

    <a href="index.php" class="back-link">
        ← Back to Candidates
    </a>

    <div class="toolbar-actions">

        <a href="candidate-report.php?id=<?= $userId ?>"
           target="_blank"
           class="toolbar-btn">
            👁 View Report
        </a>

        <a href="candidate-pdf.php?id=<?= $userId ?>"
           target="_blank"
           class="toolbar-btn primary">
            📄 Download PDF
        </a>

        <a href="export-excel.php?id=<?= $userId ?>"
           class="toolbar-btn success">
            📊 Excel
        </a>

        <button onclick="window.print()"
                class="toolbar-btn dark">
            🖨 Print
        </button>

    </div>

</div>


                <!-- =========================================
                     PROFILE HERO
                ========================================== -->

                <section class="candidate-profile-hero">

                    <div class="candidate-identity">

                        <div class="candidate-photo">

                            <?php if ($photoUrl): ?>

                                <img
                                    src="<?= e($photoUrl) ?>"
                                    alt="<?= e($displayName) ?>"
                                >

                            <?php else: ?>

                                <div class="candidate-photo-placeholder">

                                    <?= e(
                                        strtoupper(
                                            substr(
                                                $displayName,
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div>

                            <span class="candidate-label">
                                CANDIDATE 360° PROFILE
                            </span>

                            <h1 class="candidate-name">
                                <?= e($displayName) ?>
                            </h1>

                            <div class="candidate-sub-meta">

                                <span>
                                    Registration:
                                    <strong>
                                        <?= e($registrationNo) ?>
                                    </strong>
                                </span>

                                <span>
                                    <?= e($candidate['email']) ?>
                                </span>

                                <span>
                                    <?= e($candidate['mobile']) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     QUICK STATUS
                ========================================== -->

                <section class="candidate-status-grid">

                    <div class="status-card">

                        <span class="status-card-label">
                            Profile
                        </span>

                        <div class="status-card-value
                            <?= !empty($candidate['profile_confirmed'])
                                ? 'text-success'
                                : 'text-warning' ?>
                        ">

                            <?= !empty($candidate['profile_confirmed'])
                                ? 'Confirmed'
                                : 'Pending' ?>

                        </div>

                    </div>

                    <div class="status-card">

                        <span class="status-card-label">
                            Registration
                        </span>

                        <div class="status-card-value
                            <?= !empty($candidate['registration_completed'])
                                ? 'text-success'
                                : 'text-warning' ?>
                        ">

                            <?= !empty($candidate['registration_completed'])
                                ? 'Completed'
                                : 'Incomplete' ?>

                        </div>

                    </div>

                    <div class="status-card">

                        <span class="status-card-label">
                            Video Introduction
                        </span>

                        <div class="status-card-value
                            <?= $video
                                ? 'text-success'
                                : 'text-muted' ?>
                        ">

                            <?= $video
                                ? 'Uploaded'
                                : 'Not Uploaded' ?>

                        </div>

                    </div>

                    <div class="status-card">

                        <span class="status-card-label">
                            Current Step
                        </span>

                        <div class="status-card-value">

                            Step
                            <?= e(
                                $candidate['current_step']
                                ?? '-'
                            ) ?>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     BASIC INFORMATION
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>PERSONAL DETAILS</span>

                            <h2>Basic Information</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item">

                            <span class="info-item-label">
                                Full Name
                            </span>

                            <div class="info-item-value">
                                <?= e($displayName) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Registration Number
                            </span>

                            <div class="info-item-value">
                                <?= e($registrationNo) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Email
                            </span>

                            <div class="info-item-value">
                                <?= showValue($candidate['email']) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mobile
                            </span>

                            <div class="info-item-value">
                                <?= showValue($candidate['mobile']) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Date of Birth
                            </span>

                            <div class="info-item-value">
                                <?= e(
                                    formatDateValue(
                                        $candidate['dob']
                                        ?? null
                                    )
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Age
                            </span>

                            <div class="info-item-value">

                                <?= $age !== null
                                    ? e($age . ' Years')
                                    : '-' ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Gender
                            </span>

                            <div class="info-item-value">
                                <?= showValue($candidate['gender']) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Marital Status
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['marital_status']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Nationality
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['nationality']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Religion
                            </span>

                            <div class="info-item-value">

                                <?php

                                $religion =
                                    $candidate['religion']
                                    ?? null;

                                if (
                                    !empty(
                                        $candidate['other_religion']
                                    )
                                ) {

                                    $religion =
                                        $candidate['other_religion'];
                                }

                                echo showValue($religion);

                                ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Category
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['category']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Place of Birth
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['place_of_birth']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Domicile State
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['domicile_state']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Blood Group
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['blood_group']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Height
                            </span>

                            <div class="info-item-value">

                                <?php

                                if (
                                    $candidate['height_feet'] !== null
                                    &&
                                    $candidate['height_feet'] !== ''
                                ) {

                                    echo e(
                                        $candidate['height_feet']
                                    );

                                    echo " ft ";

                                    echo e(
                                        $candidate['height_inches']
                                        ?? 0
                                    );

                                    echo " in";

                                } elseif (
                                    !empty(
                                        $candidate['height']
                                    )
                                ) {

                                    echo e(
                                        $candidate['height']
                                    );

                                } else {

                                    echo '-';
                                }

                                ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Weight
                            </span>

                            <div class="info-item-value">

                                <?= !empty($candidate['weight'])
                                    ? e(
                                        $candidate['weight']
                                        . ' kg'
                                    )
                                    : '-' ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Alternate Number
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['alternate_number']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Emergency Contact
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['emergency_contact']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Passport Number
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['passport_no']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Account Status
                            </span>

                            <div class="info-item-value">
                                <?= e($status) ?>
                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     ADDRESS
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>CONTACT INFORMATION</span>

                            <h2>Address Details</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item info-span-2">

                            <span class="info-item-label">
                                Current / Correspondence Address
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['address']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                City
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['city']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                State
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['state']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Pincode
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['pincode']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item info-span-2">

                            <span class="info-item-label">
                                Permanent Address
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['permanent_address']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Permanent City
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['permanent_city']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Permanent State
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['permanent_state']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Permanent Pincode
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['permanent_pincode']
                                ) ?>
                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     GOVERNMENT IDENTIFICATION
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>IDENTIFICATION</span>

                            <h2>Government & Identity Details</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item">

                            <span class="info-item-label">
                                Aadhaar Number
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['aadhaar_no']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                PAN Number
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['pan_no']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Govt ID Type
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['govt_id_type']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Govt ID Number
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['govt_id_number']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                PWBD
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['pwbd']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                PWBD ID
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['pwbd_id']
                                ) ?>
                            </div>

                        </div>

                    </div>

                    <?php if (
                        $govtIdUrl
                        ||
                        $pwbdCertificateUrl
                    ): ?>

                        <div class="file-grid">

                            <?php if ($govtIdUrl): ?>

                                <div class="file-card">

                                    <h3>
                                        Government ID Document
                                    </h3>

                                    <p>
                                        Uploaded identity document
                                    </p>

                                    <div class="file-actions">

                                        <a
                                            href="<?= e($govtIdUrl) ?>"
                                            target="_blank"
                                            class="file-btn"
                                        >
                                            View Document
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <?php if ($pwbdCertificateUrl): ?>

                                <div class="file-card">

                                    <h3>
                                        PWBD Certificate
                                    </h3>

                                    <p>
                                        Uploaded disability certificate
                                    </p>

                                    <div class="file-actions">

                                        <a
                                            href="<?= e(
                                                $pwbdCertificateUrl
                                            ) ?>"
                                            target="_blank"
                                            class="file-btn"
                                        >
                                            View Certificate
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     CAREER OBJECTIVE
                ========================================== -->

                <?php if (
                    !empty(
                        $candidate['career_objective']
                    )
                ): ?>

                    <section class="profile-section">

                        <div class="section-heading">

                            <div>

                                <span>PROFILE SUMMARY</span>

                                <h2>Career Objective</h2>

                            </div>

                        </div>

                        <div class="declaration-box">

                            <?= nl2br(
                                e(
                                    $candidate[
                                        'career_objective'
                                    ]
                                )
                            ) ?>

                        </div>

                    </section>

                <?php endif; ?>


                <!-- =========================================
                     EDUCATION
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>ACADEMIC BACKGROUND</span>

                            <h2>Education</h2>

                        </div>

                    </div>

                    <?php if ($education): ?>

                        <div class="data-table-wrap">

                            <table class="profile-table">

                                <thead>

                                <tr>

                                    <th>Qualification</th>

                                    <th>Specialization</th>

                                    <th>Institute</th>

                                    <th>Board</th>

                                    <th>Passing Year</th>

                                    <th>Percentage</th>

                                    <th>Status</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $education as $edu
                                ): ?>

                                    <tr>

                                        <td>
                                            <strong>
                                                <?= showValue(
                                                    $edu[
                                                        'qualification'
                                                    ]
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $edu[
                                                    'specialization'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $edu[
                                                    'institute'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $edu['board']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $edu[
                                                    'passing_year'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>

                                            <?php

                                            if (
                                                $edu['percentage']
                                                !== null
                                                &&
                                                $edu['percentage']
                                                !== ''
                                            ) {

                                                echo e(
                                                    $edu[
                                                        'percentage'
                                                    ]
                                                );

                                                echo '%';

                                            } else {

                                                echo '-';
                                            }

                                            ?>

                                        </td>

                                        <td>
                                            <?= showValue(
                                                $edu['status']
                                            ) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No education records available.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     SKILLS
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>TECHNICAL CAPABILITIES</span>

                            <h2>Skills</h2>

                        </div>

                    </div>

                    <?php if ($skills): ?>

                        <div class="skills-wrap">

                            <?php foreach (
                                $skills as $skill
                            ): ?>

                                <div class="skill-chip">

                                    <span class="skill-name">

                                        <?= showValue(
                                            $skill['skill_name']
                                        ) ?>

                                    </span>

                                    <span class="skill-level">

                                        <?= showValue(
                                            $skill['skill_level']
                                        ) ?>

                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No skills added.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     EXPERIENCE
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>PROFESSIONAL BACKGROUND</span>

                            <h2>Experience</h2>

                        </div>

                    </div>

                    <?php if ($experience): ?>

                        <div class="cards-grid">

                            <?php foreach (
                                $experience as $exp
                            ): ?>

                                <div class="detail-card">

                                    <h3>
                                        <?= showValue(
                                            $exp['designation']
                                        ) ?>
                                    </h3>

                                    <div class="meta">

                                        <?= showValue(
                                            $exp['company_name']
                                        ) ?>

                                        ·

                                        <?= showValue(
                                            $exp[
                                                'employment_type'
                                            ]
                                        ) ?>

                                    </div>

                                    <p>

                                        <strong>Duration:</strong>

                                        <?= e(
                                            formatDateValue(
                                                $exp[
                                                    'start_date'
                                                ]
                                            )
                                        ) ?>

                                        —

                                        <?= !empty(
                                            $exp[
                                                'currently_working'
                                            ]
                                        )
                                            ? 'Present'
                                            : e(
                                                formatDateValue(
                                                    $exp[
                                                        'end_date'
                                                    ]
                                                )
                                            ) ?>

                                    </p>

                                    <?php if (
                                        !empty(
                                            $exp[
                                                'job_description'
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= nl2br(
                                                e(
                                                    $exp[
                                                        'job_description'
                                                    ]
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $exp['achievement']
                                        )
                                    ): ?>

                                        <p>

                                            <strong>
                                                Achievement:
                                            </strong>

                                            <?= nl2br(
                                                e(
                                                    $exp[
                                                        'achievement'
                                                    ]
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No experience records available / Candidate may be a fresher.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     CERTIFICATES
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>CREDENTIALS</span>

                            <h2>Certifications</h2>

                        </div>

                    </div>

                    <?php if ($certificates): ?>

                        <div class="data-table-wrap">

                            <table class="profile-table">

                                <thead>

                                <tr>

                                    <th>Certificate</th>

                                    <th>Organization</th>

                                    <th>Issue Date</th>

                                    <th>Certificate ID</th>

                                    <th>File</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $certificates
                                    as $certificate
                                ): ?>

                                    <tr>

                                        <td>
                                            <strong>
                                                <?= showValue(
                                                    $certificate[
                                                        'certificate_name'
                                                    ]
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $certificate[
                                                    'organization'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                formatDateValue(
                                                    $certificate[
                                                        'issue_date'
                                                    ]
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $certificate[
                                                    'certificate_id'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $certificate[
                                                        'certificate_file'
                                                    ]
                                                )
                                            ): ?>

                                                <?= e(
                                                    basename(
                                                        $certificate[
                                                            'certificate_file'
                                                        ]
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No certifications available.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     PROJECTS
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>PROJECT WORK</span>

                            <h2>Projects</h2>

                        </div>

                    </div>

                    <?php if ($projects): ?>

                        <div class="cards-grid">

                            <?php foreach (
                                $projects as $project
                            ): ?>

                                <div class="detail-card">

                                    <h3>
                                        <?= showValue(
                                            $project[
                                                'project_title'
                                            ]
                                        ) ?>
                                    </h3>

                                    <?php if (
                                        !empty(
                                            $project[
                                                'technologies'
                                            ]
                                        )
                                    ): ?>

                                        <div class="meta">

                                            Technologies:

                                            <?= e(
                                                $project[
                                                    'technologies'
                                                ]
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $project[
                                                'project_description'
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= nl2br(
                                                e(
                                                    $project[
                                                        'project_description'
                                                    ]
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No projects available.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     LANGUAGES
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>COMMUNICATION</span>

                            <h2>Languages</h2>

                        </div>

                    </div>

                    <?php if ($languages): ?>

                        <div class="data-table-wrap">

                            <table class="profile-table">

                                <thead>

                                <tr>

                                    <th>Language</th>

                                    <th>Proficiency</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $languages
                                    as $language
                                ): ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?= showValue(
                                                    $language[
                                                        'language_name'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <div class="language-badges">

                                                <?php if (
                                                    !empty(
                                                        $language[
                                                            'can_read'
                                                        ]
                                                    )
                                                ): ?>

                                                    <span class="language-badge">
                                                        Read
                                                    </span>

                                                <?php endif; ?>

                                                <?php if (
                                                    !empty(
                                                        $language[
                                                            'can_write'
                                                        ]
                                                    )
                                                ): ?>

                                                    <span class="language-badge">
                                                        Write
                                                    </span>

                                                <?php endif; ?>

                                                <?php if (
                                                    !empty(
                                                        $language[
                                                            'can_speak'
                                                        ]
                                                    )
                                                ): ?>

                                                    <span class="language-badge">
                                                        Speak
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No language information available.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     HOBBIES
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>PERSONAL INTERESTS</span>

                            <h2>Hobbies & Interests</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item info-span-2">

                            <span class="info-item-label">
                                Hobbies
                            </span>

                            <div class="info-item-value">

                                <?php

                                $candidateHobbies =
                                    $hobbiesData['hobbies']
                                    ??
                                    $candidate['hobbies']
                                    ??
                                    null;

                                echo showValue(
                                    $candidateHobbies
                                );

                                ?>

                            </div>

                        </div>

                        <div class="info-item info-span-2">

                            <span class="info-item-label">
                                Interests
                            </span>

                            <div class="info-item-value">

                                <?= showValue(
                                    $hobbiesData['interests']
                                    ?? null
                                ) ?>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     FAMILY
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>FAMILY INFORMATION</span>

                            <h2>Parents & Family Details</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item">

                            <span class="info-item-label">
                                Father's Name
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['father_name']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Father's Age
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['father_age']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Father's Qualification
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate[
                                        'father_qualification'
                                    ]
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Father's Occupation
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate[
                                        'father_occupation'
                                    ]
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Father's Annual Income
                            </span>

                            <div class="info-item-value">
                                <?= e(
                                    formatMoney(
                                        $candidate[
                                            'father_income'
                                        ]
                                    )
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mother's Name
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['mother_name']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mother's Age
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate['mother_age']
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mother's Qualification
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate[
                                        'mother_qualification'
                                    ]
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mother's Occupation
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate[
                                        'mother_occupation'
                                    ]
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Mother's Annual Income
                            </span>

                            <div class="info-item-value">
                                <?= e(
                                    formatMoney(
                                        $candidate[
                                            'mother_income'
                                        ]
                                    )
                                ) ?>
                            </div>

                        </div>

                    </div>

                    <?php if ($siblings): ?>

                        <div class="data-table-wrap">

                            <table class="profile-table">

                                <thead>

                                <tr>

                                    <th>Relation</th>

                                    <th>Name</th>

                                    <th>Age</th>

                                    <th>Qualification</th>

                                    <th>Occupation</th>

                                    <th>Annual Income</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $siblings as $sibling
                                ): ?>

                                    <tr>

                                        <td>
                                            <?= showValue(
                                                $sibling[
                                                    'relation'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= showValue(
                                                    $sibling[
                                                        'name'
                                                    ]
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $sibling[
                                                    'age'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $sibling[
                                                    'qualification'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= showValue(
                                                $sibling[
                                                    'occupation'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                formatMoney(
                                                    $sibling[
                                                        'annual_income'
                                                    ]
                                                )
                                            ) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     NAME CHANGE
                ========================================== -->

                <?php if (
                    isset($candidate['name_changed'])
                ): ?>

                    <section class="profile-section">

                        <div class="section-heading">

                            <div>

                                <span>ADDITIONAL INFORMATION</span>

                                <h2>Name Change Details</h2>

                            </div>

                        </div>

                        <div class="info-grid">

                            <div class="info-item">

                                <span class="info-item-label">
                                    Name Changed
                                </span>

                                <div class="info-item-value">
                                    <?= showValue(
                                        $candidate[
                                            'name_changed'
                                        ]
                                    ) ?>
                                </div>

                            </div>

                            <?php if (
                                !empty(
                                    $candidate[
                                        'previous_name'
                                    ]
                                )
                            ): ?>

                                <div class="info-item">

                                    <span class="info-item-label">
                                        Previous Name
                                    </span>

                                    <div class="info-item-value">
                                        <?= e(
                                            $candidate[
                                                'previous_name'
                                            ]
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                <?php endif; ?>


                <!-- =========================================
                     RESUME
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>APPLICATION DOCUMENT</span>

                            <h2>Resume</h2>

                        </div>

                    </div>

                    <?php if ($resume): ?>

                        <div class="file-grid">

                            <div class="file-card">

                                <h3>

                                    <?= showValue(
                                        $resume[
                                            'original_name'
                                        ]
                                        ??
                                        basename(
                                            $resume[
                                                'resume_file'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>

                                </h3>

                                <p>

                                    Type:
                                    <?= showValue(
                                        $resume[
                                            'resume_type'
                                        ]
                                    ) ?>

                                    · Uploaded:

                                    <?= e(
                                        formatDateValue(
                                            $resume[
                                                'uploaded_at'
                                            ]
                                        )
                                    ) ?>

                                </p>

                                <?php if (
                                    !empty(
                                        $resume['file_size']
                                    )
                                ): ?>

                                    <p>

                                        File Size:

                                        <?= e(
                                            number_format(
                                                $resume[
                                                    'file_size'
                                                ]
                                                / 1024,
                                                1
                                            )
                                        ) ?>

                                        KB

                                    </p>

                                <?php endif; ?>

                                <?php if ($resumeUrl): ?>

                                    <div class="file-actions">

                                        <a
                                            href="<?= e(
                                                $resumeUrl
                                            ) ?>"
                                            target="_blank"
                                            class="file-btn file-btn-primary"
                                        >
                                            View Resume
                                        </a>

                                        <a
                                            href="<?= e(
                                                $resumeUrl
                                            ) ?>"
                                            download
                                            class="file-btn"
                                        >
                                            Download
                                        </a>

                                    </div>

                                <?php else: ?>

                                    <p>
                                        Resume database record exists,
                                        but file was not found in the
                                        expected upload folders.
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No resume uploaded or generated.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     VIDEO INTRODUCTION
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>CANDIDATE PRESENTATION</span>

                            <h2>Video Introduction</h2>

                        </div>

                    </div>

                    <?php if (
                        $video
                        &&
                        $videoUrl
                    ): ?>

                        <div class="video-wrapper">

                            <video controls preload="metadata">

                                <source
                                    src="<?= e($videoUrl) ?>"
                                >

                                Your browser does not support
                                HTML5 video.

                            </video>

                            <p
                                style="
                                    margin-top:10px;
                                    color:#64748b;
                                    font-size:10px;
                                "
                            >

                                Uploaded:

                                <?= e(
                                    formatDateValue(
                                        $video[
                                            'uploaded_at'
                                        ]
                                    )
                                ) ?>

                            </p>

                        </div>

                    <?php elseif ($video): ?>

                        <div class="empty-state">

                            Video database record exists,
                            but the video file was not found
                            in the expected upload folder.

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            Candidate has not uploaded a video introduction.
                        </div>

                    <?php endif; ?>

                </section>


                <!-- =========================================
                     DECLARATION
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>CONFIRMATION</span>

                            <h2>Candidate Declaration</h2>

                        </div>

                    </div>

                    <div class="declaration-box">

                        <?php if (
                            !empty(
                                $candidate['declaration']
                            )
                        ): ?>

                            Candidate has confirmed the declaration
                            that the information provided in the
                            application is true and correct.

                        <?php else: ?>

                            Candidate declaration has not been
                            confirmed yet.

                        <?php endif; ?>

                    </div>

                </section>


                <!-- =========================================
                     ADMIN REVIEW
                ========================================== -->

                <section class="profile-section">

                    <div class="section-heading">

                        <div>

                            <span>ADMINISTRATION</span>

                            <h2>Application Review</h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item">

                            <span class="info-item-label">
                                Account Status
                            </span>

                            <div class="info-item-value">
                                <?= e($status) ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Registered On
                            </span>

                            <div class="info-item-value">

                                <?= e(
                                    formatDateValue(
                                        $candidate[
                                            'user_created_at'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Reviewed At
                            </span>

                            <div class="info-item-value">

                                <?= e(
                                    formatDateValue(
                                        $candidate[
                                            'reviewed_at'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <span class="info-item-label">
                                Reviewed By
                            </span>

                            <div class="info-item-value">
                                <?= showValue(
                                    $candidate[
                                        'reviewed_by'
                                    ]
                                ) ?>
                            </div>

                        </div>

                        <div class="info-item info-span-2">

                            <span class="info-item-label">
                                Admin Remarks
                            </span>

                            <div class="info-item-value">

                                <?= !empty(
                                    $candidate['remarks']
                                )
                                    ? nl2br(
                                        e(
                                            $candidate[
                                                'remarks'
                                            ]
                                        )
                                    )
                                    : '-' ?>

                            </div>

                        </div>

                    </div>

                    <form
    method="POST"
    class="review-form"
>

    <input
        type="hidden"
        name="candidate_id"
        value="<?= (int)$userId ?>"
    >

    <div class="review-form-grid">

        <!-- STATUS -->

        <div class="review-field">

            <label for="candidateStatus">

                Candidate Status

            </label>

            <select
                name="status"
                id="candidateStatus"
                class="review-control"
                required
            >

                <option
                    value="Pending"
                    <?= $status === 'Pending'
                        ? 'selected'
                        : '' ?>
                >
                    Pending
                </option>

                <option
                    value="Active"
                    <?= $status === 'Active'
                        ? 'selected'
                        : '' ?>
                >
                    Active
                </option>

                <option
                    value="Blocked"
                    <?= $status === 'Blocked'
                        ? 'selected'
                        : '' ?>
                >
                    Blocked
                </option>

            </select>

        </div>


        <!-- REMARKS -->

        <div class="review-field">

            <label for="candidateRemarks">

                Admin Remarks

            </label>

            <textarea
                name="remarks"
                id="candidateRemarks"
                class="review-control"
                placeholder="Enter review remarks..."
            ><?= e($candidate['remarks'] ?? '') ?></textarea>

        </div>

    </div>


    <div class="review-actions">

        <button
            type="submit"
            name="update_candidate_status"
            class="review-submit"
        >

            Save Review

        </button>

    </div>

</form>
                    

                </section>

            </div>

        </main>

    </div>

</div>

<script src="../assets/js/admin.js"></script>

</body>

</html>