<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Skills</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/resume-builder.css">

</head>

<body class="bg-light">
<?php require_once __DIR__ . '/../../includes/nav_header.php'; ?>

<div class="container py-5">
    
<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h3 class="fw-bold">
Resume Builder
</h3>

<p class="text-muted">
Step 2 of 6
</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:33%"></div>

</div>

<form action="save-skills.php" method="POST">

<div id="skillContainer">

<div class="skill-item border rounded-3 p-4 mb-4">

<h5>Technical Skill/ तकनीकी कौशल | तांत्रिक कौशल्य<span class="text-danger">*</span></h5>

<div class="mb-3">

<label>Skill Name/ कौशल का नाम | कौशल्याचे नाव</label>

<select name="skill_name[]" class="form-select" required>
    <option value="">Select Technical Skill</option>
   
    <option>Carpenter</option>
    <option>Draughtsman Civil</option>
    <option>Electrician</option>
    <option>Fitter</option>
    <option>Maintenance Mechanic</option>
    <option>Plumber</option>
     <option>Surveyor</option>
     <option>Tools & Die maker</option>
    <option>Turner</option>
    <option>Welder</option>
    <option>Wireman</option>
   
</select>
</div>

<div class="mb-3">

<label>Skill Level/ कौशल स्तर | कौशल्य पातळी</label>

<select
name="skill_level[]"
class="form-select">

<option>Beginner(शुरुआती | नवशिक्या)</option>

<option>Intermediate (मध्यम | मध्यम)</option>

<option>Advanced (उन्नत | प्रगत)</option>

<option>Expert (विशेषज्ञ | तज्ञ)</option>

</select>

</div>

</div>

</div>

<button
type="button"
class="btn btn-outline-primary"
id="addSkill">

+ Add Another Skill
+ एक और कौशल जोड़ें
+ आणखी कौशल्य जोडा
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

<script src="../../assets/js/skills.js"></script>

</body>

</html>