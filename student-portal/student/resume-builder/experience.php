<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Resume Builder - Experience</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/resume-builder.css">

</head>

<body class="bg-light">

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-7">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold">

Resume Builder

</h2>

<p class="text-muted">

Step 3 of 6

</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:50%"></div>

</div>

<form action="save-experience.php" method="POST">

<h4 class="mb-4">

Select Your Experience

</h4>

<div class="form-check mb-3">

<input
class="form-check-input"
type="radio"
name="experience_type"
value="Fresher"
checked>

<label class="form-check-label">

I am Fresher/ मैं फ्रेशर हूँ | मी फ्रेशर आहे

</label>

</div>

<div class="form-check mb-4">

<input
class="form-check-input"
type="radio"
name="experience_type"
value="Experienced">

<label class="form-check-label">

I have Work Experience/मेरे पास कार्य अनुभव है | माझ्याकडे कामाचा अनुभव आहे

</label>

</div>

<div class="text-end">

<button
class="btn btn-success px-5">

Continue →

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>