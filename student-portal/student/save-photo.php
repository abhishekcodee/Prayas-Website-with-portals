<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$from = $_REQUEST['from'] ?? '';

// Helper for error redirect back to camera page
function returnWithError($message, $fromParam) {
    $_SESSION['photo_error'] = $message;
    $redirect = "camera.php" . (!empty($fromParam) ? "?from=" . urlencode($fromParam) : "");
    header("Location: " . $redirect);
    exit;
}

// 1. Check if photo data was received
if (!isset($_POST['photo']) || empty(trim($_POST['photo']))) {
    returnWithError("Photo not received. Please capture a photo first.", $from);
}

$image = $_POST['photo'];
$image = str_replace("data:image/png;base64,", "", $image);
$image = str_replace(" ", "+", $image);

$data = base64_decode($image);

if ($data === false || strlen($data) === 0) {
    returnWithError("Invalid photo image format. Please capture a photo again.", $from);
}

// 2. Prepare upload directory
$uploadDir = "../uploads/photos/";

if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0777, true)) {
        returnWithError("Server error: Unable to create photo upload directory.", $from);
    }
}

// 3. Generate unique file name and save file
$fileName = "user_" . $user_id . "_" . time() . ".png";
$bytesSaved = @file_put_contents($uploadDir . $fileName, $data);

if ($bytesSaved === false || $bytesSaved === 0) {
    returnWithError("Failed to save the captured photo file. Please try again.", $from);
}

// 4. Update student_profile in database
try {
    // Check if profile exists and get existing photo name to delete old image file
    $check = $pdo->prepare("SELECT id, photo FROM student_profile WHERE user_id=?");
    $check->execute([$user_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Delete previous photo file if it exists
        if (!empty($existing['photo'])) {
            $oldFilename = basename($existing['photo']);
            $oldPath = $uploadDir . $oldFilename;
            if (file_exists($oldPath) && $oldFilename !== $fileName) {
                @unlink($oldPath);
            }
        }

        // Update existing profile record
        $stmt = $pdo->prepare("
            UPDATE student_profile
            SET photo=?
            WHERE user_id=?
        ");
        $success = $stmt->execute([$fileName, $user_id]);
    } else {
        // Create new profile record if it doesn't exist
        $stmt = $pdo->prepare("
            INSERT INTO student_profile(user_id, photo)
            VALUES(?,?)
        ");
        $success = $stmt->execute([$user_id, $fileName]);
    }

    if (!$success) {
        @unlink($uploadDir . $fileName);
        returnWithError("Database update failed. Please try capturing the photo again.", $from);
    }
} catch (Exception $e) {
    @unlink($uploadDir . $fileName);
    returnWithError("Database error occurred while saving photo.", $from);
}

// Clear any stale photo error
unset($_SESSION['photo_error']);

// 5. Successful capture & save redirect logic
if ($from === 'candidate-summary') {
    header("Location: candidate-summary.php");
} elseif ($from === 'personal') {
    header("Location: personal.php");
} else {
    // Default redirect target is dashboard.php
    header("Location: ../dashboard.php");
}
exit;