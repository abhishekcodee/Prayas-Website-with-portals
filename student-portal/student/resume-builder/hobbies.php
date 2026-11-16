<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: save-resume.php");
exit;
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Resume Builder - Hobbies</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/resume-builder.css">

</head>

<body class="bg-light">

<header class="prayas-header">

    <div class="prayas-header-inner">

        <div class="prayas-brand">

            <img
                src="../../assets/images/prayas-logo.png"
                alt="PRAYAS - The Automation Academy"
                class="prayas-logo">

        </div>

    </div>

</header>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold">

Hobbies & Interests/ शौक एवं रुचियाँ | छंद आणि आवडी

</h2>

<p class="text-muted">

Step 6 of 6

</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:100%"></div>

</div>

<form action="save-hobbies.php" method="POST">

<div class="mb-4">

<label class="fw-bold">

Hobbies/ शौक | छंद

</label>

<textarea

name="hobbies"

class="form-control"

rows="4"

placeholder="Reading, Cricket, Photography, Travelling"></textarea>

</div>

<div class="mb-4">

<label class="fw-bold">

Interests/ रुचियाँ | आवडी

</label>

<textarea

name="interests"

class="form-control"

rows="4"

placeholder=""></textarea>

</div>

<div class="text-end">

<button

class="btn btn-success px-5">

Next
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