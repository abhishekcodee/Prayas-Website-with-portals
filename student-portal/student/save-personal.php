<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    header("Location: personal.php");
    exit;
}

/* -------------------------------
   Personal Details
--------------------------------*/
$name_changed = $_POST['name_changed'] ?? "No";
$previous_name = trim($_POST['previous_name'] ?? "");
$dob            = $_POST['dob'];
$place_of_birth = trim($_POST['place_of_birth']);
$gender         = $_POST['gender'];
$marital_status = $_POST['marital_status'] ?? '';
$height_feet   = !empty($_POST['height_feet']) ? $_POST['height_feet'] : NULL;
$height_inches = !empty($_POST['height_inches']) ? $_POST['height_inches'] : NULL;
$weight = !empty($_POST['weight']) ? $_POST['weight'] : NULL;
$blood_group    = $_POST['blood_group'];
$passport_no    = trim($_POST['passport_no']);
$aadhaar_no     = trim($_POST['aadhaar_no']);
$pan_no         = strtoupper(trim($_POST['pan_no']));
$govt_id_type   = trim($_POST['govt_id_type'] ?? '');
$govt_id_number = trim($_POST['govt_id_number'] ?? '');
$address = trim($_POST['address']);
$city     = trim($_POST['city']);
$state    = trim($_POST['state']);
$pincode  = trim($_POST['pincode']);
$permanent_address = trim($_POST['permanent_address']);
$permanent_city    = trim($_POST['permanent_city']);
$permanent_state   = trim($_POST['permanent_state']);
$permanent_pincode = trim($_POST['permanent_pincode']);
$religion = $_POST['religion'];
$category = $_POST['category'];
$alternate_number = trim($_POST['alternate_number'] ?? '');
$emergency_contact = trim($_POST['emergency_contact'] ?? '');

// Alternate Number Optional
if ($alternate_number !== '' && !preg_match('/^[0-9]{10}$/', $alternate_number)) {
    die("Alternate Contact Number must be 10 digits.");
}
$domicile_state = $_POST['domicile_state'];
$nationality = trim($_POST['nationality']);
$declaration = isset($_POST['declaration']) ? 1 : 0;
$pwbd = $_POST['pwbd'] ?? 'No';
$pwbd_id = trim($_POST['pwbd_id'] ?? '');
$pwbd_certificate = "";

/* -------------------------------
   Family Details
--------------------------------*/

$father_name           = trim($_POST['father_name']);
$father_age            = $_POST['father_age'] ?? NULL;
$father_qualification  = trim($_POST['father_qualification']);
$father_occupation     = trim($_POST['father_occupation']);
$father_income         = !empty($_POST['father_income']) ? $_POST['father_income'] : NULL;

$mother_name           = trim($_POST['mother_name']);
$mother_age            = $_POST['mother_age'] ?? NULL;
$mother_qualification  = trim($_POST['mother_qualification']);
$mother_occupation     = trim($_POST['mother_occupation']);
$mother_income = !empty($_POST['mother_income']) ? $_POST['mother_income'] : NULL;


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


/* -------------------------------
   Validation
--------------------------------*/

if (
    empty($dob) ||
    empty($gender)
    
) {
    die("Please fill all required fields.");
}
if($name_changed=="Yes" && empty($previous_name)){
    die("Please enter your previous full name.");
}
// Aadhaar Optional
if (!empty($aadhaar_no) && !preg_match('/^[0-9]{12}$/', $aadhaar_no)) {
    die("Enter a valid 12-digit Aadhaar Number.");
}

if (!empty($pan_no) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_no)) {
    die("Enter a valid PAN Number.");
}

if (empty($address)) {
    die("Address is required.");
}

if (empty($city)) {
    die("City is required.");
}

if (empty($state)) {
    die("State is required.");
}

if (!preg_match('/^[0-9]{6}$/', $pincode)) {
    die("Enter a valid 6-digit Pincode.");
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
    die("Enter a valid 6-digit Permanent Pincode.");
}

if (empty($father_name)) {
    die("Please enter Father's Name.");
}

if (empty($mother_name)) {
    die("Please enter Mother's Name.");
}

if (empty($govt_id_type) || empty($govt_id_number)) {
    die("Government ID Type and Number are required.");
}

/* -------------------------------
   Check Profile
--------------------------------*/

$check = $pdo->prepare("SELECT id FROM student_profile WHERE user_id=?");
$check->execute([$user_id]);

