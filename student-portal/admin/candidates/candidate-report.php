<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../db.php";

/* ===========================
   Candidate ID
=========================== */

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId) {
    die("Invalid Candidate ID");
}

/* ===========================
   Helper Functions
=========================== */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function showValue($value, $fallback='-')
{
    return ($value === null || $value === '') ? $fallback : e($value);
}

function formatDateValue($date)
{
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }

    return date("d M Y", strtotime($date));
}

/* ===========================
   Candidate
=========================== */

$stmt = $pdo->prepare("
SELECT
u.*,
sp.*
FROM users u
LEFT JOIN student_profile sp
ON sp.user_id=u.id
WHERE u.id=?
LIMIT 1
");

$stmt->execute([$userId]);

$candidate=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$candidate){
    die("Candidate Not Found");
}

/* ===========================
   Education
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM education
WHERE user_id=?
ORDER BY passing_year DESC
");

$stmt->execute([$userId]);

$education=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Skills
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM skills
WHERE user_id=?
");

$stmt->execute([$userId]);

$skills=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Experience
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM experience
WHERE user_id=?
ORDER BY id DESC
");

$stmt->execute([$userId]);

$experience=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Certificates
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM certificates
WHERE user_id=?
");

$stmt->execute([$userId]);

$certificates=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Projects
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM projects
WHERE user_id=?
");

$stmt->execute([$userId]);

$projects=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   Languages
=========================== */

