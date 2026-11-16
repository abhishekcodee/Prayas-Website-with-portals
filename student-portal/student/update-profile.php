<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    header("Location: candidate-summary.php");
    exit;
}

/* Get Data */
$full_name = trim($_POST['full_name']);
$dob            = $_POST['dob'];
$place_of_birth = trim($_POST['place_of_birth']);
$gender         = $_POST['gender'];
$height_feet    = $_POST['height_feet'];
$height_inches  = $_POST['height_inches'];
$weight         = $_POST['weight'];
$blood_group    = $_POST['blood_group'];
$passport_no    = trim($_POST['passport_no']);
$alternate_number = trim($_POST['alternate_number']);
$emergency_contact = trim($_POST['emergency_contact']);
$aadhaar_no     = trim($_POST['aadhaar_no']);
$pan_no         = strtoupper(trim($_POST['pan_no']));
$govt_id_type   = trim($_POST['govt_id_type'] ?? '');
$govt_id_number = trim($_POST['govt_id_number'] ?? '');
$address        = trim($_POST['address']);
$city           = trim($_POST['city']);
$state          = trim($_POST['state']);
$pincode        = trim($_POST['pincode']);
$permanent_address = trim($_POST['permanent_address']);
$permanent_city    = trim($_POST['permanent_city']);
$permanent_state   = trim($_POST['permanent_state']);
$permanent_pincode = trim($_POST['permanent_pincode']);
$name_changed = $_POST['name_changed'] ?? "No";
$previous_name = trim($_POST['previous_name'] ?? "");
$nationality = trim($_POST['nationality']);
$declaration = isset($_POST['declaration']) ? 1 : 0;
$domicile_state = trim($_POST['domicile_state']);
$religion = $_POST['religion'];
$pwbd = $_POST['pwbd'] ?? 'No';
$pwbd_id = trim($_POST['pwbd_id'] ?? '');
$pwbd_certificate = "";
$category = $_POST['category'];


if(empty($full_name)){
    die("Full Name is required.");
}

if($name_changed == "Yes" && empty($previous_name)){

    die("Please enter your previous full name.");

}

$govt_id_image = "";

if (!empty($_FILES['govt_id_image']['name'])) {

    $uploadDir = "../uploads/govt-id/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['govt_id_image']['name'], PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','pdf'];

    if (!in_array($ext, $allowed)) {
        die("Only JPG, JPEG, PNG and PDF files are allowed.");
    }

    $filename = time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file(
        $_FILES['govt_id_image']['tmp_name'],
        $uploadDir . $filename
    );

    $govt_id_image = $filename;
}
/* PwBD Certificate Upload */

if (!empty($_FILES['pwbd_certificate']['name'])) {

    $uploadDir = "../uploads/pwbd/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['pwbd_certificate']['name'], PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','pdf'];

    if (!in_array($ext, $allowed)) {
        die("Only JPG, JPEG, PNG and PDF files are allowed.");
    }

    $filename = time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file(
        $_FILES['pwbd_certificate']['tmp_name'],
        $uploadDir . $filename
    );

    $pwbd_certificate = $filename;
}

if (empty($permanent_address)) {
    die("Permanent Address is required.");
}

if (empty($permanent_city)) {
    die("Permanent City is required.");
}

if (empty($permanent_state)) {
    die("Permanent State is required.");
}

if (!preg_match('/^[0-9]{6}$/', $permanent_pincode)) {
    die("Enter a valid Permanent Pincode.");
}

/* Update */

$stmt = $pdo->prepare("
UPDATE student_profile
SET
    dob=?,
    place_of_birth=?,
    gender=?,
    height_feet=?,
    height_inches=?,
    weight=?,
    blood_group=?,
    passport_no=?,
    alternate_number = ?,
emergency_contact = ?,
    aadhaar_no=?,
    pan_no=?,
    govt_id_type=?,
govt_id_number=?,
govt_id_image=COALESCE(NULLIF(?,''), govt_id_image),
    address=?,
    city=?,
    state=?,
    pincode=?,
    permanent_address=?,
permanent_city=?,
permanent_state=?,
permanent_pincode=?,
name_changed=?,
previous_name=?,
nationality=?,
domicile_state=?,
declaration=?,
religion = ?,
pwbd=?,
pwbd_id=?,
pwbd_certificate=COALESCE(NULLIF(?,''), pwbd_certificate),
category = ?
WHERE user_id=?
");

$updateUser = $pdo->prepare("
UPDATE users
SET full_name=?
WHERE id=?
");

$updateUser->execute([
    $full_name,
    $user_id
]);

$stmt->execute([
    $dob,
    $place_of_birth,
    $gender,
    $height_feet,
    $height_inches,
    $weight,
    $blood_group,
    $passport_no,
    $alternate_number,
$emergency_contact,
    $aadhaar_no,
    $pan_no,
    $govt_id_type,
    $govt_id_number,
    $govt_id_image,
    $address,
    $city,
    $state,
    $pincode,
    $permanent_address,
$permanent_city,
$permanent_state,
$permanent_pincode,
    $name_changed,
$previous_name,
$nationality,
$domicile_state,
$declaration,
$religion,
$pwbd,
$pwbd_id,
$pwbd_certificate,
$category,
    $user_id
]);

header("Location: candidate-summary.php");
exit;