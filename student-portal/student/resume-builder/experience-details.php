<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}

if(!isset($_SESSION['experience_type']) || $_SESSION['experience_type']!="Experienced"){
    header("Location: experience.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Resume Builder - Experience Details</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/experience-details.css">

</head>

<body class="bg-light">
<?php require_once __DIR__ . '/../../includes/nav_header.php'; ?>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-9">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold">

Work Experience/ कार्य अनुभव | कामाचा अनुभव

</h2>

<p class="text-muted">

Step 3 of 6

</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:50%"></div>

</div>

<form action="save-experience-details.php" method="POST">

<div id="experienceContainer">

<div class="experience-box border rounded-3 p-4 mb-4">

<h5 class="mb-3">

Experience 1/ अनुभव 1 | अनुभव 1

</h5>

<div class="row">

<div class="col-md-6 mb-3">

<label>Company Name/ कंपनी का नाम | कंपनीचे नाव</label>

<input
type="text"
name="company_name[]"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label>Designation/ पद | पदनाम</label>

<input
type="text"
name="designation[]"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label>Employment Type/ रोजगार का प्रकार | रोजगाराचा प्रकार</label>

<select
name="employment_type[]"
class="form-select">

<option>Full Time(पूर्णकालिक | पूर्णवेळ)</option>

<option>Part Time(अंशकालिक | अर्धवेळ)</option>

<option>Internship(इंटर्नशिप | इंटर्नशिप)</option>

<option>Freelance(फ्रीलांस | स्वतंत्र काम)</option>

</select>

</div>

<div class="col-md-6 mb-3">

<label>Current Company/ वर्तमान कंपनी | सध्याची कंपनी</label>

<select
name="currently_working[]"
class="form-select">

<option>No/ नहीं | नाही</option>

<option>Yes/ Yes
हाँ | होय</option>

</select>

</div>

<div class="col-md-6 mb-3">

<label>Start Date/ प्रारंभ तिथि | सुरुवातीची तारीख</label>

<input
type="date"
name="start_date[]"
class="form-control"
max="<?= date('Y-m-d'); ?>">

</div>

<div class="col-md-6 mb-3">

<label>End Date/ दिन-माह-वर्ष | दिवस-महिना-वर्ष</label>

<input
type="date"
name="end_date[]"
class="form-control"
max="<?= date('Y-m-d'); ?>">

</div>

<div class="col-12 mb-3">

<label>Job Description/ कार्य विवरण | कामाचे वर्णन</label>

<textarea
name="description[]"
class="form-control"
rows="4"></textarea>

</div>

<div class="col-12">

<label>Achievements/ उपलब्धियाँ | उपलब्धी</label>

<textarea
name="achievement[]"
class="form-control"
rows="3"></textarea>

</div>

</div>

</div>

</div>

<button
type="button"
id="addExperience"
class="btn btn-outline-primary">

+ Add Another Experience
+ एक और अनुभव जोड़ें
+ आणखी अनुभव जोडा
</button>

<div class="text-end mt-4">

<button
class="btn btn-success px-5">

Save & Next →
सहेजें और आगे बढ़ें →
जतन करा व पुढे जा →
</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<script src="../../assets/js/experience-details.js"></script>

</body>

</html>