$stmt=$pdo->prepare("
SELECT
l.language_name,
sl.can_read,
sl.can_write,
sl.can_speak
FROM student_languages sl
INNER JOIN languages l
ON l.id=sl.language_id
WHERE sl.user_id=?
");

$stmt->execute([$userId]);

$languages=$stmt->fetchAll(PDO::FETCH_ASSOC);


/* ===========================
   Siblings
=========================== */

$stmt = $pdo->prepare("
SELECT *
FROM student_siblings
WHERE user_id = ?
ORDER BY id ASC
");

$stmt->execute([$userId]);

$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ===========================
   Resume
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM resume
WHERE user_id=?
LIMIT 1
");

$stmt->execute([$userId]);

$resume=$stmt->fetch(PDO::FETCH_ASSOC);

/* ===========================
   Video
=========================== */

$stmt=$pdo->prepare("
SELECT *
FROM video_upload
WHERE user_id=?
LIMIT 1
");

$stmt->execute([$userId]);

$video=$stmt->fetch(PDO::FETCH_ASSOC);

/* ===========================
   Variables
=========================== */

$displayName=$candidate['full_name'] ?? '';

$registrationNo=$candidate['registration_no'] ?? '';

$status=$candidate['status'] ?? '';

$photo=$candidate['photo'] ?? '';
?>

<!DOCTYPE html>

<html>

<head>

<title>Candidate Report</title>
<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Segoe UI,sans-serif;
}

body{
    background:#f5f7fb;
    color:#1e293b;
    padding:30px;
}

.report{
    max-width:1200px;
    margin:auto;
    background:#fff;
    border-radius:15px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.report-header{
    background:#0F172A;
    color:#fff;
    padding:30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.report-header h1{
    font-size:28px;
}

.report-header p{
    margin-top:6px;
    color:#cbd5e1;
}

.summary{
    display:flex;
    gap:25px;
    align-items:center;
    padding:30px;
    border-bottom:1px solid #eee;
}

.photo{

    width:130px;
    height:130px;
    border-radius:12px;
    overflow:hidden;
    border:4px solid #ef6b6b;

}

.photo img{

    width:100%;
    height:100%;
    object-fit:cover;

}

.no-photo{

    width:100%;
    height:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f1f5f9;
    font-size:40px;
    font-weight:bold;

}

.summary-info h2{

    font-size:30px;
    color:#0F172A;

}

.summary-info p{

    margin-top:8px;
    color:#475569;

}

.section{

    padding:25px 30px;

}

.section-title{

    font-size:20px;
    color:#EF6B6B;
    margin-bottom:20px;
    border-left:5px solid #EF6B6B;
    padding-left:12px;

}

.info-grid{

    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;

}

.info-box{

    background:#f8fafc;
    border:1px solid #e2e8f0;
    padding:15px;
    border-radius:10px;

}

.info-box small{

    display:block;
    color:#64748b;
    margin-bottom:5px;

}

.info-box strong{

    color:#0f172a;

}

.report-table{

    width:100%;
    border-collapse:collapse;

}

.report-table th{

    background:#0F172A;
    color:#fff;
    padding:12px;
    text-align:left;
    font-size:14px;

}

.report-table td{

    padding:12px;
    border:1px solid #e5e7eb;
    font-size:13px;

}

.report-table tr:nth-child(even){

    background:#f8fafc;

}

.empty-box{

    padding:25px;
    background:#f8fafc;
    border:1px dashed #cbd5e1;
    text-align:center;
    border-radius:10px;
    color:#64748b;

}

.skills{

display:flex;
flex-wrap:wrap;
gap:12px;

}

.skill{

background:#EF6B6B;
color:#fff;
padding:10px 16px;
border-radius:30px;
font-size:13px;
font-weight:600;

}

.card{

    background:#fff;

    border-left:5px solid #EF6B6B;

    border-radius:12px;

    padding:20px;

    margin-bottom:20px;

    box-shadow:0 4px 15px rgba(0,0,0,.08);

}

.card h3{

    color:#0F172A;

    margin-bottom:10px;

}

.card p{

    margin-top:8px;

    color:#475569;

    line-height:1.7;

}

.download-btn{

display:inline-block;

padding:12px 20px;

background:#2563EB;

color:#fff;

text-decoration:none;

border-radius:8px;

font-weight:600;

}

.download-btn:hover{

background:#1d4ed8;

}

video{

border-radius:12px;

box-shadow:0 5px 20px rgba(0,0,0,.12);

margin-top:15px;

}

.action-buttons{
    display:flex;
    gap:12px;
    justify-content:flex-end;
    padding:20px 30px;
    background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
}

.btn{
    padding:10px 18px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    color:#fff;
    transition:.3s;
}

.btn-print{
    background:#2563EB;
}

.btn-print:hover{
    background:#1d4ed8;
}

.btn-pdf{
    background:#16A34A;
}

.btn-pdf:hover{
    background:#15803d;
}

.btn-excel{
    background:#EA580C;
}

.btn-excel:hover{
    background:#C2410C;
}

</style>
</head>

<body>

<div class="report">

<div class="report-header">

<div>

<h1>PRAYAS THE AUTOMATION ACADEMY</h1>

<p>Candidate Recruitment Report</p>

</div>

<div>

<?= date("d M Y") ?>

</div>

</div>


<div class="summary">

<div class="photo">

<?php if(!empty($photo)){ ?>

<img src="../../uploads/photos/<?= e($photo) ?>">

<?php } else { ?>

<div class="no-photo">

<?= strtoupper(substr($displayName,0,1)) ?>

</div>

<?php } ?>

</div>

<div class="summary-info">

<h2><?= e($displayName) ?></h2>

<p><b>Registration :</b> <?= e($registrationNo) ?></p>

<p><b>Email :</b> <?= e($candidate['email']) ?></p>

<p><b>Mobile :</b> <?= e($candidate['mobile']) ?></p>

<p><b>Status :</b> <?= e($status) ?></p>

</div>

</div>

<div class="section">

<div class="section-title">

Basic Information

</div>

<div class="info-grid">

<div class="info-box">

<small>Date of Birth</small>

<strong><?= showValue($candidate['dob']) ?></strong>

</div>

<div class="info-box">

<small>Gender</small>

<strong><?= showValue($candidate['gender']) ?></strong>

</div>

<div class="info-box">

<small>Nationality</small>

<strong><?= showValue($candidate['nationality']) ?></strong>

</div>

<div class="info-box">

<small>Religion</small>

<strong><?= showValue($candidate['religion']) ?></strong>

</div>

<div class="info-box">

<small>Blood Group</small>

<strong><?= showValue($candidate['blood_group']) ?></strong>

</div>

<div class="info-box">

<small>Category</small>

<strong><?= showValue($candidate['category']) ?></strong>

</div>

<div class="info-box">

<small>Aadhaar</small>

<strong><?= showValue($candidate['aadhaar_no']) ?></strong>

</div>

<div class="info-box">

<small>PAN</small>

<strong><?= showValue($candidate['pan_no']) ?></strong>

</div>

</div>

</div>

<div class="section">

    <div class="section-title">
        Education Details
    </div>

    <?php if($education){ ?>

    <table class="report-table">

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

        <?php foreach($education as $edu){ ?>

            <tr>

                <td><?= showValue($edu['qualification']) ?></td>

                <td><?= showValue($edu['specialization']) ?></td>

                <td><?= showValue($edu['institute']) ?></td>

                <td><?= showValue($edu['board']) ?></td>

                <td><?= showValue($edu['passing_year']) ?></td>

                <td><?= showValue($edu['percentage']) ?>%</td>

                <td><?= showValue($edu['status']) ?></td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

    <?php }else{ ?>

    <div class="empty-box">
        No Education Record Found
    </div>

    <?php } ?>

</div>

<div class="section">

<div class="section-title">
Technical Skills
</div>

<div class="skills">

<?php foreach($skills as $skill){ ?>

<span class="skill">

<?= showValue($skill['skill_name']) ?>

</span>

<?php } ?>

</div>

</div>

<div class="section">

    <div class="section-title">
        Work Experience
    </div>

    <?php if($experience){ ?>

        <?php foreach($experience as $exp){ ?>

        <div class="card">

            <h3><?= showValue($exp['designation']) ?></h3>

            <p>
                <strong>Company :</strong>
                <?= showValue($exp['company_name']) ?>
            </p>

            <p>
                <strong>Employment :</strong>
                <?= showValue($exp['employment_type']) ?>
            </p>

            <p>
                <strong>Duration :</strong>

                <?= formatDateValue($exp['start_date']) ?>

                -

                <?=
                !empty($exp['currently_working'])
                    ? 'Present'
                    : formatDateValue($exp['end_date']);
                ?>

            </p>

            <?php if(!empty($exp['job_description'])){ ?>

            <p>

                <strong>Description :</strong>

                <?= nl2br(e($exp['job_description'])) ?>

            </p>

            <?php } ?>

        </div>

        <?php } ?>

    <?php }else{ ?>

        <div class="empty-box">

            Candidate is Fresher

        </div>

    <?php } ?>

</div>

<div class="section">

<div class="section-title">

Projects

</div>

<?php if($projects){ ?>

<?php foreach($projects as $project){ ?>

<div class="card">

<h3>

<?= showValue($project['project_title']) ?>

</h3>

<p>

<strong>Technology :</strong>

<?= showValue($project['technologies']) ?>

</p>

<p>

<strong>Description :</strong>

<?= nl2br(e($project['description'])) ?>

</p>

</div>

<?php } ?>

<?php } else { ?>

<div class="empty-box">

No Project Available

</div>

<?php } ?>

</div>

<div class="section">

<div class="section-title">

Certificates

</div>

<?php if($certificates){ ?>

<table class="report-table">

<thead>

<tr>

<th>Certificate</th>

<th>Organization</th>

<th>Issue Date</th>

<th>ID</th>

</tr>

</thead>

<tbody>

<?php foreach($certificates as $c){ ?>

<tr>

<td>

<?= showValue($c['certificate_name']) ?>

</td>

<td>

<?= showValue($c['organization']) ?>

</td>

<td>

<?= formatDateValue($c['issue_date']) ?>

</td>

<td>

<?= showValue($c['certificate_id']) ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<?php }else{ ?>

<div class="empty-box">

No Certificate Uploaded

</div>

<?php } ?>

</div>

<div class="section">

    <div class="section-title">
        Family Details
    </div>

    <div class="info-grid">

        <div class="info-box">
            <small>Father Name</small>
            <strong><?= showValue($candidate['father_name']) ?></strong>
        </div>

        <div class="info-box">
            <small>Father Occupation</small>
            <strong><?= showValue($candidate['father_occupation']) ?></strong>
        </div>

        <div class="info-box">
            <small>Mother Name</small>
            <strong><?= showValue($candidate['mother_name']) ?></strong>
        </div>

        <div class="info-box">
            <small>Mother Occupation</small>
            <strong><?= showValue($candidate['mother_occupation']) ?></strong>
        </div>

    </div>

</div>

<?php if($siblings){ ?>

<div class="section">

    <div class="section-title">
        Brother / Sister Details
    </div>

    <table class="report-table">

        <thead>
            <tr>
                <th>Name</th>
                <th>Relation</th>
                <th>Age</th>
                <th>Qualification</th>
                <th>Occupation</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($siblings as $sibling){ ?>

        <tr>

            <td><?= showValue($sibling['name']) ?></td>

            <td><?= showValue($sibling['relation']) ?></td>

            <td><?= showValue($sibling['age']) ?></td>

            <td><?= showValue($sibling['qualification']) ?></td>

            <td><?= showValue($sibling['occupation']) ?></td>

        </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

<?php } ?>

<div class="section">

<div class="section-title">

Languages

</div>

<?php if($languages){ ?>

<table class="report-table">

<thead>

<tr>

<th>Language</th>
<th>Read</th>
<th>Write</th>
<th>Speak</th>

</tr>

</thead>

<tbody>

<?php foreach($languages as $lang){ ?>

<tr>

<td><?= showValue($lang['language_name']) ?></td>

<td><?= !empty($lang['can_read']) ? "✔" : "-" ?></td>

<td><?= !empty($lang['can_write']) ? "✔" : "-" ?></td>

<td><?= !empty($lang['can_speak']) ? "✔" : "-" ?></td>

</tr>

<?php } ?>

</tbody>

</table>

<?php } else { ?>

<div class="empty-box">

No Language Added

</div>



<?php } ?>

</div>

<div class="section">

<div class="section-title">

Resume

</div>

<?php if(!empty($resume['resume_file'])){ ?>

<a
href="../../uploads/resumes/<?= basename($resume['resume_file']) ?>"
target="_blank"
class="download-btn">

Download Resume

</a>

<?php } else { ?>

<div class="empty-box">

Resume Not Uploaded

</div>

<?php } ?>

</div>

<div class="section">

    <div class="section-title">
        Candidate Documents
    </div>

    <table class="report-table">

        <thead>

        <tr>
            <th>Document</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        </thead>

        <tbody>

        <tr>

            <td>Aadhaar Card</td>

            <td>
                <?= !empty($candidate['aadhaar_image']) ? 'Uploaded' : 'Not Uploaded'; ?>
            </td>

            <td>

            <?php if(!empty($candidate['aadhaar_image'])){ ?>

                <a
                href="../../uploads/documents/<?= basename($candidate['aadhaar_image']) ?>"
                target="_blank"
                class="download-btn">

                View

                </a>

            <?php } ?>

            </td>

        </tr>

        <tr>

            <td>PAN Card</td>

            <td>

            <?= !empty($candidate['pan_image']) ? 'Uploaded' : 'Not Uploaded'; ?>

            </td>

            <td>

            <?php if(!empty($candidate['pan_image'])){ ?>

            <a
            href="../../uploads/documents/<?= basename($candidate['pan_image']) ?>"
            target="_blank"
            class="download-btn">

            View

            </a>

            <?php } ?>

            </td>

        </tr>

        <tr>

            <td>Resume</td>

            <td>

            <?= !empty($resume['resume_file']) ? 'Uploaded' : 'Not Uploaded'; ?>

            </td>

            <td>

            <?php if(!empty($resume['resume_file'])){ ?>

            <a
            href="../../uploads/resumes/<?= basename($resume['resume_file']) ?>"
            target="_blank"
            class="download-btn">

            Download

            </a>

            <?php } ?>

            </td>

        </tr>

        </tbody>

    </table>

</div>

<div class="section">

<div class="section-title">

Video Introduction

</div>

<?php if(!empty($video['video_file'])){ ?>

<video
controls
width="100%">

<source
src="../../uploads/videos/<?= basename($video['video_file']) ?>">

</video>

<?php } else { ?>

<div class="empty-box">

Video Not Uploaded

</div>

<?php } ?>

</div>


</div>


</body>

</html>