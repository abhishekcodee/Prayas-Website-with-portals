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

<title>Resume Builder - Certificates</title>

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

<div class="col-lg-9">

<div class="card shadow border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold">
Certificates/ प्रमाणपत्र | प्रमाणपत्रे
</h2>

<p class="text-muted">
Step 4 of 6
</p>

<div class="progress mb-4">

<div class="progress-bar bg-success" style="width:66%;"></div>

</div>

<form
action="save-certificates.php"
method="POST"
enctype="multipart/form-data">

<div id="certificateContainer">

<div class="certificate-box border rounded-3 p-4 mb-4">

<h5>Certificate 1 / प्रमाणपत्र 1 | प्रमाणपत्र 1</h5>

<div class="mb-3">

<label class="form-label">
    Certificate Name
    <small class="d-block text-muted">
        प्रमाणपत्र का नाम | प्रमाणपत्राचे नाव
    </small>
</label>

<input
type="text"
name="certificate_name[]"
class="form-control">

</div>

<div class="mb-3">

<label>Organization/ संगठन | संस्था</label>

<input
type="text"
name="organization[]"
class="form-control">

</div>

<div class="row">

<div class="col-md-6">

<label>Issue Date जारी करने की तिथि | जारी करण्याची तारीख</label>

<input
type="date"
name="issue_date[]"
class="form-control"
max="<?= date('Y-m-d'); ?>">

</div>

<div class="col-md-6">

<label>Certificate ID/ प्रमाणपत्र आईडी | प्रमाणपत्र आयडी</label>

<input
type="text"
name="certificate_id[]"
class="form-control">

</div>

</div>

<div class="mt-3">

<label class="form-label">
    Upload Certificate (Optional)
    <small class="d-block text-muted">
        प्रमाणपत्र अपलोड करें (वैकल्पिक) | प्रमाणपत्र अपलोड करा (पर्यायी)
    </small>
</label>

<input
type="file"
name="certificate_file[]"
class="form-control">

</div>

</div>

</div>

<button
type="button"
id="addCertificate"
class="btn btn-outline-primary">

+ Add Another Certificate
+ एक और प्रमाणपत्र जोड़ें
+ आणखी प्रमाणपत्र जोडा
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

<script src="../../assets/js/certificates.js"></script>

</body>

</html>