if ($check->rowCount() > 0) {

    $update = $pdo->prepare("
        UPDATE student_profile
        SET
            dob=?,
            place_of_birth = ?,
            gender=?,
            marital_status = ?,
            height_feet=?,
            height_inches=?,
            weight=?,
            blood_group=?,
            passport_no=?,
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
            religion = ?,
            alternate_number = ?,
emergency_contact = ?,
            category = ?,
            name_changed=?,
previous_name=?,
            father_name=?,
father_age=?,
father_qualification=?,
father_occupation=?,
father_income=?,

mother_name=?,
mother_age=?,
mother_qualification=?,
mother_occupation=?,
mother_income=?,
domicile_state=?,
pwbd=?,
pwbd_id=?,
pwbd_certificate=COALESCE(NULLIF(?,''), pwbd_certificate),
nationality=?,
declaration=?
        WHERE user_id=?
    ");

    $update->execute([
        $dob,
        $place_of_birth,
        $gender,
        $marital_status,
        $height_feet,
        $height_inches,
        $weight,
        $blood_group,
        $passport_no,
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
        $religion,
        $alternate_number,
$emergency_contact,
        $category,
        $name_changed,
$previous_name,
        $father_name,
$father_age,
$father_qualification,
$father_occupation,
$father_income,

$mother_name,
$mother_age,
$mother_qualification,
$mother_occupation,
$mother_income,
$domicile_state,
$pwbd,
$pwbd_id,
$pwbd_certificate,
$nationality,
$declaration,
$user_id
    ]);

} else {

    $insert = $pdo->prepare("
        INSERT INTO student_profile
(
    user_id,
    dob,
    place_of_birth,
    gender,
    height_feet,
    height_inches,
    weight,
    blood_group,
    passport_no,
    aadhaar_no,
    pan_no,
    govt_id_type,
    govt_id_number,
    govt_id_image,
    address,
    city,
    state,
    pincode,
    permanent_address,
permanent_city,
permanent_state,
permanent_pincode,
    name_changed,
previous_name,
    father_name,
father_age,
father_qualification,
father_occupation,
father_income,

mother_name,
mother_age,
mother_qualification,
mother_occupation,
mother_income,
domicile_state,
alternate_number,
emergency_contact,
pwbd,
pwbd_id,
pwbd_certificate,
   nationality,
    declaration
)
VALUES
(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $insert->execute([
    $user_id,
    $dob,
    $place_of_birth,
    $gender,
    $height_feet,
    $height_inches,
    $weight,
    $blood_group,
    $passport_no,
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
    $father_name,
$father_age,
$father_qualification,
$father_occupation,
$father_income,

$mother_name,
$mother_age,
$mother_qualification,
$mother_occupation,
$mother_income,
$domicile_state,
$alternate_number,
$emergency_contact,
$pwbd,
$pwbd_id,
$pwbd_certificate,
$nationality,
    $declaration
]);

}

/* -------------------------------
   Save Languages
--------------------------------*/

$pdo->prepare("
DELETE FROM student_languages
WHERE user_id=?
")->execute([$user_id]);

$languages = $_POST['language_id'] ?? [];

foreach ($languages as $index => $language_id) {

    if (empty($language_id)) {
        continue;
    }

    $can_read  = isset($_POST["can_read_$index"]) ? 1 : 0;
    $can_write = isset($_POST["can_write_$index"]) ? 1 : 0;
    $can_speak = isset($_POST["can_speak_$index"]) ? 1 : 0;

    if (!$can_read && !$can_write && !$can_speak) {
        continue;
    }

    $insert = $pdo->prepare("
        INSERT INTO student_languages
        (
            user_id,
            language_id,
            can_read,
            can_write,
            can_speak
        )
        VALUES
        (?,?,?,?,?)
    ");

    $insert->execute([
        $user_id,
        $language_id,
        $can_read,
        $can_write,
        $can_speak
    ]);
}

/* -------------------------------
   Save Brother / Sister Details
--------------------------------*/

// Delete old records
$pdo->prepare("
    DELETE FROM student_siblings
    WHERE user_id = ?
")->execute([$user_id]);

// Save new records
if (!empty($_POST['siblings'])) {

    $stmt = $pdo->prepare("
        INSERT INTO student_siblings
        (
            user_id,
            relation,
            name,
            age,
            qualification,
            occupation,
            annual_income
        )
        VALUES
        (?,?,?,?,?,?,?)
    ");

    foreach ($_POST['siblings'] as $row) {

        if (empty($row['relation']) || empty($row['name'])) {
            continue;
        }

        $stmt->execute([
            $user_id,
            trim($row['relation']),
            trim($row['name']),
            !empty($row['age']) ? $row['age'] : NULL,
            trim($row['qualification'] ?? ''),
            trim($row['occupation'] ?? ''),
            !empty($row['annual_income']) ? $row['annual_income'] : NULL
        ]);
    }
}
/* -------------------------------
   Next Step
--------------------------------*/

// Candidate has not confirmed profile yet
$pdo->prepare("
UPDATE users
SET profile_confirmed = 0
WHERE id = ?
")->execute([$user_id]);

header("Location: candidate-summary.php");
exit;