<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, full_name, registration_no, email FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['logged_in' => false]);
    exit;
}

$profileStmt = $pdo->prepare("SELECT photo FROM student_profile WHERE user_id=?");
$profileStmt->execute([$user_id]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

$candidate_photo = '';
if (!empty($profile['photo'])) {
    $photo_filename = basename($profile['photo']);
    if (file_exists("uploads/photos/" . $photo_filename)) {
        $candidate_photo = "student-portal/uploads/photos/" . $photo_filename;
    }
}

echo json_encode([
    'logged_in' => true,
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['full_name'],
        'reg_no' => $user['registration_no'],
        'photo' => $candidate_photo
    ]
]);
