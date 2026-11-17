<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Capture Photograph</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/photo.css">

</head>

<body class="bg-light">
<?php require_once __DIR__ . '/../includes/nav_header.php'; ?>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow-lg border-0 rounded-4">

<div class="card-body p-5">

<h2 class="fw-bold mb-4">
Step 3 – Capture Your Photograph<br>
चरण 3 – अपना फोटो कैप्चर करें<br>
टप्पा 3 – तुमचा फोटो काढा
</h2>

<p class="lead">

Students will be requested to capture a recent passport-size photograph directly using:<br>
छात्रों को अपने डिवाइस के कैमरे का उपयोग करके हाल ही का पासपोर्ट आकार का फोटो कैप्चर करना होगा।<br>
विद्यार्थ्यांनी आपल्या डिव्हाइसच्या कॅमेऱ्याचा वापर करून अलीकडील पासपोर्ट आकाराचा फोटो काढावा.

</p>

<ul class="fs-5">

<li>Laptop Camera<br>
लैपटॉप कैमरा<br>
लॅपटॉप कॅमेरा
</li>

<li>Mobile Camera<br>
मोबाइल कैमरा<br>
मोबाईल कॅमेरा</li>

<li>Tablet Camera<br>
टैबलेट कैमरा<br>
टॅबलेट कॅमेरा</li>

</ul>

<hr>

<h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-camera text-primary me-2"></i> Photo Quality Checklist:</h5>

<div class="row g-2 mb-4">
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">Face clearly visible</span>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">Look straight at camera</span>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">Good lighting</span>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">Only one person</span>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">No sunglasses</span>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="p-2 bg-white border rounded-3 d-flex align-items-center gap-2">
            <span class="text-success fs-5">✓</span>
            <span class="small fw-semibold text-dark">No cap / head covering</span>
        </div>
    </div>
</div>

<div class="text-end mt-5">

<a href="camera.php" class="btn btn-success btn-lg">

Start Camera →<br>
कैमरा शुरू करें →<br>
कॅमेरा सुरू करा →
</a>

</div>

</div>

</div>

</div>

</div>

</div>

</body>

</html>