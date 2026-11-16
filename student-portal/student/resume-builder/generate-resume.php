<?php

session_start();
require_once "../../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

$user_id=$_SESSION['user_id'];


// Personal + User Details

$stmt = $pdo->prepare("
SELECT
    u.full_name,
    u.email,
    u.mobile,
    sp.*
FROM users u
LEFT JOIN student_profile sp
ON u.id = sp.user_id
WHERE u.id = ?
");

$stmt->execute([$user_id]);

$personal = $stmt->fetch(PDO::FETCH_ASSOC);


// Education

$education=$pdo->prepare("SELECT * FROM education WHERE user_id=?");
$education->execute([$user_id]);


// Skills

$skills=$pdo->prepare("SELECT * FROM skills WHERE user_id=?");
$skills->execute([$user_id]);


// Experience

$experience=$pdo->prepare("SELECT * FROM experience WHERE user_id=?");
$experience->execute([$user_id]);


// Certificates

$certificate=$pdo->prepare("SELECT * FROM certificates WHERE user_id=?");
$certificate->execute([$user_id]);



// Hobbies

$hobby=$pdo->prepare("SELECT * FROM hobbies WHERE user_id=?");
$hobby->execute([$user_id]);
$hobby=$hobby->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Resume Preview</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/resume-preview.css">

</head>

<body class="bg-light">

<div class="container py-5">


<div class="resume shadow">

<h1>

<?= $personal['full_name']; ?>

</h1>

<p>

<?= $personal['email']; ?>

|

<?= $personal['mobile']; ?>

</p>

<hr>

<h3>Education</h3>

<?php
while($row=$education->fetch(PDO::FETCH_ASSOC)){
?>

<b>

<?= $row['qualification']; ?>

</b>

<br>

<?= $row['institute']; ?>

<br>

<?= $row['passing_year']; ?>

<br><br>

<?php } ?>

<h3>

Skills

</h3>

<ul>

<?php

while($row=$skills->fetch(PDO::FETCH_ASSOC)){

?>

<li>

<?= $row['skill_name']; ?>

</li>

<?php } ?>

</ul>

<h3>

Experience

</h3>

<?php

while($row=$experience->fetch(PDO::FETCH_ASSOC)){

?>

<b>

<?= $row['company_name']; ?>

</b>

<br>

<?= $row['designation']; ?>

<br>

<?= date("M Y",strtotime($row['start_date'])) ?>

-

<?= $row['currently_working'] ? "Present" : date("M Y",strtotime($row['end_date'])) ?> Years

<p>

<?= $row['job_description']; ?>

</p>

<?php } ?>

<h3>

Certificates</h3>

<ul>

<?php

while($row=$certificate->fetch(PDO::FETCH_ASSOC)){

?>

<li>

<?= $row['certificate_name']; ?>

</li>

<?php } ?>

</ul>

<h3>

Hobbies

</h3>

<p>

<?= $hobby['hobbies']; ?>

</p>

<h3>

Interests

</h3>

<p>

<?= $hobby['interests']; ?>

</p>



</div>

</div>

</body>

</html>