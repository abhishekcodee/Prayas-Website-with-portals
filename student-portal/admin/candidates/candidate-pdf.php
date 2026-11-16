<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../db.php";
require_once "../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId) {
    die("Invalid Candidate ID");
}

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function showValue($value, $fallback = '-')
{
    return ($value === null || $value === '') ? $fallback : e($value);
}

$stmt = $pdo->prepare("
SELECT u.*, sp.*
FROM users u
LEFT JOIN student_profile sp ON sp.user_id = u.id
WHERE u.id = ?
LIMIT 1
");

$stmt->execute([$userId]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    die("Candidate not found.");
}

$stmt = $pdo->prepare("SELECT * FROM education WHERE user_id=?");
$stmt->execute([$userId]);
$education = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id=?");
$stmt->execute([$userId]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Experience */
$stmt = $pdo->prepare("SELECT * FROM experience WHERE user_id=?");
$stmt->execute([$userId]);
$experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Projects */
$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id=?");
$stmt->execute([$userId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Certificates */
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id=?");
$stmt->execute([$userId]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Languages */
$stmt = $pdo->prepare("
SELECT l.language_name,
       sl.can_read,
       sl.can_write,
       sl.can_speak
FROM student_languages sl
JOIN languages l ON l.id = sl.language_id
WHERE sl.user_id=?
");
$stmt->execute([$userId]);
$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Siblings */
$stmt = $pdo->prepare("SELECT * FROM student_siblings WHERE user_id=?");
$stmt->execute([$userId]);
$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Resume */
$stmt = $pdo->prepare("SELECT * FROM resume WHERE user_id=? LIMIT 1");
$stmt->execute([$userId]);
$resume = $stmt->fetch(PDO::FETCH_ASSOC);

/* Video */
$stmt = $pdo->prepare("SELECT * FROM video_upload WHERE user_id=? LIMIT 1");
$stmt->execute([$userId]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

$photoPath = "../../uploads/photos/" . $candidate['photo'];

if (empty($candidate['photo']) || !file_exists($photoPath)) {
    $photoPath = "";
}

ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Candidate Report</title>

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:12px;
    color:#333;
    margin:20px;
}

h1,h2,h3{
    margin:0;
}

.header{
    text-align:center;
    border-bottom:2px solid #0F172A;
    padding-bottom:10px;
    margin-bottom:20px;
}

.section{
    margin-top:20px;
}

.section-title{
    background:#0F172A;
    color:#fff;
    padding:8px 10px;
    font-size:14px;
    font-weight:bold;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

table th,
table td{
    border:1px solid #ccc;
    padding:8px;
    font-size:11px;
}

.label{
    width:30%;
    font-weight:bold;
}

</style>

</head>
<body>

<div class="header">
    <h1>PRAYAS - Student Recruitment Portal</h1>
    <p>Candidate Profile Report</p>
</div>

<div class="section">

<div class="section-title">
Candidate Information
</div>

<table>
    <tr>

        <!-- LEFT SIDE -->
        <td width="80%" style="vertical-align:top;">

            <table width="100%" cellpadding="6">

                <tr>
                    <td class="label">Full Name</td>
                    <td><?= showValue($candidate['full_name']) ?></td>
                </tr>

                <tr>
                    <td class="label">Email</td>
                    <td><?= showValue($candidate['email']) ?></td>
                </tr>

                <tr>
                    <td class="label">Mobile</td>
                    <td><?= showValue($candidate['mobile']) ?></td>
                </tr>

                <tr>
                    <td class="label">Registration ID</td>
                    <td><?= showValue($candidate['registration_no']) ?></td>
                </tr>

            </table>

        </td>

        <!-- RIGHT SIDE PHOTO -->
        <td width="20%" align="center" valign="top">

            <?php if($photoPath!=""){ ?>

                <img src="<?= $photoPath ?>" width="120" height="140">

            <?php }else{ ?>

                <div style="width:120px;height:140px;border:1px solid #000;
                text-align:center;line-height:140px;">
                    No Photo
                </div>

            <?php } ?>

        </td>

    </tr>
</table>

</div>

<div class="section">

    <div class="section-title">
        Education Details
    </div>

    <table>

        <tr>
            <th>Qualification</th>
            <th>Institute</th>
            <th>Board / University</th>
            <th>Passing Year</th>
            <th>Percentage / CGPA</th>
        </tr>

        <?php if (!empty($education)) { ?>

            <?php foreach ($education as $row) { ?>

                <tr>

                    <td><?= showValue($row['qualification']) ?></td>

                    <td><?= showValue($row['institute']) ?></td>

                    <td><?= showValue($row['board']) ?></td>

                    <td><?= showValue($row['passing_year']) ?></td>

                    <td><?= showValue($row['percentage']) ?></td>

                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    No Education Record Found
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Skills
    </div>

    <table>

        <tr>
            <th width="5%">#</th>
            <th>Skill Name</th>
            <th>Skill Level</th>
        </tr>

        <?php if (!empty($skills)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($skills as $skill) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($skill['skill_name']) ?></td>

                    <td><?= showValue($skill['skill_level']) ?></td>

                    

                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="4" style="text-align:center;">
                    No Skills Added
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Experience Details
    </div>

    <table>

        <tr>
            <th width="5%">#</th>
            <th>Company</th>
            <th>Designation</th>
            <th>From</th>
            <th>To</th>
            <th>Total Experience</th>
        </tr>

        <?php if (!empty($experience)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($experience as $exp) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($exp['company_name']) ?></td>

                    <td><?= showValue($exp['designation']) ?></td>

                    <td><?= showValue($exp['start_date']) ?></td>

                    <td><?= showValue($exp['end_date']) ?></td>


                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="6" style="text-align:center;">
                    No Experience Added
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Projects
    </div>

    <table>

        <tr>
            <th width="5%">#</th>
            <th>Project Name</th>
            <th>Technology</th>
            <th>Description</th>
            <th>Duration</th>
        </tr>

        <?php if (!empty($projects)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($projects as $project) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($project['project_title']) ?></td>

                    <td><?= showValue($project['technologies']) ?></td>

                    <td><?= showValue($project['project_description']) ?></td>


                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    No Projects Added
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Certificates
    </div>

    <table>

        <tr>
            <th width="5%">#</th>
            <th>Certificate Name</th>
            <th>Organization</th>
            <th>Issue Date</th>
            <th>Certificate ID</th>
        </tr>

        <?php if (!empty($certificates)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($certificates as $certificate) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($certificate['certificate_name']) ?></td>

                    <td><?= showValue($certificate['organization']) ?></td>

                    <td><?= showValue($certificate['issue_date']) ?></td>

                    <td><?= showValue($certificate['certificate_id']) ?></td>

                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    No Certificates Added
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Family Details
    </div>

    <table>

        <tr>
            <td class="label">Father Name</td>
            <td><?= showValue($candidate['father_name']) ?></td>

            <td class="label">Father Occupation</td>
            <td><?= showValue($candidate['father_occupation']) ?></td>
        </tr>

        <tr>
            <td class="label">Father Qualification</td>
            <td><?= showValue($candidate['father_qualification']) ?></td>

            <td class="label">Father Income</td>
            <td><?= showValue($candidate['father_income']) ?></td>
        </tr>

        <tr>
            <td class="label">Mother Name</td>
            <td><?= showValue($candidate['mother_name']) ?></td>

            <td class="label">Mother Occupation</td>
            <td><?= showValue($candidate['mother_occupation']) ?></td>
        </tr>

        <tr>
            <td class="label">Mother Qualification</td>
            <td><?= showValue($candidate['mother_qualification']) ?></td>

            <td class="label">Mother Income</td>
            <td><?= showValue($candidate['mother_income']) ?></td>
        </tr>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Brother / Sister Details
    </div>

    <table>

        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Relation</th>
            <th>Age</th>
            <th>Occupation</th>
        </tr>

        <?php if (!empty($siblings)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($siblings as $row) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($row['name']) ?></td>

                    <td><?= showValue($row['relation']) ?></td>

                    <td><?= showValue($row['age']) ?></td>

                    <td><?= showValue($row['occupation']) ?></td>

                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    No Brother / Sister Details Found
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Languages Known
    </div>

    <table>

        <tr>
            <th>#</th>
            <th>Language</th>
            <th>Read</th>
            <th>Write</th>
            <th>Speak</th>
        </tr>

        <?php if (!empty($languages)) { ?>

            <?php $i = 1; ?>

            <?php foreach ($languages as $lang) { ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td><?= showValue($lang['language_name']) ?></td>

                    <td><?= !empty($lang['can_read']) ? 'Yes' : 'No' ?></td>

                    <td><?= !empty($lang['can_write']) ? 'Yes' : 'No' ?></td>

                    <td><?= !empty($lang['can_speak']) ? 'Yes' : 'No' ?></td>

                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    No Languages Added
                </td>
            </tr>

        <?php } ?>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Resume
    </div>

    <table>

        <tr>
            <td class="label">Resume Status</td>

            <td>
                <?= !empty($resume) ? 'Uploaded' : 'Not Uploaded' ?>
            </td>
        </tr>

    </table>

</div>

<div class="section">

    <div class="section-title">
        Video Introduction
    </div>

    <table>

        <tr>
            <td class="label">Video Status</td>

            <td>
                <?= !empty($video) ? 'Uploaded' : 'Not Uploaded' ?>
            </td>
        </tr>

        <?php if (!empty($video['video_file'])) { ?>

        <tr>
            <td class="label">Video File</td>

            <td><?= basename($video['video_file']) ?></td>
        </tr>

        <?php } ?>

    </table>

    <p style="margin-top:10px;font-size:11px;color:#666;">
        * Video can be viewed from the PRAYAS Recruitment Portal.
    </p>

</div>


</body>
</html>

<?php

$html = ob_get_clean();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

echo $html;
exit;
//$dompdf->stream(
  //  "Candidate_Report_" . $candidate['registration_id'] . ".pdf",
    //[
      //  "Attachment" => false
   // ]
//);