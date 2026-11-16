<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../config.php";
require_once "../../db.php";

// Excel Headers
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Candidate_Report_" . date("Ymd_His") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";

echo "<tr style='font-weight:bold;background:#d9ead3'>
        <th>Registration No</th>
        <th>Name</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>Gender</th>
        <th>DOB</th>
        <th>City</th>
        <th>State</th>
        <th>Qualification</th>
        <th>Skills</th>
        <th>Resume</th>
        <th>Video</th>
        <th>Registered On</th>
      </tr>";

$sql = "
SELECT
u.id,
u.registration_no,
u.full_name,
u.email,
u.mobile,
u.created_at,

sp.gender,
sp.dob,
sp.city,
sp.state,

GROUP_CONCAT(DISTINCT e.qualification SEPARATOR ', ') AS qualification,
GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ') AS skills,

MAX(r.resume_file) AS resume_file,
MAX(v.video_file) AS video_file

FROM users u

LEFT JOIN student_profile sp
ON u.id = sp.user_id

LEFT JOIN education e
ON u.id = e.user_id

LEFT JOIN skills s
ON u.id = s.user_id

LEFT JOIN resume r
ON u.id = r.user_id

LEFT JOIN video_upload v
ON u.id = v.user_id

GROUP BY u.id
ORDER BY u.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    echo "<tr>";

    echo "<td>".$row['registration_no']."</td>";
    echo "<td>".$row['full_name']."</td>";
    echo "<td>".$row['email']."</td>";
    echo "<td>".$row['mobile']."</td>";
    echo "<td>".$row['gender']."</td>";
    echo "<td>".$row['dob']."</td>";
    echo "<td>".$row['city']."</td>";
    echo "<td>".$row['state']."</td>";
    echo "<td>".$row['qualification']."</td>";
    echo "<td>".$row['skills']."</td>";

    echo "<td>".(!empty($row['resume_file']) ? "Uploaded" : "Not Uploaded")."</td>";

    echo "<td>".(!empty($row['video_file']) ? "Uploaded" : "Not Uploaded")."</td>";

    echo "<td>".$row['created_at']."</td>";

    echo "</tr>";
}

echo "</table>";
exit;