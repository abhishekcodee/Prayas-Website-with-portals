<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";
$fromSummary = isset($_GET['from']) && $_GET['from'] == "summary";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Upload</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/resume.css">
</head>

<body>

<?php require_once __DIR__ . '/../includes/nav_header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-5">

                    <span class="step-badge">
                        <i class="fa-solid fa-layer-group"></i> STEP 1 OF 10
                    </span>

                    <div class="progress mt-3 mb-4">
                        <div class="progress-bar bg-success" style="width:10%;"></div>
                    </div>

                    <h2 class="fw-bold">
                        Upload Your Resume<br>
                        अपना रिज्यूमे अपलोड करें<br>
                        तुमचा रेझ्युमे अपलोड करा
                    </h2>

                    <p class="text-muted">
                        Upload your latest resume to continue your registration process.<br>
                        पंजीकरण प्रक्रिया जारी रखने के लिए अपना नवीनतम रिज्यूमे अपलोड करें।<br>
                        नोंदणी प्रक्रिया पुढे सुरू ठेवण्यासाठी तुमचा नवीनतम रेझ्युमे अपलोड करा.
                    </p>

                    <form id="resumeUploadForm"
                        action="resume-upload.php"
                        method="POST"
                        enctype="multipart/form-data">

                        <input type="hidden" name="from" value="<?= $fromSummary ? 'summary' : '' ?>">

                        <div class="upload-box mt-4 text-center" id="dropZone">
                            <div class="upload-icon-wrapper">
                                <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            </div>

                            <h4>
                                Drag &amp; Drop Resume Here<br>
                                रिज्यूमे यहाँ खींचें और छोड़ें<br>
                                रेझ्युमे येथे ड्रॅग आणि ड्रॉप करा
                            </h4>

                            <p class="text-muted">OR</p>

                            <!-- File Input -->
                            <input
                                type="file"
                                id="resume"
                                name="resume"
                                accept=".pdf,.doc,.docx"
                                style="display:none;">

                            <button
                                type="button"
                                id="chooseResume">
                                <i class="fa-solid fa-folder-open me-2"></i> Choose Resume
                            </button>

                            <!-- Selected File -->
                            <div id="fileName" class="mt-3"></div>

                            <div id="resumeError" class="alert alert-danger mt-3 d-none" role="alert">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>
                                <strong>Please upload your resume first.</strong><br>
                                If you don't have a resume, please click <strong>"Skip &amp; Continue"</strong>.
                                <hr class="my-2">
                                <small>
                                    कृपया पहले अपना रिज्यूमे अपलोड करें। यदि आपके पास रिज्यूमे नहीं है, तो <strong>"Skip &amp; Continue"</strong> पर क्लिक करें।<br>
                                    कृपया प्रथम तुमचा रेझ्युमे अपलोड करा. तुमच्याकडे रेझ्युमे नसल्यास, <strong>"Skip &amp; Continue"</strong> वर क्लिक करा.
                                </small>
                            </div>

                            <p class="mt-4 text-muted mb-0">
                                Supported Files: <strong>PDF, DOC, DOCX</strong> &bull; Maximum Size: <strong>5 MB</strong>
                            </p>
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <!-- Skip -->
                            <a href="resume-builder.php" class="btn btn-secondary px-4 py-2">
                                <span>Skip &amp; Continue <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                <small>छोड़ें और आगे बढ़ें &rarr; | वगळा आणि पुढे जा &rarr;</small>
                            </a>

                            <!-- Upload Resume -->
                            <button type="submit" name="upload" class="btn btn-success px-4 py-2">
                                <span>Upload &amp; Continue <i class="fa-solid fa-cloud-arrow-up ms-1"></i></span>
                                <small>अपलोड करें और आगे बढ़ें &rarr; | अपलोड करा आणि पुढे जा &rarr;</small>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/resume.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("resumeUploadForm");
    const resumeInput = document.getElementById("resume");
    const chooseBtn = document.getElementById("chooseResume");
    const dropZone = document.getElementById("dropZone");
    const fileNameDiv = document.getElementById("fileName");
    const resumeError = document.getElementById("resumeError");

    // Click to choose file
    chooseBtn.addEventListener("click", () => resumeInput.click());

    // File selected display handler
    function updateFileDisplay(files) {
        if (files && files.length > 0) {
            const file = files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileNameDiv.innerHTML = `<i class="fa-solid fa-circle-check text-success fs-5"></i> Selected: <strong>${file.name}</strong> (${sizeMB} MB)`;
            resumeError.classList.add("d-none");
        } else {
            fileNameDiv.innerHTML = "";
        }
    }

    resumeInput.addEventListener("change", function () {
        updateFileDisplay(resumeInput.files);
    });

    // Drag and Drop Events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            resumeInput.files = files;
            updateFileDisplay(files);
        }
    });

    // Form Validation
    form.addEventListener("submit", function (event) {
        if (!resumeInput.files || resumeInput.files.length === 0) {
            event.preventDefault();
            resumeError.classList.remove("d-none");
            resumeError.scrollIntoView({ behavior: "smooth", block: "center" });
            return false;
        }
        resumeError.classList.add("d-none");
    });
});
</script>
</body>
</html>