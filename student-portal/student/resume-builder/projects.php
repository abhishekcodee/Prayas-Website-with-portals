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

<title>Resume Builder - Projects</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-9">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold">

Projects/ प्रोजेक्ट्स | प्रकल्प

</h2>

<p class="text-muted">

Step 5 of 6

</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:83%"></div>

</div>

<form action="save-project.php" method="POST">

<div id="projectContainer">

<div class="project-box border rounded-3 p-4 mb-4">

<h5>

Project 1/ प्रोजेक्ट 1 | प्रकल्प 1

</h5>

<div class="mb-3">

<label class="form-label">
    Project Title
    <small class="d-block text-muted">
        प्रोजेक्ट का शीर्षक | प्रकल्पाचे शीर्षक
    </small>
</label>

<input
type="text"
name="project_title[]"
class="form-control">

</div>

<div class="mb-3">

<label class="form-label">
    Technologies Used
    <small class="d-block text-muted">
        प्रयुक्त तकनीकें | वापरलेली तंत्रज्ञान
    </small>
</label>

<input
type="text"
name="technologies[]"
class="form-control"
placeholder="PHP, MySQL, HTML, CSS">

</div>

<div class="mb-3">

<label class="form-label">
    Project Description
    <small class="d-block text-muted">
        प्रोजेक्ट का विवरण | प्रकल्पाचे वर्णन
    </small>
</label>

<textarea
name="description[]"
class="form-control"
rows="4"></textarea>

</div>

<div class="row">

<div class="col-md-6">

<label>GitHub Link/ गिटहब लिंक | गिटहब लिंक</label>

<input
type="url"
name="github[]"
class="form-control">

</div>

<div class="col-md-6">

<label>Live Project Link/ लाइव प्रोजेक्ट लिंक | लाईव्ह प्रकल्प लिंक</label>

<input
type="url"
name="live[]"
class="form-control">

</div>

</div>

<div class="mt-3">

<label>Duration/ अवधि | कालावधी</label>

<input
type="text"
name="duration[]"
class="form-control"
placeholder="3 Months">

</div>

</div>

</div>

<button
type="button"
id="addProject"
class="btn btn-outline-primary">

+ Add Another Project
+ एक और प्रोजेक्ट जोड़ें
+ आणखी प्रकल्प जोडा
</button>

<div class="text-end mt-4">

<button
class="btn btn-success px-5">

Save & Next →

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<script src="../../assets/js/projects.js"></script>

</body>

</html>