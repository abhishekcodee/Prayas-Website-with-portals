<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$photo_error = '';
if (isset($_SESSION['photo_error'])) {
    $photo_error = $_SESSION['photo_error'];
    unset($_SESSION['photo_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Capture Photo | PRAYAS Student Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/photo.css">

<style>
.camera-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.video-wrapper {
    position: relative;
    width: 100%;
    border-radius: 16px;
    overflow: hidden;
    background: #0F172A;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.4);
}

video {
    width: 100%;
    display: block;
    border-radius: 16px;
}

canvas {
    display: none;
}

#preview {
    display: none;
    width: 100%;
    border-radius: 16px;
    border: 3px solid #10B981;
}

/* Compact Visual Indicators Bar */
.rules-badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-top: 18px;
}

.rule-pill-badge {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.rule-pill-badge .check-icon {
    color: #10B981;
    font-size: 0.9rem;
}

.btn-capture-primary {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: #FFFFFF;
    border: none;
    padding: 12px 32px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
    transition: all 0.25s ease;
}

.btn-capture-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(16, 185, 129, 0.45);
    color: #FFFFFF;
}

.btn-capture-primary:disabled {
    background: #CBD5E1 !important;
    box-shadow: none !important;
    color: #64748B !important;
    cursor: not-allowed;
}
</style>
</head>

<body class="bg-light">

<?php require_once __DIR__ . '/../includes/nav_header.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="camera-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="fw-bold text-dark m-0">
                        <i class="fa-solid fa-camera text-primary me-2"></i> Photo Capture Verification
                    </h4>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace">
                        Step 3 of 5
                    </span>
                </div>

                <!-- Primary Camera Preview -->
                <div class="video-wrapper">
                    <video id="video" autoplay playsinline muted></video>
                    <canvas id="faceCanvas"></canvas>
                    <img id="preview" alt="Captured Photo Preview">
                </div>

                <?php if (!empty($photo_error)): ?>
                <div class="alert alert-danger shadow-sm fw-bold border-0 text-center mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($photo_error); ?>
                </div>
                <?php endif; ?>

                <!-- Status Feedback Banner -->
                <div id="status" class="alert alert-warning mt-3 mb-2 fw-semibold text-center border-0 shadow-sm">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Detecting face &amp; initializing camera...
                </div>

                <!-- Compact Visual Rule Indicators -->
                <div class="rules-badges-grid">
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>Face clearly visible</span>
                    </div>
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>Look straight at camera</span>
                    </div>
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>Good lighting</span>
                    </div>
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>Only one person</span>
                    </div>
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>No sunglasses</span>
                    </div>
                    <div class="rule-pill-badge">
                        <i class="fa-solid fa-circle-check check-icon"></i>
                        <span>No cap / head covering</span>
                    </div>
                </div>

                <!-- Hidden Canvas for Data Capture -->
                <canvas id="canvas"></canvas>

                <!-- Action Controls -->
                <div class="mt-4 text-center">
                    <button id="capture" type="button" class="btn btn-capture-primary" disabled>
                        <i class="fa-solid fa-camera me-2"></i> Capture Photo
                    </button>

                    <button id="retake" type="button" class="btn btn-outline-secondary btn-lg px-4 py-2" style="display:none;">
                        <i class="fa-solid fa-rotate-left me-2"></i> Retake Photo
                    </button>

                    <form action="save-photo.php<?php echo isset($_GET['from']) ? '?from=' . urlencode($_GET['from']) : ''; ?>" method="POST" class="d-inline">
                        <input type="hidden" name="photo" id="photo">
                        <?php if (isset($_GET['from'])): ?>
                            <input type="hidden" name="from" value="<?php echo htmlspecialchars($_GET['from']); ?>">
                        <?php endif; ?>
                        <button id="save" type="submit" class="btn btn-primary btn-lg px-4 py-2 ms-2" style="display:none;">
                            <span>Save &amp; Continue</span>
                            <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils@0.3/camera_utils.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils@0.3/drawing_utils.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_detection@0.4/face_detection.js"></script>
<script src="../assets/js/camera.js?v=<?php echo time(); ?>"></script>
</body>
</html>