<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Resume Builder - Education</title>

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

<div class="card shadow-lg border-0 rounded-4">

<div class="card-body p-5">

<h3 class="fw-bold">
Resume Builder /रिज्यूमे बिल्डर / रेझ्युमे बिल्डर
</h3>

<p class="text-muted">
Step 1 of 6
</p>

<div class="progress mb-4" style="height:8px;">

<div class="progress-bar bg-success" style="width:16%;"></div>

</div>

<form action="save-education.php" method="POST">

<div id="educationContainer">

<div class="education-item border rounded-3 p-4 mb-4">

<h5 class="mb-4">
Education 1/ / शिक्षा 1 / शिक्षण 1
</h5>

<div class="mb-3">

<label class="form-label">
Qualification/ योग्यता / पात्रता<span class="text-danger">*</span>
</label>

<select
name="qualification[]"
class="form-select qualification-select"
required>

<option value="">Select/ चुनें / निवडा</option>

<option>10th / दसवीं / दहावी</option>

<option>ITI / आईटीआई / आयटीआय</option>

<option>Other / अन्य / इतर</option>

</select>
<div class="other-qualification mt-3" style="display:none;">
    

    <input
        type="text"
        name="other_qualification[]"
        class="form-control other-input"
        placeholder="">
</div>
</div>

<div class="mb-3">

<label>
Specialization
<small class="d-block text-muted">
        विशेषज्ञता | विशेषीकरण
    </small>
</label>

<input
type="text"
name="specialization[]"
class="form-control">

</div>

<div class="mb-3">

<label>
Institute<span class="text-danger">*</span>
 <small class="d-block text-muted">
        संस्थान | संस्था
    </small>
</label>

<input
type="text"
name="institute[]"
class="form-control"
required>

</div>

<div class="mb-3">

<label>
Board / University<span class="text-danger">*</span>
<small class="d-block text-muted">
        बोर्ड / विश्वविद्यालय | मंडळ / विद्यापीठ
    </small>
</label>

<input
type="text"
name="board[]"
class="form-control">

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">
Passing Year<span class="text-danger">*</span>
<small class="d-block text-muted">
        उत्तीर्ण वर्ष | उत्तीर्ण वर्ष
    </small>
</label>

<select name="passing_year[]" class="form-select" required>
    <option value="">Select Passing Year/ उत्तीर्ण वर्ष | उत्तीर्ण वर्ष
</option>

    <?php
    $currentYear = date("Y"); // Current Year (e.g. 2026)

    for ($year = $currentYear; $year >= 1980; $year--) {
        echo "<option value='$year'>$year</option>";
    }
    ?>
</select>

</div>

<div class="col-md-6 mb-3">

<label>
Percentage / CGPA
 <small class="d-block text-muted">
        प्रतिशत / सीजीपीए | टक्केवारी / सीजीपीए
    </small>
</label>

<input
type="text"
name="percentage[]"
class="form-control">

</div>

</div>

<div class="mb-3">

<label>
Status
<small class="d-block text-muted">
        स्थिति | स्थिती
    </small>
</label>

<select
name="status[]"
class="form-select">

<option>Completed (पूर्ण | पूर्ण)</option>

<option>Pursuing (अध्ययनरत | शिकत आहे)</option>

</select>

</div>

</div>

</div>

<button
type="button"
class="btn btn-outline-primary"
id="addEducation">

+ Add Another Education
(एक और शिक्षा जोड़ें | आणखी शिक्षण जोडा)
</button>

<hr class="my-4">

<h5 class="fw-bold mb-3">
Select Your Experience
</h5>

<div class="form-check mb-2">
    <input class="form-check-input"
           type="radio"
           name="experience_type"
           id="fresher"
           value="Fresher"
           checked>

    <label class="form-check-label" for="fresher">
        I am Fresher
        <br>
        <small class="text-muted">
            मैं फ्रेशर हूँ | मी फ्रेशर आहे
        </small>
    </label>
</div>

<div class="form-check mb-4">
    <input class="form-check-input"
           type="radio"
           name="experience_type"
           id="experienced"
           value="Experienced">

    <label class="form-check-label" for="experienced">
        I have Work Experience
        <br>
        <small class="text-muted">
            मेरे पास कार्य अनुभव है | माझ्याकडे कामाचा अनुभव आहे
        </small>
    </label>
</div>

<div class="text-end mt-4">

<button
type="submit"
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
<script>
function initializeOtherQualification() {

    document.querySelectorAll(".qualification-select").forEach(function(select){

        select.addEventListener("change", function(){

            const educationItem = this.closest(".education-item");

            const otherDiv = educationItem.querySelector(".other-qualification");

            const otherInput = educationItem.querySelector(".other-input");

            if(this.value.includes("Other")){

                otherDiv.style.display = "block";
                otherInput.required = true;

            }else{

                otherDiv.style.display = "none";
                otherInput.required = false;
                otherInput.value = "";

            }

        });

    });

}

initializeOtherQualification();
</script>
<script src="../../assets/js/resume-builder.js"></script>

</body>

</html>